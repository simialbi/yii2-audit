<?php
/**
 * @package yii2-audit
 * @author Simon Karlen <simi.albi@gmail.com>
 * @version 1.0
 */

namespace simialbi\yii2\audit\models;


use yii\base\Model;
use yii\data\ActiveDataProvider;

class SearchLogAction extends LogAction {
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
	public function scenarios() {
		return Model::scenarios();
	}

	/**
	 * Creates data provider instance with search query applied
	 *
	 * @param array $params
	 *
	 * @return ActiveDataProvider
	 */
	public function search($params) {
		$query = LogAction::find();

		$dataProvider = new ActiveDataProvider([
			'query' => $query
		]);
		$this->load($params);

		if (!$this->validate()) {
			return $dataProvider;
		}

		$query->andFilterWhere([
			self::tableName() . '.[[event_id]]'    => $this->event_id,
			self::tableName() . '.[[schema_name]]' => $this->schema_name,
			self::tableName() . '.[[table_name]]'  => $this->table_name,
			self::tableName() . '.[[relation_id]]' => $this->relation_id,
			self::tableName() . '.[[action]]'      => $this->action,
			self::tableName() . '.[[changed_by]]'  => $this->changed_by,
			self::tableName() . '.[[changed_at]]'  => $this->changed_at
		]);

		$query->andFilterWhere(['like', self::tableName() . '.[[query]]', $this->query])
			  ->andFilterWhere(['like', self::tableName() . '.[[data_before]]', $this->data_before])
			  ->andFilterWhere(['like', self::tableName() . '.[[data_after]]', $this->data_after]);

		return $dataProvider;
	}
}