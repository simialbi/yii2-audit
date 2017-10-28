<?php
/**
 * Created by PhpStorm.
 * User: karlen
 * Date: 28.10.2017
 * Time: 13:48
 */

namespace simialbi\yii2\audit\behaviors;

use yii\base\Behavior;

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
	public $auditTableName = '';
}