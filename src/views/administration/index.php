<?php

use kartik\grid\GridView;
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $searchModel \simialbi\yii2\audit\models\SearchLogAction */
/* @var $dataProvider \yii\data\ActiveDataProvider */
/* @var string[] $schemas */
/* @var string[] $tables */
/* @var array $users */
/* @var string $displayField */

$this->title                   = Yii::t('simialbi/audit/administration', 'Audit Administration');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="audit-administration">
	<h1><?= Html::encode($this->title); ?></h1>

	<div class="row">
		<div class="col-12 col-xs-12">
			<?php
			echo GridView::widget([
				'filterModel'  => $searchModel,
				'dataProvider' => $dataProvider,
				'columns'      => [
					[
						'class' => '\kartik\grid\SerialColumn'
					],
					[
						'class'  => '\kartik\grid\ExpandRowColumn',
						'value'  => function () {
							return GridView::ROW_COLLAPSED;
						},
						'detail' => function ($model) {
							/* @var $model \simialbi\yii2\audit\models\LogAction */
							return Yii::$app->controller->renderPartial('_log-data', [
								'model' => $model
							]);
						}
					],
					[
						'class'               => '\kartik\grid\DataColumn',
						'attribute'           => 'schema_name',
						'filter'              => $schemas,
						'filterType'          => GridView::FILTER_SELECT2,
						'filterWidgetOptions' => [
							'options'       => [
								'placeholder' => ''
							],
							'pluginOptions' => [
								'allowClear' => true
							]
						]
					],
					[
						'class'               => '\kartik\grid\DataColumn',
						'attribute'           => 'table_name',
						'filter'              => $tables,
						'filterType'          => GridView::FILTER_SELECT2,
						'filterWidgetOptions' => [
							'options'       => [
								'placeholder' => ''
							],
							'pluginOptions' => [
								'allowClear' => true
							]
						]
					],
					'relation_id',
					[
						'class'               => '\kartik\grid\DataColumn',
						'attribute'           => 'action',
						'filter'              => [
							'I' => 'Insert',
							'U' => 'Update',
							'D' => 'Delete'
						],
						'filterType'          => GridView::FILTER_SELECT2,
						'filterWidgetOptions' => [
							'options'       => [
								'placeholder' => ''
							],
							'pluginOptions' => [
								'allowClear' => true
							]
						]
					],
					[
						'class'               => '\kartik\grid\DataColumn',
						'attribute'           => 'changed_by',
						'value'               => function ($model) use ($displayField) {
							return $displayField
								? call_user_func([Yii::$app->user->identityClass, 'findIdentity'], $model->changed_by)->$displayField
								: $model->changed_by;
						},
						'filter'              => $users,
						'filterType'          => GridView::FILTER_SELECT2,
						'filterWidgetOptions' => [
							'options'       => [
								'placeholder' => ''
							],
							'pluginOptions' => [
								'allowClear' => true
							]
						]
					],
					[
						'class'               => '\kartik\grid\DataColumn',
						'attribute'           => 'changed_at',
						'filterType'          => GridView::FILTER_DATETIME,
						'filterWidgetOptions' => [
							'convertFormat' => true,
							'pluginOptions' => [
								'format'         => 'd.m.Y H:i:s',
								'todayHighlight' => true
							]
						]
					],
					[
						'class'    => '\kartik\grid\ActionColumn',
						'template' => '{restore} {delete}',
						'buttons'  => [
							'restore' => function ($url) {
								/* @var string $url */
								return Html::a('<i class="fas fa-upload"></i>', $url, [
									'title' => Yii::t('simialbi/audit/administration', 'Restore'),
									'data'  => [
										'pjax' => '0'
									]
								]);
							}
						]
					]
				]
			]);
			?>
		</div>
	</div>
</div>