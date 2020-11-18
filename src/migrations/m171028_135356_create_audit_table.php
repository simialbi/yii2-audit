<?php
/**
 * @package yii2-audit
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\audit\migrations;

use yii\db\Expression;
use yii\db\Migration;

class m171028_135356_create_audit_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%audit_logged_actions}}', [
            'event_id' => $this->bigPrimaryKey()->comment('Unique identifier for each auditable event'),
            'schema_name' => $this->string(255)
                ->notNull()
                ->comment('Database schema audited table for this event is in'),
            'table_name' => $this->string(255)
                ->notNull()
                ->comment('Non-schema-qualified table name of table event occured in'),
            'relation_id' => $this->string(255)->notNull()->comment('Primary key value of this auditable event'),
            'action' => $this->char(1)
                ->notNull()
                ->check('action IN (\'I\',\'D\',\'U\',\'T\')')
                ->comment('Action type; I = insert, D = delete, U = update, T = truncate'),
            'query' => $this->text()
                ->null()
                ->defaultValue(null)
                ->comment('Top-level query that caused this auditable event. May be more than one statement.'),
            'data_before' => $this->text()->null()->defaultValue(null)->comment('Row data before event'),
            'data_after' => $this->text()->null()->defaultValue(null)->comment('Row data after event'),
            'changed_by' => $this->integer()->null()->defaultValue(null)->comment('User who triggered the event'),
            'changed_at' => $this->timestamp()
                ->notNull()
                ->defaultValue(new Expression('CURRENT_TIMESTAMP'))
                ->comment('Timestamp when the event got triggered')
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable('{{%audit_logged_actions}}');
    }
}