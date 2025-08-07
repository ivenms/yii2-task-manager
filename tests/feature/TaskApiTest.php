<?php

namespace tests\feature;

use Yii;
use tests\TestCase;
use app\models\Task;
use app\models\Tag;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TaskApiTest extends TestCase
{
    private $client;
    private $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
        $this->cleanDatabase();
        
        // Set up HTTP client for API testing
        // When running in container, use the test entry point directly
        $this->baseUrl = 'http://127.0.0.1:80/index-test.php';
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'http_errors' => false,
        ]);
    }

    public function testGetTasksEmpty()
    {
        $response = $this->client->get('/tasks');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals([], $data['data']);
        $this->assertEquals(0, $data['pagination']['total']);
    }

    public function testCreateTask()
    {
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test task description',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => '2024-12-31',
            'tags' => ['work', 'urgent']
        ];

        $response = $this->client->post('/tasks', [
            'json' => $taskData
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals('Task created successfully', $data['message']);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('Test Task', $data['data']['title']);
        $this->assertEquals('Test task description', $data['data']['description']);
        $this->assertEquals('pending', $data['data']['status']);
        $this->assertEquals('medium', $data['data']['priority']);
        $this->assertCount(2, $data['data']['tags']);
    }

    public function testCreateTaskValidationFail()
    {
        $taskData = [
            'description' => 'Task without title'
        ];

        $response = $this->client->post('/tasks', [
            'json' => $taskData
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $data['status']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testGetTasks()
    {
        // Create test tasks directly in database
        $task1 = new Task([
            'title' => 'First Task',
            'description' => 'First description',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => '2024-12-31'
        ]);
        $task1->save();

        $task2 = new Task([
            'title' => 'Second Task',
            'description' => 'Second description',
            'status' => 'completed',
            'priority' => 'low',
            'due_date' => '2024-11-30'
        ]);
        $task2->save();

        $response = $this->client->get('/tasks');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals(2, $data['pagination']['total']);
        $this->assertCount(2, $data['data']);
    }

    public function testGetTaskWithFilters()
    {
        $task1 = new Task([
            'title' => 'Pending Task',
            'status' => 'pending',
            'priority' => 'high'
        ]);
        $task1->save();

        $task2 = new Task([
            'title' => 'Completed Task',
            'status' => 'completed',
            'priority' => 'low'
        ]);
        $task2->save();

        // Test status filter
        $response = $this->client->get('/tasks?status=pending');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(1, $data['pagination']['total']);

        // Test priority filter
        $response = $this->client->get('/tasks?priority=high');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(1, $data['pagination']['total']);

        // Test search filter
        $response = $this->client->get('/tasks?search=Pending');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(1, $data['pagination']['total']);
    }

    public function testViewTask()
    {
        $task = new Task([
            'title' => 'View Task',
            'description' => 'Task to view',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => '2024-12-31'
        ]);
        $task->save();

        $response = $this->client->get("/tasks/{$task->id}");
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals($task->id, $data['data']['id']);
        $this->assertEquals('View Task', $data['data']['title']);
        $this->assertEquals('Task to view', $data['data']['description']);
    }

    public function testViewTaskNotFound()
    {
        $response = $this->client->get('/tasks/999999');
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testUpdateTask()
    {
        $task = new Task([
            'title' => 'Original Title',
            'description' => 'Original description',
            'status' => 'pending',
            'priority' => 'low'
        ]);
        $task->save();

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'priority' => 'high',
            'tags' => ['updated', 'important']
        ];

        $response = $this->client->put("/tasks/{$task->id}", [
            'json' => $updateData
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals('Task updated successfully', $data['message']);
        $this->assertEquals('Updated Title', $data['data']['title']);
        $this->assertEquals('high', $data['data']['priority']);
    }

    public function testDeleteTask()
    {
        $task = new Task([
            'title' => 'Task to Delete',
            'status' => 'pending'
        ]);
        $task->save();

        $response = $this->client->delete("/tasks/{$task->id}");
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals('Task deleted successfully', $data['message']);
    }

    public function testToggleTaskStatus()
    {
        $task = new Task([
            'title' => 'Status Toggle Task',
            'status' => 'pending'
        ]);
        $task->save();

        // Toggle from pending to in_progress
        $response = $this->client->patch("/tasks/{$task->id}/toggle-status");
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('in_progress', $data['data']['status']);

        // Toggle from in_progress to completed
        $response = $this->client->patch("/tasks/{$task->id}/toggle-status");
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('completed', $data['data']['status']);

        // Toggle from completed to pending
        $response = $this->client->patch("/tasks/{$task->id}/toggle-status");
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('pending', $data['data']['status']);
    }

    public function testGetTrashTasks()
    {
        // Create a regular task
        $regularTask = new Task([
            'title' => 'Regular Task',
            'status' => 'pending'
        ]);
        $regularTask->save();

        // Create a deleted task
        $deletedTask = new Task([
            'title' => 'Deleted Task',
            'status' => 'pending'
        ]);
        $deletedTask->save();
        $deletedTask->softDelete();

        $response = $this->client->get('/tasks/trash');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals(1, $data['pagination']['total']);
        $this->assertArrayHasKey('deleted_at', $data['data'][0]);
    }

    public function testRestoreTask()
    {
        $task = new Task([
            'title' => 'Task to Restore',
            'status' => 'pending'
        ]);
        $task->save();
        $task->softDelete();

        $response = $this->client->patch("/tasks/{$task->id}/restore");
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals('Task restored successfully', $data['message']);
    }

    public function testTaskPagination()
    {
        // Create multiple tasks
        for ($i = 1; $i <= 15; $i++) {
            $task = new Task([
                'title' => "Task $i",
                'status' => 'pending'
            ]);
            $task->save();
        }

        // Test first page
        $response = $this->client->get('/tasks?page=0');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(15, $data['pagination']['total']);
        $this->assertEquals(0, $data['pagination']['page']);
        $this->assertEquals(2, $data['pagination']['totalPages']);

        // Test second page
        $response = $this->client->get('/tasks?page=1');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(1, $data['pagination']['page']);
    }

    public function testInvalidHttpMethods()
    {
        // Test invalid method for list endpoint
        try {
            $response = $this->client->post('/tasks/index');
            $this->assertEquals(405, $response->getStatusCode());
        } catch (RequestException $e) {
            $this->assertEquals(405, $e->getResponse()->getStatusCode());
        }
    }
}