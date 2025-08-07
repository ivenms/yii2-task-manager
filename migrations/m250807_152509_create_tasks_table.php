<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%tasks}}`.
 */
class m250807_152509_create_tasks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%tasks}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string()->notNull(),
            'description' => $this->text(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'priority' => $this->string(20)->notNull()->defaultValue('medium'),
            'due_date' => $this->date(),
            'deleted_at' => $this->timestamp(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Add indexes for better query performance
        $this->createIndex('idx_tasks_status', '{{%tasks}}', 'status');
        $this->createIndex('idx_tasks_priority', '{{%tasks}}', 'priority');
        $this->createIndex('idx_tasks_due_date', '{{%tasks}}', 'due_date');
        $this->createIndex('idx_tasks_deleted_at', '{{%tasks}}', 'deleted_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%tasks}}');
    }
}
