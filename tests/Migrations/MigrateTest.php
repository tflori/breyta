<?php

namespace Breyta\Test\Migrations;

use Breyta\Model\Migration;
use Breyta\Test\TestCase;

class MigrateTest extends TestCase
{
    /** @test */
    public function returnsSuccessWhenNoMigrationsNeedToBeExecuted()
    {
        $this->setProtectedProperty($this->migrations, 'migrations', [Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'done'
        ])]);
        $this->migrations->shouldReceive('up')->with()->once()->andReturn(true);

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesNewMigrations()
    {
        $this->setProtectedProperty($this->migrations, 'migrations', [$migration = Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'new',
        ])]);
        $this->migrations->shouldReceive('up')->with($migration)->once()->andReturn(true);

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesFailedMigrations()
    {
        $this->setProtectedProperty($this->migrations, 'migrations', [$migration = Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'failed',
            'executed' => date('c', strtotime('-1 hour')),
        ])]);
        $this->migrations->shouldReceive('up')->with($migration)->once()->andReturn(true);

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesRevertedMigrations()
    {
        $this->setProtectedProperty($this->migrations, 'migrations', [$migration = Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'reverted',
            'executed' => date('c', strtotime('-1 hour')),
            'reverted' => date('c', strtotime('-10 minutes')),
        ])]);
        $this->migrations->shouldReceive('up')->with($migration)->once()->andReturn(true);

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function migratesToMatchingFile()
    {
        $migrations = [
            Migration::createInstance([
                'file' => 'FileA.php',
                'status' => 'new',
            ]),
            Migration::createInstance([
                'file' => 'FileB.php',
                'status' => 'new',
            ]),
            Migration::createInstance([
                'file' => 'FileC.php',
                'status' => 'new',
            ]),
        ];
        $this->setProtectedProperty($this->migrations, 'migrations', $migrations);

        $this->migrations->shouldReceive('up')->withArgs(array_slice($migrations, 0, 2))
            ->once()->andReturn(true);

        $result = $this->migrations->migrateTo('FileB');

        self::assertTrue($result);
    }

    /** @test */
    public function throwsWhenNoFileMatches()
    {
        $migrations = [
            Migration::createInstance([
                'file' => '@breyta/CreateMigrationTable.php',
                'status' => 'new',
            ]),
        ];
        $this->setProtectedProperty($this->migrations, 'migrations', $migrations);

        self::expectException(\LogicException::class);
        self::expectExceptionMessage('No migration found matching FileB');

        $this->migrations->migrateTo('FileB');
    }

    /** @test */
    public function filtersByFilesBeforeOrEqualTime()
    {
        $migrations = [
            Migration::createInstance([
                'file' => 'WithoutTime.php',
                'status' => 'new',
            ]),
            Migration::createInstance([
                'file' => '2018-01-01T00.00.00Z Before.php',
                'status' => 'new',
            ]),
            Migration::createInstance([
                'file' => '2018-01-02T00.00.00Z Equal.php',
                'status' => 'new',
            ]),
            Migration::createInstance([
                'file' => '2018-01-03T00.00.00Z After.php',
                'status' => 'new',
            ]),
        ];
        $this->setProtectedProperty($this->migrations, 'migrations', $migrations);

        $this->migrations->shouldReceive('up')->withArgs(array_slice($migrations, 0, 3))
            ->once()->andReturn(true);

        $result = $this->migrations->migrateTo('2018-01-02T00:00:00Z');

        self::assertTrue($result);
    }

    /** @test */
    public function filtersDoneAfterSearch()
    {
        $migrations = [
            Migration::createInstance([
                'file' => 'FileA.php',
                'status' => 'done',
            ]),
            Migration::createInstance([
                'file' => 'FileB.php',
                'status' => 'done',
            ]),
            Migration::createInstance([
                'file' => 'FileC.php',
                'status' => 'new',
            ]),
        ];
        $this->setProtectedProperty($this->migrations, 'migrations', $migrations);

        $this->migrations->shouldReceive('up')->with()
            ->once()->andReturn(true);

        $result = $this->migrations->migrateTo('FileB');

        self::assertTrue($result);
    }
}
