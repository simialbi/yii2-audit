<?php
/**
 * @package yii2-audit
 * @author Simon Karlen <simi.albi@gmail.com>
 * @version 1.0
 */


namespace simialbi\yii2\audit\controllers;

use simialbi\yii2\audit\models\LogAction;
use simialbi\yii2\audit\models\SearchLogAction;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class AdministrationController
 * @package simialbi\yii2\audit\controllers
 *
 * @property-read \simialbi\yii2\audit\Module $module
 */
class AdministrationController extends Controller {
	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return [
			'verbs' => [
				'class'   => VerbFilter::class,
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

		$users = [];
		$class = Yii::$app->user->identityClass;
		/** @var $class \yii\db\ActiveRecord */
		if ($class && class_exists($class) && method_exists($class, 'find') && $this->module->userDisplayField) {
			$query = $class::find();

			if ($this->module->userCondition) {
				$query->where($this->module->userCondition);
			}

			foreach ($query->all() as $user) {
				/** @var $user \yii\web\IdentityInterface */
				$users[$user->getId()] = $user->{$this->module->userDisplayField};
			}
		}

		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
			'schemas'      => $schema->getSchemaNames(),
			'tables'       => $schema->getTableNames(),
			'users'        => $users,
			'displayField' => $this->module->userDisplayField
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

		$schema = $model::getDb()->getTableSchema($model->table_name);

		echo "<pre>";
		var_dump($schema);
		exit("</pre>");

		switch ($model->action) {
			case LogAction::ACTION_INSERT:
				break;
			case LogAction::ACTION_UPDATE:
				break;
			case LogAction::ACTION_DELETE:
				break;
		}
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