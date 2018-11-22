<?php

require_once __DIR__ . '/vendor/autoload.php';

// connect to your database...
$dsn = 'sqlite:/tmp/breyta_test.sq3';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];
$db = new PDO($dsn, '', '', $options);

$adapter = new Breyta\Adapter\BasicAdapter(function ($statement) use ($db) {
    var_dump($statement);
    return $db->exec($statement);
});

$migration = new Breyta\Migration\CreateMigrationTable($adapter);
$migration->up();
