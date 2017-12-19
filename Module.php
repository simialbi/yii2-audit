<?php
/**
 * @package yii2-audit
 * @author Simon Karlen <simi.albi@gmail.com>
 * @version 1.0
 */

namespace simialbi\yii2\audit;

use Yii;

class Module extends \simialbi\yii2\base\Module {
	/**
	 * @var string the namespace that controller classes are in.
	 */
	public $controllerNamespace = "simialbi\yii2\audit\controllers";

	/**
	 * @var string the default route of this module.
	 */
	public $defaultRoute = 'administration';

	/**
	 * @inheritdoc
	 * @throws \yii\base\Exception
	 */
	public function init() {
		if (!Yii::$app->hasModule('gridview')) {
			$this->modules = [
				'gridview' => [
					'class'             => 'kartik\grid\Module',
					'exportEncryptSalt' => Yii::$app->security->generateRandomKey(),
					'i18n'              => [
						'class'            => 'yii\i18n\PhpMessageSource',
						'basePath'         => '@kvgrid/messages',
						'forceTranslation' => true
					]
				]
			];
		}

		parent::init();
	}
}