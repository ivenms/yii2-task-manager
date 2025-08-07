<?php

// Define constants
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

// Include the Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Include Yii class file
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Include test configuration
$config = require __DIR__ . '/../config/test.php';

// Create the application instance
(new yii\web\Application($config));