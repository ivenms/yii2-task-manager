<?php

namespace tests\unit;

use Yii;
use tests\TestCase;
use app\models\Task;
use app\models\Tag;

class TaskModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
        $this->cleanDatabase();
    }

    public function testCreateTask()
    {
        $task = new Task([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => '2024-12-31'
        ]);

        $this->assertTrue($task->save());
        $this->assertNotNull($task->id);
        $this->assertEquals('Test Task', $task->title);
        $this->assertEquals('pending', $task->status);
    }

    public function testTaskValidation()
    {
        $task = new Task();
        
        $this->assertFalse($task->validate());
        $this->assertArrayHasKey('title', $task->getErrors());
        
        $task->title = 'Valid Title';
        $this->assertTrue($task->validate());
    }

    public function testTaskStatusConstants()
    {
        $this->assertEquals('pending', Task::STATUS_PENDING);
        $this->assertEquals('in_progress', Task::STATUS_IN_PROGRESS);
        $this->assertEquals('completed', Task::STATUS_COMPLETED);
    }

    public function testTaskPriorityConstants()
    {
        $this->assertEquals('low', Task::PRIORITY_LOW);
        $this->assertEquals('medium', Task::PRIORITY_MEDIUM);
        $this->assertEquals('high', Task::PRIORITY_HIGH);
    }

    public function testSoftDelete()
    {
        $task = new Task([
            'title' => 'Task to Delete',
            'status' => 'pending'
        ]);
        $task->save();

        $this->assertTrue($task->softDelete());
        $this->assertNotNull($task->deleted_at);

        // Task should not be found in normal queries
        $foundTask = Task::findOne($task->id);
        $this->assertNull($foundTask);

        // Task should be found with findWithDeleted
        $deletedTask = Task::findWithDeleted()->where(['id' => $task->id])->one();
        $this->assertNotNull($deletedTask);
    }

    public function testRestore()
    {
        $task = new Task([
            'title' => 'Task to Restore',
            'status' => 'pending'
        ]);
        $task->save();
        $task->softDelete();

        $deletedTask = Task::findWithDeleted()->where(['id' => $task->id])->one();
        $this->assertTrue($deletedTask->restore());
        $this->assertNull($deletedTask->deleted_at);

        // Task should be found in normal queries again
        $restoredTask = Task::findOne($task->id);
        $this->assertNotNull($restoredTask);
    }

    public function testTaskTagRelation()
    {
        $task = new Task([
            'title' => 'Task with Tags',
            'status' => 'pending'
        ]);
        $task->save();

        $tag1 = new Tag(['name' => 'work']);
        $tag1->save();
        
        $tag2 = new Tag(['name' => 'urgent']);
        $tag2->save();

        // Link tags to task
        Yii::$app->db->createCommand()
            ->insert('{{%task_tag}}', ['task_id' => $task->id, 'tag_id' => $tag1->id])
            ->execute();
        
        Yii::$app->db->createCommand()
            ->insert('{{%task_tag}}', ['task_id' => $task->id, 'tag_id' => $tag2->id])
            ->execute();

        // Test relation
        $task->refresh();
        $tags = $task->tags;
        
        $this->assertCount(2, $tags);
        $this->assertEquals('work', $tags[0]->name);
        $this->assertEquals('urgent', $tags[1]->name);
    }

    public function testFindActiveOnly()
    {
        // Create active task
        $activeTask = new Task([
            'title' => 'Active Task',
            'status' => 'pending'
        ]);
        $activeTask->save();

        // Create and delete task
        $deletedTask = new Task([
            'title' => 'Deleted Task',
            'status' => 'pending'
        ]);
        $deletedTask->save();
        $deletedTask->softDelete();

        // Default find should only return active tasks
        $activeTasks = Task::find()->all();
        $this->assertCount(1, $activeTasks);
        $this->assertEquals('Active Task', $activeTasks[0]->title);

        // findWithDeleted should return all tasks
        $allTasks = Task::findWithDeleted()->all();
        $this->assertCount(2, $allTasks);
    }

    public function testTaskAttributeLabels()
    {
        $task = new Task();
        $labels = $task->attributeLabels();
        
        $this->assertArrayHasKey('title', $labels);
        $this->assertArrayHasKey('description', $labels);
        $this->assertArrayHasKey('status', $labels);
        $this->assertArrayHasKey('priority', $labels);
        $this->assertArrayHasKey('due_date', $labels);
    }

    public function testTaskToArray()
    {
        $task = new Task([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => '2024-12-31'
        ]);
        $task->save();

        $array = $task->toArray();
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('priority', $array);
        $this->assertArrayHasKey('due_date', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }
}