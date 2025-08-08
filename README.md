# Task Manager - CRUD Assignment

A RESTful API-based task manager built with Yii2 framework, featuring comprehensive task management capabilities with filtering, sorting, pagination, and a simple frontend interface.

## Features

### Core Features
- Full CRUD operations for tasks
- RESTful JSON API endpoints
- Task filtering by status, priority, due date, and title search
- Pagination and sorting capabilities
- Data validation using Yii2 model rules
- Proper HTTP status codes (200, 201, 400, 404, 422)
- Simple HTML + Bootstrap + JavaScript frontend

### Bonus Features Implemented
- **Soft Delete Support** - Tasks are soft-deleted with restore capability
- **Tag System** - Many-to-many relationship between tasks and tags
- **Status Toggle Endpoint** - Cycle through task statuses
- **Advanced Filtering** - Filter by tags and date ranges

## Project Structure

```
task-manager/
├── controllers/
│   └── TaskController.php          # RESTful API controller
├── models/
│   ├── Task.php                   # Task model with validation rules
│   └── Tag.php                    # Tag model for tagging system
├── migrations/
│   ├── m250807_152509_create_tasks_table.php    # Tasks table migration
│   ├── m250807_161308_create_tags_table.php     # Tags table migration
│   └── m250807_161322_create_task_tag_table.php # Junction table migration
├── tests/
│   ├── unit/                      # Unit tests for models
│   ├── feature/                   # API integration tests
│   └── TestCase.php               # Base test class
├── web/
│   ├── index.html                 # Frontend interface
│   ├── trash.html                 # Trash/deleted tasks interface
│   └── js/                       # JavaScript files
├── config/
│   ├── db.php                     # Database configuration
│   ├── test_db.php               # Test database configuration
│   └── web.php                    # Application configuration
├── container/
│   ├── Dockerfile                 # Container configuration
│   └── yii2-vhost.conf           # Apache virtual host
├── Makefile                       # Build and deployment commands
├── composer.json                  # PHP dependencies
├── phpunit.xml                    # PHPUnit configuration
├── .env.example                   # Sample environment configuration
└── README.md
```

## Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/ivenms/yii2-task-manager
cd yii2-task-manager
```

### 2. Environment Configuration
Copy the sample environment file and configure your settings:

```bash
cp .env.example .env
```

Edit the `.env` file with your configuration:

```env
# Database Configuration
DB_HOST=your_db_host
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_username
DB_PASSWORD=your_password
DB_ROOT_PASSWORD=your_root_password

# Application Configuration
YII_ENV=dev
YII_DEBUG=true

# Container Configuration
APP_PORT=8080
MYSQL_PORT=3306
REDIS_PORT=6379
```

### 3. Database Setup
The database will be automatically created when you start the local environment. You can customize the database name by updating the `DB_NAME` variable in your `.env` file.

### 4. Run Migrations
```bash
make migrate
```

This will create the following tables:
- `tasks` - Main tasks table with soft delete support
- `tags` - Tags for categorizing tasks
- `task_tag` - Junction table for many-to-many relationship

### 5. Start the Application
```bash
make local
```

### 6. Access the Application
- **Frontend Interface**: `http://localhost:8080/index.html`
- **API Base URL**: `http://localhost:8080/tasks`

## API Documentation

### Base URL
```
http://localhost:8080/tasks
```

### Endpoints

#### 1. List All Tasks
**GET** `/tasks`

**Query Parameters:**
- `status` - Filter by status (pending, in_progress, completed)
- `priority` - Filter by priority (low, medium, high)
- `due_date_from` - Filter tasks due from date (YYYY-MM-DD)
- `due_date_to` - Filter tasks due until date (YYYY-MM-DD)
- `search` - Search in task title
- `tag` - Filter by tag name
- `sort` - Sort by field (created_at, due_date, priority, status)
- `order` - Sort order (asc, desc)
- `limit` - Results per page (default: 20)
- `page` - Page number (default: 0)

**Example:**
```bash
GET /tasks?status=pending&priority=high&limit=10&page=0
```

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "title": "Complete project documentation",
            "description": "Write comprehensive README and API docs",
            "status": "pending",
            "priority": "high",
            "due_date": "2025-08-15",
            "created_at": "2025-08-07 10:30:00",
            "updated_at": "2025-08-07 10:30:00",
            "tags": [
                {"id": 1, "name": "documentation"},
                {"id": 2, "name": "urgent"}
            ]
        }
    ],
    "pagination": {
        "total": 25,
        "page": 0,
        "pageSize": 10,
        "totalPages": 3
    }
}
```

#### 2. Get Task by ID
**GET** `/tasks/{id}`

**Example:**
```bash
GET /tasks/1
```

#### 3. Create New Task
**POST** `/tasks`

**Request Body:**
```json
{
    "title": "New task title",
    "description": "Task description",
    "status": "pending",
    "priority": "medium",
    "due_date": "2025-08-20"
}
```

**Response:** `201 Created`

#### 4. Update Task
**PUT** `/tasks/{id}`

**Request Body:**
```json
{
    "title": "Updated task title",
    "status": "in_progress",
    "priority": "high"
}
```

**Response:** `200 OK`

#### 5. Delete Task (Soft Delete)
**DELETE** `/tasks/{id}`

**Response:** `200 OK`

#### 6. Toggle Task Status
**PATCH** `/tasks/{id}/toggle-status`

Cycles through: pending → in_progress → completed → pending

**Response:** `200 OK`

#### 7. Restore Deleted Task
**PATCH** `/tasks/{id}/restore`

**Response:** `200 OK`

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `404` - Not Found
- `422` - Validation Error

### Error Response Format
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "title": ["Title cannot be blank"]
    }
}
```

## Frontend Usage

### Access the Frontend
Open `http://localhost:8080/index.html` in your browser.

### Features Available:
1. **Create Tasks** - Fill out the form with task details
2. **View Tasks** - See all tasks in a responsive card layout
3. **Filter Tasks** - Use dropdowns and search to filter tasks
4. **Delete Tasks** - Click delete button (with confirmation)
5. **Toggle Status** - Use the dropdown menu to cycle through statuses
6. **Real-time Updates** - Frontend automatically refreshes after operations

## Testing the API

### Using cURL

#### Create a Task:
```bash
curl -X POST http://localhost:8080/tasks \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test task from cURL",
    "description": "This is a test task",
    "priority": "high",
    "due_date": "2025-08-20"
  }'
```

#### List Tasks:
```bash
curl -X GET "http://localhost:8080/tasks?status=pending&limit=5"
```

#### Update a Task:
```bash
curl -X PUT http://localhost:8080/tasks/1 \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated task title",
    "status": "completed"
  }'
```

#### Delete a Task:
```bash
curl -X DELETE http://localhost:8080/tasks/1
```

### Using Postman

Import these requests into Postman:

1. **GET Tasks Collection**
   - URL: `http://localhost:8080/tasks`
   - Method: GET
   - Params: Add query parameters as needed

2. **POST Create Task**
   - URL: `http://localhost:8080/tasks`
   - Method: POST
   - Headers: `Content-Type: application/json`
   - Body (raw JSON): Task data

3. **PUT Update Task**
   - URL: `http://localhost:8080/tasks/{id}`
   - Method: PUT
   - Headers: `Content-Type: application/json`
   - Body (raw JSON): Updated task data

## Database Schema

### Tasks Table
```sql
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `priority` varchar(20) NOT NULL DEFAULT 'medium',
  `due_date` date,
  `deleted_at` timestamp NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_status` (`status`),
  KEY `idx_tasks_priority` (`priority`),
  KEY `idx_tasks_due_date` (`due_date`),
  KEY `idx_tasks_deleted_at` (`deleted_at`)
);
```

### Tags Table
```sql
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL UNIQUE,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
```

### Task-Tag Junction Table
```sql
CREATE TABLE `task_tag` (
  `task_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`, `tag_id`),
  FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
);
```

## Development Notes

### Task Model Validation Rules
- `title`: Required, minimum 5 characters
- `status`: Must be one of: pending, in_progress, completed
- `priority`: Must be one of: low, medium, high
- `due_date`: Must be valid date format (YYYY-MM-DD)

### Soft Delete Implementation
- Tasks are not permanently deleted from the database
- `deleted_at` timestamp is set when deleting
- Default queries exclude soft-deleted records
- Restore endpoint available to recover deleted tasks

### Tag System
- Many-to-many relationship between tasks and tags
- Tags can be reused across multiple tasks
- Filter tasks by tag name in API

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check `config/db.php` credentials
   - Ensure database exists
   - Verify MySQL service is running

2. **Migration Errors**
   - Ensure database user has CREATE/ALTER privileges
   - Check if tables already exist

3. **404 Errors on API Calls**
   - Verify URL rewriting is enabled
   - Check `.htaccess` file in `/web` directory

4. **CORS Issues (if accessing from different domain)**
   - Add CORS headers in `TaskController.php` if needed

### Logs
Check application logs in:
- `runtime/logs/app.log`

## License

This project is created for educational purposes as part of a CRUD assignment.

## Testing

This project uses PHPUnit for testing.

### Setup

1. Make sure your test database is configured in `config/test_db.php`

2. Run migrations for the test database:
```bash
make migrate
```

### Running Tests

Run all tests:
```bash
composer test
# or
./vendor/bin/phpunit
```

Run unit tests only:
```bash
composer test-unit
# or
./vendor/bin/phpunit --testsuite="Unit Tests"
```

Run feature tests only:
```bash
composer test-feature
# or
./vendor/bin/phpunit --testsuite="Feature Tests"
```

Run with coverage:
```bash
./vendor/bin/phpunit --coverage-html tests/_output/coverage
```

### Test Structure

- `tests/unit/` - Unit tests for models and components
- `tests/feature/` - Feature/integration tests for API endpoints
- `tests/TestCase.php` - Base test class with common functionality
- `tests/bootstrap.php` - Bootstrap file for PHPUnit
- `phpunit.xml` - PHPUnit configuration

**Note:** Feature tests make HTTP requests to test API endpoints. Ensure your local development server is running on `http://localhost:8080` when running these tests, or update the `$baseUrl` in the test files to match your setup.

## Author

[@ivenms](https://github.com/ivenms)