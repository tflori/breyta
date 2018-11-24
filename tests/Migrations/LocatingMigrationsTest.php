<?php

namespace Breyta\Test\Migrations;

use Breyta\Migrations;
use Breyta\Model\Migration;
use Breyta\Test\TestCase;
use Mockery as m;

class LocatingMigrationsTest extends TestCase
{
    /** @test */
    public function returnsAStatusObject()
    {
        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $status = $migrations->getStatus();

        self::assertObjectHasAttribute('migrations', $status);
        self::assertObjectHasAttribute('count', $status);
    }

    /** @test */
    public function findsMigrationsInTheGivenPath()
    {
        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $status = $migrations->getStatus();

        self::assertContains(Migration::createInstance([
            'file' => 'CreateAnimalsTable.php',
            'status' => 'new',
        ]), $status->migrations, '', false, false);
    }

    /** @test */
    public function findsMigrationsInSubFolders()
    {
        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $status = $migrations->getStatus();

        self::assertContains(Migration::createInstance([
            'file' => 'Grouped/2018-11-22T22-59-59Z_FooBar.php',
            'status' => 'new',
        ]), $status->migrations, '', false, false);
    }

    /** @test */
    public function filtersFilesWithoutClasses()
    {
        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $status = $migrations->getStatus();

        self::assertNotContains((object)[
            'file' => 'EmptyFile.php',
            'status' => 'new',
        ], $status->migrations, '', false, false);
    }

    /** @test */
    public function filtersClassesThatNotExtendAbstractMigration()
    {
        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $status = $migrations->getStatus();

        self::assertNotContains((object)[
            'file' => 'NoMigration.php',
            'status' => 'new',
        ], $status->migrations, '', false, false);
    }

    /** @test */
    public function ordersMigrations()
    {
        $expectedOrder = [
            '@breyta/CreateMigrationTable.php', // always first
            'Grouped/Anomaly.php', // migrations without timestamps first in alphabetical order
            'CreateAnimalsTable.php', // sub folders do not change the order
            'Grouped/FamilyTable.php',
            'Grouped/2018-11-22T22-59-59Z_FooBar.php', // then by timestamp
            '2018-11-22T23-15-31Z_FooBaz.php',
            'Grouped/2018-11-22T23-59-50Z_Bar.php', // in alphabetical order when equal
            'Grouped/2018-11-22T23-59-50Z_Foo.php',
        ];
        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $status = $migrations->getStatus();

        // pluck file names
        $migrationFiles = array_map(function ($migration) {
            return $migration->file;
        }, $status->migrations);
        // filter other migrations that might get added to this folder
        $migrationFiles = array_filter($migrationFiles, function ($file) use ($expectedOrder) {
            return in_array($file, $expectedOrder);
        });

        self::assertSame($expectedOrder, array_values($migrationFiles));
    }

    /** @test */
    public function throwsWhenTheFolderDoesNotExist()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('The path to migrations is not valid');

        $migrations = new Migrations($this->pdo, '/any/non-existing/path');
    }

    /** @test */
    public function throwsWhenTheGivenPathIsAFile()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('The path to migrations is not valid');

        $migrations = new Migrations($this->pdo, __FILE__);
    }

    /** @test */
    public function throwsWhenTheGivenPathIsASymLinkToAFile()
    {
        if (file_exists('/tmp/symlink')) {
            unlink('/tmp/symlink');
        }

        if (!@symlink(__FILE__, '/tmp/symlink')) {
            $this->markTestSkipped('Could not create a symlink');
            return;
        }

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('The path to migrations is not valid');

        $migrations = new Migrations($this->pdo, '/tmp/symlink');
    }

    /** @test */
    public function allowsSymLinksToADirectory()
    {
        if (file_exists('/tmp/symlink')) {
            unlink('/tmp/symlink');
        }

        if (!@symlink(__DIR__, '/tmp/symlink')) {
            $this->markTestSkipped('Could not create a symlink');
            return;
        }

        $migrations = new Migrations($this->pdo, '/tmp/symlink');

        self::assertInstanceOf(Migrations::class, $migrations);
    }
}
