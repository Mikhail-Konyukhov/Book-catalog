<?php

declare(strict_types=1);

// Окружение задаётся переменной YII_ENV; docker-compose ставит dev.
// Без переменной — prod: отладчик и Gii не поднимаются, стектрейсы наружу не идут.
$env = getenv('YII_ENV') ?: 'prod';
defined('YII_ENV') or define('YII_ENV', $env);
defined('YII_DEBUG') or define('YII_DEBUG', $env === 'dev');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
