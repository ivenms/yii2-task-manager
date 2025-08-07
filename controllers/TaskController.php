<?php

namespace app\controllers;

use Yii;
use app\models\Task;
use app\models\Tag;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\data\ActiveDataProvider;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;

/**
 * TaskController implements the RESTful API for Task model.
 */
class TaskController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'verbFilter' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET'],
                    'view' => ['GET'],
                    'create' => ['POST'],
                    'update' => ['PUT'],
                    'delete' => ['DELETE'],
                    'toggle-status' => ['PATCH'],
                    'restore' => ['PATCH'],
                    'trash' => ['GET'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * Lists all Task models with filtering, sorting and pagination.
     * GET /tasks
     */
    public function actionIndex()
    {
        $query = Task::find();

        // Filtering
        $status = Yii::$app->request->get('status');
        $priority = Yii::$app->request->get('priority');
        $dueDateFrom = Yii::$app->request->get('due_date_from');
        $dueDateTo = Yii::$app->request->get('due_date_to');
        $search = Yii::$app->request->get('search');
        $tag = Yii::$app->request->get('tag');

        if ($status) {
            $query->andWhere(['status' => $status]);
        }

        if ($priority) {
            $query->andWhere(['priority' => $priority]);
        }

        if ($dueDateFrom && $dueDateTo) {
            $query->andWhere(['between', 'due_date', $dueDateFrom, $dueDateTo]);
        } elseif ($dueDateFrom) {
            $query->andWhere(['>=', 'due_date', $dueDateFrom]);
        } elseif ($dueDateTo) {
            $query->andWhere(['<=', 'due_date', $dueDateTo]);
        }

        if ($search) {
            $query->andWhere(['like', 'title', $search]);
        }

        if ($tag) {
            $query->joinWith('tags')->andWhere(['{{%tags}}.name' => $tag]);
        }

        // Sorting
        $sortBy = Yii::$app->request->get('sort', 'created_at');
        $sortOrder = Yii::$app->request->get('order', 'desc');
        
        $allowedSortFields = ['created_at', 'due_date', 'priority', 'status'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy([$sortBy => $sortOrder === 'asc' ? SORT_ASC : SORT_DESC]);
        }

        // Pagination
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => Yii::$app->params['taskPageSize'] ?? 10,
                'page' => max(0, (int) Yii::$app->request->get('page', 0)),
            ],
        ]);

        $tasks = [];
        foreach ($dataProvider->getModels() as $task) {
            $tasks[] = $this->formatTaskResponse($task);
        }

        return [
            'status' => 'success',
            'data' => $tasks,
            'pagination' => [
                'total' => $dataProvider->getTotalCount(),
                'page' => $dataProvider->pagination->page,
                'pageSize' => $dataProvider->pagination->pageSize,
                'totalPages' => $dataProvider->pagination->getPageCount(),
            ]
        ];
    }

    /**
     * Displays a single Task model.
     * GET /tasks/{id}
     */
    public function actionView($id)
    {
        $task = $this->findModel($id);
        
        return [
            'status' => 'success',
            'data' => $this->formatTaskResponse($task)
        ];
    }

    /**
     * Creates a new Task model.
     * POST /tasks
     */
    public function actionCreate()
    {
        $task = new Task();
        
        $data = Yii::$app->request->getBodyParams();
        $tags = isset($data['tags']) ? $data['tags'] : [];
        unset($data['tags']); // Remove tags from data array
        
        $task->load($data, '');

        if ($task->save()) {
            // Handle tags
            $this->handleTags($task, $tags);
            
            Yii::$app->response->setStatusCode(201);
            return [
                'status' => 'success',
                'message' => 'Task created successfully',
                'data' => $this->formatTaskResponse($task)
            ];
        }

        Yii::$app->response->setStatusCode(422);
        return [
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $task->getErrors()
        ];
    }

    /**
     * Updates an existing Task model.
     * PUT /tasks/{id}
     */
    public function actionUpdate($id)
    {
        $task = $this->findModel($id);
        
        $data = Yii::$app->request->getBodyParams();
        $tags = isset($data['tags']) ? $data['tags'] : null;
        unset($data['tags']); // Remove tags from data array
        
        $task->load($data, '');

        if ($task->save()) {
            // Handle tags if provided
            if ($tags !== null) {
                $this->handleTags($task, $tags);
            }
            
            return [
                'status' => 'success',
                'message' => 'Task updated successfully',
                'data' => $this->formatTaskResponse($task)
            ];
        }

        Yii::$app->response->setStatusCode(422);
        return [
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $task->getErrors()
        ];
    }

    /**
     * Deletes an existing Task model (soft delete).
     * DELETE /tasks/{id}
     */
    public function actionDelete($id)
    {
        $task = $this->findModel($id);
        
        if ($task->softDelete()) {
            return [
                'status' => 'success',
                'message' => 'Task deleted successfully'
            ];
        }

        Yii::$app->response->setStatusCode(400);
        return [
            'status' => 'error',
            'message' => 'Failed to delete task'
        ];
    }

    /**
     * Toggles task status through the cycle: pending -> in_progress -> completed -> pending
     * PATCH /tasks/{id}/toggle-status
     */
    public function actionToggleStatus($id)
    {
        $task = $this->findModel($id);
        
        switch ($task->status) {
            case Task::STATUS_PENDING:
                $task->status = Task::STATUS_IN_PROGRESS;
                break;
            case Task::STATUS_IN_PROGRESS:
                $task->status = Task::STATUS_COMPLETED;
                break;
            case Task::STATUS_COMPLETED:
                $task->status = Task::STATUS_PENDING;
                break;
        }

        if ($task->save()) {
            return [
                'status' => 'success',
                'message' => 'Task status updated successfully',
                'data' => $this->formatTaskResponse($task)
            ];
        }

        Yii::$app->response->setStatusCode(400);
        return [
            'status' => 'error',
            'message' => 'Failed to update task status',
            'errors' => $task->getErrors()
        ];
    }

    /**
     * Lists all deleted Task models with pagination.
     * GET /tasks/trash
     */
    public function actionTrash()
    {
        $query = Task::findWithDeleted()->andWhere(['is not', 'deleted_at', null]);

        // Pagination
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => Yii::$app->params['taskPageSize'] ?? 10,
                'page' => max(0, (int) Yii::$app->request->get('page', 0)),
            ],
        ]);

        $tasks = [];
        foreach ($dataProvider->getModels() as $task) {
            $taskData = $this->formatTaskResponse($task);
            $taskData['deleted_at'] = $task->deleted_at;
            $tasks[] = $taskData;
        }

        return [
            'status' => 'success',
            'data' => $tasks,
            'pagination' => [
                'total' => $dataProvider->getTotalCount(),
                'page' => $dataProvider->pagination->page,
                'pageSize' => $dataProvider->pagination->pageSize,
                'totalPages' => $dataProvider->pagination->getPageCount(),
            ]
        ];
    }

    /**
     * Restores a soft-deleted task.
     * PATCH /tasks/{id}/restore
     */
    public function actionRestore($id)
    {
        $task = Task::findWithDeleted()->andWhere(['id' => $id])->andWhere(['is not', 'deleted_at', null])->one();
        
        if (!$task) {
            throw new NotFoundHttpException('Task not found or not deleted');
        }

        if ($task->restore()) {
            return [
                'status' => 'success',
                'message' => 'Task restored successfully',
                'data' => $this->formatTaskResponse($task)
            ];
        }

        Yii::$app->response->setStatusCode(400);
        return [
            'status' => 'error',
            'message' => 'Failed to restore task'
        ];
    }

    /**
     * Finds the Task model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Task the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Task::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Task not found');
    }

    /**
     * Formats task data for API response
     * @param Task $task
     * @return array
     */
    protected function formatTaskResponse($task)
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => $task->due_date,
            'created_at' => $task->created_at,
            'updated_at' => $task->updated_at,
            'tags' => array_map(function($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name
                ];
            }, $task->tags)
        ];
    }

    /**
     * Handle tags for a task
     * @param Task $task
     * @param array $tagNames
     */
    protected function handleTags($task, $tagNames)
    {
        if (!is_array($tagNames)) {
            return;
        }

        // Clear existing tags
        Yii::$app->db->createCommand()
            ->delete('{{%task_tag}}', ['task_id' => $task->id])
            ->execute();

        // Add new tags
        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) {
                continue;
            }

            // Find or create tag
            $tag = Tag::findOne(['name' => $tagName]);
            if (!$tag) {
                $tag = new Tag();
                $tag->name = $tagName;
                $tag->save();
            }

            // Link tag to task
            Yii::$app->db->createCommand()
                ->insert('{{%task_tag}}', [
                    'task_id' => $task->id,
                    'tag_id' => $tag->id
                ])
                ->execute();
        }
    }
}