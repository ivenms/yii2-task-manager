<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_tag}}`.
 */
class m250807_161322_create_task_tag_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_tag}}', [
            'task_id' => $this->integer()->notNull(),
            'tag_id' => $this->integer()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Add composite primary key
        $this->addPrimaryKey('pk_task_tag', '{{%task_tag}}', ['task_id', 'tag_id']);

        // Add foreign keys
        $this->addForeignKey('fk_task_tag_task_id', '{{%task_tag}}', 'task_id', '{{%tasks}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_task_tag_tag_id', '{{%task_tag}}', 'tag_id', '{{%tags}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%task_tag}}');
    }
}
