<?php
/**
 * Created by PhpStorm.
 * User: karlen
 * Date: 28.10.2017
 * Time: 13:53
 */

class m171028_135356_create_audit_table extends \yii\db\Migration {
	/**
	 * @inheritdoc
	 */
	public function safeUp() {
		$this->createTable('{{%audit_logged_actions}}', [
			'event_id'    => $this->bigPrimaryKey()->comment('Unique identifier for each auditable event'),
			'schema_name' => $this->string(255)->notNull()->comment('Database schema audited table for this event is in'),
			'table_name'  => $this->string(255)->notNull()->comment('Non-schema-qualified table name of table event occured in'),
			'action'      => $this->char(1)->notNull()->check('action IN (\'I\',\'D\',\'U\', \'T\')')->comment('Action type; I = insert, D = delete, U = update, T = truncate'),
			'query'       => $this->text()->null()->defaultValue(null)->comment('Top-level query that caused this auditable event. May be more than one statement.'),
			'data_before' => $this->text()->null()->defaultValue(null)->comment('Row data before event'),
			'data_after'  => $this->text()->null()->defaultValue(null)->comment('Row data after event'),
			'changed_by'  => $this->string(255)->null()->defaultValue(null)->comment('User who triggered the event'),
			'changed_at'  => $this->timestamp()->notNull()->defaultValue(new \yii\db\Expression('CURRENT_TIMESTAMP'))->comment('Timestamp when the event got triggered')
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown() {
		$this->dropTable('{{%audit_logged_actions}}');
	}
}