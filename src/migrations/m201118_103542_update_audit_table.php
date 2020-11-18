<?php
/**
 * @package yii2-audit
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\audit\migrations;

use yii\db\Expression;
use yii\db\Migration;

/**
 * Class m201118_103542_update_audit_table
 * @package simialbi\yii2\audit\migrations
 */
class m201118_103542_update_audit_table extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{%audit_logged_actions}}',
            'changed_at',
            $this->string(255)
        );
        $this->update('{{%audit_logged_actions}}', [
            'changed_at' => new Expression('UNIX_TIMESTAMP([[changed_at]])')
        ]);
        $this->alterColumn(
            '{{%audit_logged_actions}}',
            'changed_at',
            $this->integer()->unsigned()->notNull()->comment('Timestamp when the event got triggered')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown()
    {
        $this->alterColumn(
            '{{%audit_logged_actions}}',
            'changed_at',
            $this->string(255)->notNull()
        );
        $this->update('{{%audit_logged_actions}}', [
            'changed_at' => new Expression('DATE_FORMAT(\'%Y-%m-%d %H:%i:%s\', FROM_UNIXTIME([[changed_at]])))')
        ]);
        $this->alterColumn(
            '{{%audit_logged_actions}}',
            'changed_at',
            $this->timestamp()
                ->notNull()
                ->defaultValue(new Expression('CURRENT_TIMESTAMP'))
                ->comment('Timestamp when the event got triggered')
        );
    }
}