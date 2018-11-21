<?php

require_once __DIR__ . '/vendor/autoload.php';

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $dotenv = new Symfony\Component\Dotenv\Dotenv();
    $dotenv->load($envFile);
}

// connect to your database...
$dsn = 'mysql:host=127.0.0.1;dbname=testdb;port=3307';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'password';
$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
);

$db = new PDO($dsn, $username, $password, $options);

$adapter = new Breyta\Adapter\BasicAdapter(function ($statement) use ($db) {
    var_dump($statement);
    return $db->exec($statement);
});

$migration = new Breyta\Migration\CreateMigrationTable($adapter);
$migration->up();

