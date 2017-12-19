<?php

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\audit\models\LogAction */

use yii\widgets\DetailView;

?>
<div class="row">
	<div class="col-xs-6">
		<h3>Data before</h3>
		<?php
		echo DetailView::widget([
			'model'      => $model->data_before,
			'attributes' => array_keys($model->data_before)
		])
		?>
	</div>
	<div class="col-xs-6">
		<h3>Data after</h3>
		<?php
		echo DetailView::widget([
			'model'      => $model->data_after,
			'attributes' => array_keys($model->data_after)
		]);
		?>
	</div>
</div>