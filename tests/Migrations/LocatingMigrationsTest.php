<?php

namespace Breyta\Test\Migrations;

use Breyta\Migrations;
use Breyta\Test\TestCase;
use Mockery as m;

class LocatingMigrationsTest extends TestCase
{
    protected $pdo;

    protected function setUp()
    {
        parent::setUp();

        $this->pdo = m::mock(\PDO::class);
    }

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

        self::assertContains((object)[
            'file' => 'CreateAnimalsTable.php',
            'status' => 'new',
        ], $status->migrations, '', false, false);
    }

//    /** @test */
//    public function findsMigrationsInSubFolders()
//    {
//        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');
//
//        $status = $migrations->getStatus();
//
//        self::assertContains((object)[
//            'file' => 'Grouped/2018-11-22T22-59-59_FooBar.php',
//            'status' => 'new',
//        ], $status->migrations, '', false, false);
//    }

    /** @test */
    public function throwsWhenTheFolderDoesNotExist()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('The path to migrations is not valid');

        $migrations = new Migrations(m::mock(\PDO::class), '/any/non-existing/path');
    }

    /** @test */
    public function throwsWhenTheGivenPathIsAFile()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('The path to migrations is not valid');

        $migrations = new Migrations(m::mock(\PDO::class), __FILE__);
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

        $migrations = new Migrations(m::mock(\PDO::class), '/tmp/symlink');
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

        $migrations = new Migrations(m::mock(\PDO::class), '/tmp/symlink');

        self::assertInstanceOf(Migrations::class, $migrations);
    }
}
