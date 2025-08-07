<?php

namespace tests;

use Yii;
use PHPUnit\Framework\TestCase as BaseTestCase;
use yii\web\Application;

/**
 * Base test case for PHPUnit tests
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Clean up after test.
     * By default the application created in setUp() will be destroyed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
    }

    /**
     * Mocks web application
     *
     * @param array $config
     * @param string $appClass
     */
    protected function mockApplication($config = [], $appClass = Application::class)
    {
        new $appClass(array_merge([
            'id' => 'testapp',
            'basePath' => dirname(__DIR__),
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'runtimePath' => dirname(__DIR__) . '/runtime',
            'components' => [
                'db' => require dirname(__DIR__) . '/config/test_db.php',
                'request' => [
                    'class' => 'yii\web\Request',
                    'cookieValidationKey' => 'test',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
                'urlManager' => [
                    'enablePrettyUrl' => true,
                    'showScriptName' => false,
                    'rules' => [
                        'GET tasks' => 'task/index',
                        'GET tasks/<id:\d+>' => 'task/view',
                        'POST tasks' => 'task/create',
                        'PUT tasks/<id:\d+>' => 'task/update',
                        'DELETE tasks/<id:\d+>' => 'task/delete',
                        'PATCH tasks/<id:\d+>/toggle-status' => 'task/toggle-status',
                        'GET tasks/trash' => 'task/trash',
                        'PATCH tasks/<id:\d+>/restore' => 'task/restore',
                    ],
                ],
            ],
        ], $config));
    }

    /**
     * Clean database tables
     */
    protected function cleanDatabase()
    {
        $db = Yii::$app->db;
        $db->createCommand('DELETE FROM {{%task_tag}}')->execute();
        $db->createCommand('DELETE FROM {{%tags}}')->execute();
        $db->createCommand('DELETE FROM {{%tasks}}')->execute();
    }

    /**
     * Run database migrations
     */
    protected function runMigrations()
    {
        $migrationCommand = Yii::createObject([
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => '@app/migrations',
            'interactive' => false,
        ]);

        ob_start();
        $migrationCommand->actionUp();
        ob_end_clean();
    }
}