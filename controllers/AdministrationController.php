<?php
/**
 * @package yii2-audit
 * @author Simon Karlen <simi.albi@gmail.com>
 * @version 1.0
 */


namespace simialbi\yii2\audit\controllers;

use simialbi\yii2\audit\models\SearchLogAction;
use yii\web\Controller;
use Yii;

class AdministrationController extends Controller {
	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return [];
	}

	/**
	 *
	 * @throws \yii\base\NotSupportedException
	 */
	public function actionIndex() {
		$searchModel  = new SearchLogAction();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);

		$schema = Yii::$app->db->getSchema();

		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
			'schemas'      => $schema->getSchemaNames(),
			'tables'       => $schema->getTableNames()
		]);
	}
}