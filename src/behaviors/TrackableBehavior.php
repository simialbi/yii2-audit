<?php
/**
 * @package yii2-audit
 * @author Simon Karlen <simi.albi@gmail.com>
 * @version 1.0
 */

namespace simialbi\yii2\audit\behaviors;

use simialbi\yii2\audit\models\LogAction;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\helpers\Json;
use Yii;

/**
 * Class TrackableBehavior
 * @package simialbi\yii2\audit\behaviors
 *
 * @property \yii\db\ActiveRecord $owner
 */
class TrackableBehavior extends Behavior {
	const MODE_TRIGGER = 'trigger';
	const MODE_EVENT = 'event';

	/**
	 * @var string what method is used to record changes, either
	 * TrackableBehavior::MODE_TRIGGER or TrackableBehavior::MODE_EVENT.
	 */
	public $mode = self::MODE_TRIGGER;

	/**
	 * @var string Change log table name, may contain schema name.
	 */
	public $auditTableName = '{{%audit_logged_actions}}';


	/**
	 * @inheritdoc
	 */
	public function events() {
		if ($this->mode === self::MODE_TRIGGER) {
			return [
				ActiveRecord::EVENT_AFTER_INSERT => 'updateUser',
				ActiveRecord::EVENT_AFTER_UPDATE => 'updateUser',
				ActiveRecord::EVENT_AFTER_DELETE => 'updateUser'
			];
		}

		return [
			ActiveRecord::EVENT_AFTER_INSERT => 'logAction',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'logAction',
			ActiveRecord::EVENT_AFTER_DELETE => 'logAction'
		];
	}

	/**
	 * @param AfterSaveEvent $event
	 *
	 * @throws \yii\base\InvalidConfigException
	 * @throws \yii\db\Exception
	 */
	public function updateUser($event) {
		$model   = $this->owner;
		$primary = ($event->name === ActiveRecord::EVENT_AFTER_INSERT)
			? $model->getPrimaryKey(false)
			: $model->getOldPrimaryKey(false);
		$schema  = $model::getTableSchema();
		if (is_array($primary)) {
			$primary = $primary[0];
		}
		$logAction = LogAction::find()->where([
			'schema_name' => $schema->schemaName,
			'table_name'  => $schema->name,
			'relation_id' => $primary,
			'changed_by'  => null
		])->orderBy([
			'changed_at' => SORT_DESC
		])->one();
		$userid    = (Yii::$app->user && !Yii::$app->user->isGuest) ? Yii::$app->user->id : null;

		if (!$logAction || !$userid) {
			return;
		}

		$logAction->changed_by = $userid;
		$logAction->save();
	}

	/**
	 * Event based audit action logging
	 *
	 * @param AfterSaveEvent $event
	 *
	 * @return boolean
	 *
	 * @throws \yii\base\InvalidConfigException
	 * @throws \yii\db\Exception
	 */
	public function logAction($event) {
		$model  = $this->owner;
		$schema = $model::getTableSchema();
		$action = null;
		$query  = null;
		switch ($event->name) {
			case ActiveRecord::EVENT_AFTER_INSERT:
				$action = LogAction::ACTION_INSERT;
				$query  = $model::getDb()->createCommand()->insert($schema->name, $model->attributes)->rawSql;
				break;
			case ActiveRecord::EVENT_BEFORE_UPDATE:
				$action = LogAction::ACTION_UPDATE;
				$query  = $model::getDb()
								->createCommand()
								->update($schema->name, $model->attributes, $model->getOldPrimaryKey(true))->rawSql;
				break;
			case ActiveRecord::EVENT_AFTER_DELETE:
				$action = LogAction::ACTION_DELETE;
				$query  = $model::getDb()
								->createCommand()
								->delete($schema->name, $model->getOldPrimaryKey(true))->rawSql;
				break;
		}

		$relation_ids = $model->getPrimaryKey(false);

		$logAction = new LogAction([
			'schema_name' => $schema->schemaName,
			'table_name'  => $schema->name,
			'relation_id' => is_array($relation_ids) ? Json::encode($relation_ids) : (string)$relation_ids,
			'action'      => $action,
			'query'       => $query,
			'data_before' => $action === LogAction::ACTION_INSERT ? null : Json::encode($model->oldAttributes),
			'data_after'  => $action === LogAction::ACTION_DELETE ? null : Json::encode($model->attributes)
		]);

		if (false === ($save = $logAction->save())) {
			Yii::debug($logAction->errors, self::class);
		}

		return $save;
	}
}