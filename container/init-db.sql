-- Create main database
CREATE DATABASE IF NOT EXISTS task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create test database  
CREATE DATABASE IF NOT EXISTS task_manager_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user if not exists and grant privileges
CREATE USER IF NOT EXISTS 'yii2_user'@'%' IDENTIFIED BY 'yii2_pass';
GRANT ALL PRIVILEGES ON `task_manager`.* TO 'yii2_user'@'%';
GRANT ALL PRIVILEGES ON `task_manager_test`.* TO 'yii2_user'@'%';

FLUSH PRIVILEGES;