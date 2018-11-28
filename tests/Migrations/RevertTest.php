<?php

namespace Breyta\Test\Migrations;

use Breyta\Model\Migration;
use Breyta\Test\TestCase;

class RevertTest extends TestCase
{
    /** @test */
    public function returnsSuccessWhenNoMigrationsNeedToBeReverted()
    {
        $this->mockStatus(Migration::createInstance([
            'file' => 'CreateAnimalsTable.php',
            'status' => 'new'
        ]));
        $this->migrations->shouldReceive('down')->with()->once()->andReturn(true);

        $result = $this->migrations->revert();

        self::assertTrue($result);
    }

    /** @test */
    public function revertsDoneMigrations()
    {
        $this->mockStatus($migration = Migration::createInstance([
            'file' => 'CreateAnimalsTable.php',
            'status' => 'done',
        ]));
        $this->migrations->shouldReceive('down')->with($migration)->once()->andReturn(true);

        $result = $this->migrations->revert();

        self::assertTrue($result);
    }

    /** @test */
    public function doesNotRevertInternalMigrations()
    {
        $this->mockStatus(Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'done'
        ]));
        $this->migrations->shouldReceive('down')->with()->once()->andReturn(true);

        $result = $this->migrations->revert();

        self::assertTrue($result);
    }

    /** @test */
    public function doesNotRevertFailedMigrations()
    {
        $this->mockStatus(Migration::createInstance([
            'file' => 'CreateAnimalsTable.php',
            'status' => 'failed'
        ]));
        $this->migrations->shouldReceive('down')->with()->once()->andReturn(true);

        $result = $this->migrations->revert();

        self::assertTrue($result);
    }

    /** @test */
    public function doesNotRevertRevertedMigrations()
    {
        $this->mockStatus(Migration::createInstance([
            'file' => 'CreateAnimalsTable.php',
            'status' => 'reverted'
        ]));
        $this->migrations->shouldReceive('down')->with()->once()->andReturn(true);

        $result = $this->migrations->revert();

        self::assertTrue($result);
    }
}
