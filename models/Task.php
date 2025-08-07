<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "{{%tasks}}".
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property string|null $due_date
 * @property string|null $deleted_at
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Tag[] $tags
 */
class Task extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%tasks}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['title'], 'string', 'min' => 5],
            [['description'], 'string'],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED]],
            [['priority'], 'in', 'range' => [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH]],
            [['due_date'], 'date', 'format' => 'yyyy-MM-dd'],
            [['status', 'priority'], 'string', 'max' => 20],
            [['title'], 'string', 'max' => 255],
            [['created_at', 'updated_at', 'deleted_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'description' => 'Description',
            'status' => 'Status',
            'priority' => 'Priority',
            'due_date' => 'Due Date',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Set default values
     */
    public function init()
    {
        parent::init();
        if ($this->isNewRecord) {
            $this->status = self::STATUS_PENDING;
            $this->priority = self::PRIORITY_MEDIUM;
        }
    }

    /**
     * Get available statuses
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    /**
     * Get available priorities
     */
    public static function getPriorityOptions()
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
        ];
    }

    /**
     * Override find() to exclude soft-deleted records by default
     */
    public static function find()
    {
        return parent::find()->andWhere(['deleted_at' => null]);
    }

    /**
     * Find all records including soft-deleted ones
     */
    public static function findWithDeleted()
    {
        return parent::find();
    }

    /**
     * Soft delete the task
     */
    public function softDelete()
    {
        $this->deleted_at = new Expression('NOW()');
        return $this->save(false);
    }

    /**
     * Restore soft-deleted task
     */
    public function restore()
    {
        $this->deleted_at = null;
        return $this->save(false);
    }

    /**
     * Gets query for associated tags.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])
            ->viaTable('{{%task_tag}}', ['task_id' => 'id']);
    }
}