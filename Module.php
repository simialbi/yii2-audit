<?php
/**
 * @package yii2-audit
 * @author Simon Karlen <simi.albi@gmail.com>
 * @version 1.0
 */

namespace simialbi\yii2\audit;

use yii\base\BootstrapInterface;
use Yii;

class Module extends \simialbi\yii2\base\Module implements BootstrapInterface {
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

	/**
	 * Bootstrap method to be called during application bootstrap stage.
	 *
	 * @param \yii\base\Application $app the application currently running
	 */
	public function bootstrap($app) {
		\Yii::setAlias('@tonic', '@vendor/tonic');

		$app->urlManager->addRules([
			$this->id                                                        => $this->id.'/'.$this->defaultRoute,
			$this->id.'/<controller:[a-zA-Z0-9\-]+>'                         => $this->id.'/<controller>',
			$this->id.'/<controller:[a-zA-Z0-9\-]+>/<action:[a-zA-Z0-9\-]+>' => $this->id.'/<controller>/<action>'
		], false);
	}
}