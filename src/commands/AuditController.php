<?php
/**
 * @package yii2-audit
 * @author Simon Karlen <simi.albi@gmail.com>
 * @version 1.0
 */

namespace simialbi\yii2\audit\commands;

use simialbi\yii2\audit\models\LogAction;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Exception;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\Console;
use yii\helpers\Inflector;

/**
 * Class AuditController
 * @package simialbi\yii2\audit\commands
 */
class AuditController extends Controller
{
    public $defaultAction = 'rotate';

    /**
     * Delete all entries older than the range defined in module and update auto increment values
     *
     * @param string $rotateDateRange The date range until entries should get deleted while rotating. This has to be a
     * string parsable by `strtotime` function.
     *
     * @throws Exception
     */
    public function actionRotate($rotateDateRange = '-3 months')
    {
        LogAction::deleteAll(['<=', 'changed_at', strtotime($rotateDateRange)]);
        $query = new Query();
        $query->select([
            'min' => new Expression('MIN([[event_id]])'),
            'max' => new Expression('MAX([[event_id]])')
        ])->from(LogAction::tableName());
        $result = $query->one();
        $toSubtract = $result['min'] - 1;
        $newAutoIncrement = $result['max'] - $toSubtract + 1;
        LogAction::updateAllCounters(['event_id' => $toSubtract * -1]);
        LogAction::getDb()
            ->createCommand()
            ->setSql('ALTER TABLE ' . LogAction::tableName() . ' AUTO_INCREMENT = ' . $newAutoIncrement)
            ->execute();
    }

    /**
     * Add a database audit trigger for passed model name
     *
     * @param string $modelName Model name to install trigger for
     *
     * @return integer Exit code
     */
    public function actionAddTrigger($modelName = '')
    {
        if (!class_exists($modelName)) {
            $this->stderr("Class '$modelName' does not exists", Console::FG_RED);

            return ExitCode::DATAERR;
        }
        $model = new $modelName;
        if (!($model instanceof \yii\db\ActiveRecord)) {
            $this->stderr("Class '$modelName' must extend '\yii\db\ActiveRecord'", Console::FG_RED);

            return ExitCode::DATAERR;
        }

        /* @var $modelName \yii\db\ActiveRecord */
        $db = $modelName::getDb();

        switch ($db->driverName) {
            case 'mssql':
            case 'sqlsrv':
            case 'dblib':
                $trigger = $this->getTriggerMSSQL($modelName::tableName(), $db);
                break;

            case 'mysql':
                $trigger = $this->getTriggerMySQL($modelName::tableName(), $db);
                break;

            case 'oci':
            default:
                $this->stderr("Driver '{$db->driverName}' is not yet supported", Console::FG_YELLOW);

                return ExitCode::USAGE;
        }

        try {
            $db->createCommand($trigger)->execute();
        } catch (Exception $e) {
            $this->stderr("An error occured: '" . $e->getMessage() . "'", Console::FG_RED);

            return ExitCode::SOFTWARE;
        }

        $this->stdout("Trigger installed for table model '$modelName'", Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Get mssql trigger version
     *
     * @param string $tableName
     * @param \yii\db\Connection $db
     *
     * @return string
     */
    protected function getTriggerMSSQL($tableName, $db)
    {
        $tableSchema = $db->getTableSchema($tableName);
        $origTableName = Inflector::slug($tableSchema->name, '_', true);
        $primary = $tableSchema->primaryKey[0];

        $trigger = <<<SQL
CREATE TRIGGER {{%log_action_{$origTableName}}} ON $tableName AFTER INSERT, UPDATE, DELETE AS
BEGIN 
	SET NOCOUNT ON
	
	DECLARE @beforeJson nvarchar(MAX),
			@afterJson nvarchar(MAX),
			@sql nvarchar(MAX),
			@action char(1),
			@primary NVARCHAR(255);
			
	SET @action = 'I';
	IF EXISTS(SELECT * FROM DELETED)
	BEGIN
		SET @action =
			CASE
				WHEN EXISTS(SELECT * FROM INSERTED) THEN 'U'
				ELSE 'D'
		  	END
	END
	
	SET @primary = (SELECT TOP 1 [[$primary]] FROM DELETED)
	IF NOT EXISTS(SELECT * FROM DELETED)
	BEGIN
		SET @primary = (SELECT TOP 1 [[$primary]] FROM INSERTED)
	END
	
	SET @sql = 'DBCC INPUTBUFFER(' + CAST(@@SPID AS nvarchar(100)) + ')'
	CREATE TABLE #SQL (
	    EventType varchar(100),
	    Parameters int,
	    EventInfo nvarchar(max)
	)
	INSERT INTO #SQL
	EXEC sp_executesql @sql
	
	SELECT @sql = EventInfo FROM #SQL
	DROP TABLE #SQL

	SET @beforeJson = (SELECT * FROM DELETED FOR JSON AUTO)
	SET @afterJson = (SELECT * FROM INSERTED FOR JSON AUTO)
	-- Remove array brackets left and right
	SET @beforeJson = (SELECT SUBSTRING(@beforeJson, 2, LEN(@beforeJson) - 2))
	SET @afterJson = (SELECT SUBSTRING(@afterJson, 2, LEN(@afterJson) - 2))
			
	INSERT INTO {{%audit_logged_actions}} (
		[[schema_name]],
		[[table_name]],
		[[relation_id]],
		[[action]],
		[[query]],
		[[data_before]],
		[[data_after]]
	) VALUES (
		'{$tableSchema->schemaName}',
		'{$tableSchema->name}',
		@primary,
		@action,
		@sql,
		@beforeJson,
		@afterJson
	)
END
SQL;

        return $trigger;
    }

    /**
     * Get mysql trigger version
     *
     * @param string $tableName
     * @param \yii\db\Connection $db
     *
     * @return string
     */
    protected function getTriggerMySQL($tableName, $db)
    {
        $tableSchema = $db->getTableSchema($tableName);
        $origTableName = Inflector::slug($tableSchema->name, '_', true);
        $primary = $tableSchema->primaryKey[0];

        $beforeJson = "\tSET @beforeJson = SELECT JSON_OBJECT(";
        $afterJson = "\tSET @afterJson = SELECT JSON_OBJECT(";
        foreach ($tableSchema->columnNames as $columnName) {
            $beforeJson .= "'$columnName', OLD.$columnName";
            $afterJson .= "'$columnName', NEW.$columnName";
        }
        $beforeJson .= ");\n";
        $afterJson .= ");\n";

        $trigger = <<<SQL
DELIMITER $$

CREATE TRIGGER {{%log_action_{$origTableName}_ins}} AFTER INSERT ON $tableName
FOR EACH ROW
BEGIN
	$afterJson
	SET @sql = SELECT [[info]] FROM [[INFORMATION_SCHEMA]].[[PROCESSLIST]] WHERE [[id]] = CONNECTION_ID();

	INSERT INTO {{%audit_logged_actions}} (
		[[schema_name]],
		[[table_name]],
		[[relation_id]],
		[[action]],
		[[query]],
		[[data_before]],
		[[data_after]]
	) VALUES (
		'{$tableSchema->schemaName}',
		'{$tableSchema->name}',
		NEW.[[$primary]],
		'I',
		@sql,
		NULL,
		@afterJson
	);
END$$

CREATE TRIGGER {{%log_action_{$origTableName}_upd}} AFTER UPDATE ON $tableName
FOR EACH ROW
BEGIN
	$beforeJson
	$afterJson
	SET @sql = SELECT [[info]] FROM [[INFORMATION_SCHEMA]].[[PROCESSLIST]] WHERE [[id]] = CONNECTION_ID();

	INSERT INTO {{%audit_logged_actions}} (
		[[schema_name]],
		[[table_name]],
		[[relation_id]],
		[[action]],
		[[query]],
		[[data_before]],
		[[data_after]]
	) VALUES (
		'{$tableSchema->schemaName}',
		'{$tableSchema->name}',
		OLD.[[$primary]],
		'U',
		@sql,
		@beforeJson,
		@afterJson
	);
END$$

CREATE TRIGGER {{%log_action_{$origTableName}_del}} AFTER DELETE ON $tableName
FOR EACH ROW
BEGIN
	$beforeJson
	SET @sql = SELECT [[info]] FROM [[INFORMATION_SCHEMA]].[[PROCESSLIST]] WHERE [[id]] = CONNECTION_ID();

	INSERT INTO {{%audit_logged_actions}} (
		[[schema_name]],
		[[table_name]],
		[[relation_id]],
		[[action]],
		[[query]],
		[[data_before]],
		[[data_after]]
	) VALUES (
		'{$tableSchema->schemaName}',
		'{$tableSchema->name}',
		OLD.[[$primary]],
		'D',
		@sql,
		@beforeJson,
		NULL
	);
END$$


DELIMITER ;
SQL;

        return $trigger;
    }
}