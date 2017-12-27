<?php
/**
 * Created by PhpStorm.
 * User: karlen
 * Date: 28.10.2017
 * Time: 15:18
 */

namespace simialbi\yii2\audit\models;


use yii\base\InvalidParamException;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use Yii;
use yii\db\Expression;
use yii\helpers\Json;

/**
 * Class LogAction
 * @package simialbi\yii2\audit\models
 *
 * @property integer $event_id
 * @property string $schema_name
 * @property string $table_name
 * @property string $relation_id
 * @property string $action
 * @property string $query
 * @property array $data_before
 * @property array $data_after
 * @property string $changed_by
 * @property string $changed_at
 */
class LogAction extends ActiveRecord {
	/**
	 * @inheritdoc
	 */
	public static function tableName() {
		return '{{%audit_logged_actions}}';
	}

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['event_id', 'changed_by'], 'integer'],
			[['schema_name', 'table_name', 'action', 'query', 'relation_id'], 'string'],
			[['data_before', 'data_after', 'changed_at'], 'safe']
		];
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return [
			'blameable' => [
				'class'              => BlameableBehavior::className(),
				'createdByAttribute' => 'changed_by',
				'updatedByAttribute' => 'changed_by'
			],
			'timestamp' => [
				'class'              => TimestampBehavior::className(),
				'createdAtAttribute' => 'changed_at',
				'updatedAtAttribute' => null,
				'value'              => function() {
					return new Expression('CURRENT_TIMESTAMP');
				}
			]
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels() {
		return [
			'event_id'    => Yii::t('simialbi/audit/model/log-action', 'Event id'),
			'schema_name' => Yii::t('simialbi/audit/model/log-action', 'Schema name'),
			'table_name'  => Yii::t('simialbi/audit/model/log-action', 'Table name'),
			'relation_id' => Yii::t('simialbi/audit/model/log-action', 'Relation id'),
			'action'      => Yii::t('simialbi/audit/model/log-action', 'Action'),
			'query'       => Yii::t('simialbi/audit/model/log-action', 'Query'),
			'data_before' => Yii::t('simialbi/audit/model/log-action', 'Data before'),
			'data_after'  => Yii::t('simialbi/audit/model/log-action', 'Data after'),
			'changed_by'  => Yii::t('simialbi/audit/model/log-action', 'Changed by'),
			'changed_at'  => Yii::t('simialbi/audit/model/log-action', 'Changed at')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind() {
		try {
			$this->data_before = @Json::decode((string) $this->data_before);
			$this->data_after  = @Json::decode((string) $this->data_after);
		} catch (InvalidParamException $e) {
			Yii::warning($e->getMessage(), static::className());
		}

		parent::afterFind();
	}
}