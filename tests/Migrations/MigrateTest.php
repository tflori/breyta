<?php

namespace Breyta\Test\Migrations;

use Breyta\Model\Migration;
use Breyta\Test\TestCase;

class MigrateTest extends TestCase
{
    /** @test */
    public function returnsSuccessWhenNoMigrationsNeedToBeExecuted()
    {
        $this->mockStatus(Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'done'
        ]));
        $this->migrations->shouldReceive('up')->with()->once()->andReturn(true);

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesNewMigrations()
    {
        $this->mockStatus($migration = Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'new',
        ]));
        $this->migrations->shouldReceive('up')->with($migration)->once()->andReturn(true);

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesFailedMigrations()
    {
        $this->mockStatus($migration = Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'failed',
            'executed' => date('c', strtotime('-1 hour')),
        ]));
        $this->migrations->shouldReceive('up')->with($migration)->once()->andReturn(true);

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesRevertedMigrations()
    {
        $this->mockStatus($migration = Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'reverted',
            'executed' => date('c', strtotime('-1 hour')),
            'reverted' => date('c', strtotime('-10 minutes')),
        ]));
        $this->migrations->shouldReceive('up')->with($migration)->once()->andReturn(true);

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }
}
