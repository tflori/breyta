<?php

namespace Breyta\Concerns;

use Breyta\AbstractMigration;
use Breyta\FileHelper;
use Breyta\Migrations;
use Breyta\Model;

trait SearchesForMigrations
{
    protected static function sortMigrations($left, $right)
    {
        // sort criteria 1: is from breyta
        $leftIsFromBreyta = substr($left->file, 0, 8) === Migrations::INTERNAL_PREFIX;
        $rightIsFromBreyta = substr($right->file, 0, 8) === Migrations::INTERNAL_PREFIX;
        if ($leftIsFromBreyta !== $rightIsFromBreyta) {
            return $rightIsFromBreyta - $leftIsFromBreyta;
        }

        $leftBaseName = basename($left->file);
        $rightBaseName = basename($right->file);

        // sort criteria 2: has creation date
        $leftTime = FileHelper::getTimeFromFileName($left->file);
        $rightTime = FileHelper::getTimeFromFileName($right->file);
        if (is_null($leftTime) !== is_null($rightTime)) {
            return is_null($rightTime) - is_null($leftTime);
        }

        // sort criteria 3: by creation date
        if ($leftTime !== $rightTime) {
            return $leftTime - $rightTime;
        }

        // sort criteria 4: alphabetically
        return strcmp($leftBaseName, $rightBaseName);
    }

    protected function findMigrations(): array
    {
        $migrations = [Model\Migration::createInstance([
            'file' => Migrations::INTERNAL_PREFIX . 'CreateMigrationTable.php',
            'status' => 'new',
        ])];

        $classes = FileHelper::findClasses($this->path);
        $migrationClasses = array_filter($classes, function ($className, $file) {
            require_once $this->path . '/' . $file;
            return is_subclass_of($className, AbstractMigration::class);
        }, ARRAY_FILTER_USE_BOTH);

        $migrations = array_merge($migrations, array_map(function (string $file) {
            return Model\Migration::createInstance([
                'file' => $file,
                'status' => 'new'
            ]);
        }, array_keys($migrationClasses)));

        usort($migrations, [static::class, 'sortMigrations']);

        // key by identifier...
        return array_combine(array_map(function ($migration) {
            return $migration->file;
        }, $migrations), $migrations);
    }
}
