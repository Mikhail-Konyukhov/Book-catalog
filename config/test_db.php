<?php

$db = require __DIR__ . '/db.php';

$host = getenv('DB_HOST') ?: 'db';
$name = getenv('DB_NAME_TEST') ?: 'infotech_test';
// test database! Important not to run tests on production or development databases
$db['dsn'] = "mysql:host=$host;dbname=$name";

return $db;
