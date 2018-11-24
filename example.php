<?php

use Breyta\Migrations;
use Breyta\Model\Migration;

require_once __DIR__ . '/vendor/autoload.php';

// connect to your database...
$dsn = 'sqlite:/tmp/breyta_test.sq3';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];
$db = new PDO($dsn, '', '', $options);

$migrations = new Migrations($db, __DIR__ . '/migrations');

//$status = $migrations->getStatus();
///** @var Migration $migration */
//foreach ($status->migrations as $migration) {
//    echo $migration->file;
//    switch ($migration->status) {
//        case 'done':
//            echo "\e[32m";
//            break;
//        case 'failed':
//            echo "\e[31m";
//            break;
//        case 'new':
//            echo "\e[33m";
//            break;
//    }
//    echo ' ' . $migration->status . "\e[0m" . PHP_EOL;
//}

if (!$migrations->migrate()) {
    $status = $migrations->getStatus();
    $failed = array_filter($status->migrations, function (Migration $migration) {
        return $migration->status === 'failed';
    });
    /** @var Migration $migration */
    foreach ($failed as $migration) {
        foreach ($migration->statements as $statement) {
            echo $statement->teaser;
            if ($statement->exception) {
                echo ' (' . $statement->exception->getMessage() . ')';
            }
            echo PHP_EOL;
        }
    }
}
