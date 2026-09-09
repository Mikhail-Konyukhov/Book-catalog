<?php

$host = getenv('DB_HOST') ?: 'db';
$name = getenv('DB_NAME') ?: 'infotech';

return [
    'class' => \yii\db\Connection::class,
    'dsn' => "mysql:host=$host;dbname=$name",
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: 'secret',
    'charset' => 'utf8mb4',
];
