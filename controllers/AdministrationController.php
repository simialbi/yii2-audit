<?php
/**
 * @package yii2-audit
 * @author Simon Karlen <simi.albi@gmail.com>
 * @version 1.0
 */


namespace simialbi\yii2\audit\controllers;

use simialbi\yii2\audit\models\LogAction;
use simialbi\yii2\audit\models\SearchLogAction;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use Yii;

class AdministrationController extends Controller {
	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return [
			'verbs' => [
				'class'   => VerbFilter::className(),
				'actions' => [
					'delete' => ['post', 'delete']
				]
			]
		];
	}

	/**
	 *
	 * @throws \yii\base\NotSupportedException
	 */
	public function actionIndex() {
		$searchModel  = new SearchLogAction();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);

		$schema = Yii::$app->db->getSchema();

		$users      = [];
		$primaryKey = '';
		if ($this->module->userClass && class_exists($this->module->userClass)) {
			$class = $this->module->userClass;
			$data  = $class::find()->where($this->module->userCondition)->all();

			if ($this->module->userIdField) {
				$primaryKey = $this->module->userIdField;
			} else {
				// TODO: From class
			}

			foreach ($data as $row) {
				/* @var $row \yii\db\ActiveRecord */
				$row    = $row->toArray();
				/* @var $row array */
				$search = array_combine(array_map(function ($el) {
					return '{' . $el . '}';
				}, array_keys($row)), $row);

				$users[$row[$primaryKey]] = strtr($this->module->userTemplate, $search);
			}
		}

		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
			'schemas'      => $schema->getSchemaNames(),
			'tables'       => $schema->getTableNames(),
			'users'        => $users,
			'primaryKey'   => $primaryKey
		]);
	}

	/**
	 * Restore audit
	 *
	 * @param integer $id
	 *
	 * @throws NotFoundHttpException
	 */
	public function actionRestore($id) {
		$model = $this->findModel($id); // TODO
	}

	/**
	 * Delete audit
	 *
	 * @param integer $id
	 *
	 * @return \yii\web\Response
	 *
	 * @throws \Exception
	 * @throws \Throwable
	 */
	public function actionDelete($id) {
		$model = $this->findModel($id);

		if ($model->delete()) {
			Yii::$app->session->addFlash('success', Yii::t(
				'simialbi/audit/notification',
				'Audit <b>{audit}</b> deleted',
				['audit' => $model->table_name . '-' . $model->relation_id]
			));
		} else {
			Yii::$app->session->addFlash('error', Yii::t(
				'simialbi/audit/notification',
				'Failed to save audit <b>{audit}</b>',
				['audit' => $model->table_name . '-' . $model->relation_id]
			));
		}

		return $this->redirect(['index']);
	}

	/**
	 * Finds the Audit based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 *
	 * @return LogAction the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id) {
		if (($model = LogAction::findOne($id)) !== null) {
			return $model;
		} else {
			throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
		}
	}
}