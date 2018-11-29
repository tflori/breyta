<?php

namespace Breyta\Test\Migrations;

use Breyta\Model\Migration;
use Breyta\Test\TestCase;

class RevertTest extends TestCase
{
    /** @test */
    public function returnsSuccessWhenNoMigrationsNeedToBeReverted()
    {
        $this->setProtectedProperty($this->migrations, 'migrations', [Migration::createInstance([
            'file' => 'CreateAnimalsTable.php',
            'status' => 'new'
        ])]);
        $this->migrations->shouldReceive('down')->with()->once()->andReturn(true);

        $result = $this->migrations->revert();

        self::assertTrue($result);
    }

    /** @test */
    public function revertsDoneMigrations()
    {
        $this->setProtectedProperty($this->migrations, 'migrations', [$migration = Migration::createInstance([
            'file' => 'CreateAnimalsTable.php',
            'status' => 'done',
        ])]);
        $this->migrations->shouldReceive('down')->with($migration)->once()->andReturn(true);

        $result = $this->migrations->revert();

        self::assertTrue($result);
    }

    /** @test */
    public function doesNotRevertInternalMigrations()
    {
        $this->setProtectedProperty($this->migrations, 'migrations', [Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'done'
        ])]);
        $this->migrations->shouldReceive('down')->with()->once()->andReturn(true);

        $result = $this->migrations->revert();

        self::assertTrue($result);
    }

    /** @test */
    public function doesNotRevertFailedMigrations()
    {
        $this->setProtectedProperty($this->migrations, 'migrations', [Migration::createInstance([
            'file' => 'CreateAnimalsTable.php',
            'status' => 'failed'
        ])]);
        $this->migrations->shouldReceive('down')->with()->once()->andReturn(true);

        $result = $this->migrations->revert();

        self::assertTrue($result);
    }

    /** @test */
    public function doesNotRevertRevertedMigrations()
    {
        $this->setProtectedProperty($this->migrations, 'migrations', [Migration::createInstance([
            'file' => 'CreateAnimalsTable.php',
            'status' => 'reverted'
        ])]);
        $this->migrations->shouldReceive('down')->with()->once()->andReturn(true);

        $result = $this->migrations->revert();

        self::assertTrue($result);
    }

    /** @test */
    public function revertsToMatchingFile()
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
                'status' => 'done',
            ]),
            Migration::createInstance([
                'file' => 'FileD.php',
                'status' => 'done',
            ]),
        ];
        $this->setProtectedProperty($this->migrations, 'migrations', $migrations);

        $this->migrations->shouldReceive('down')->withArgs(array_reverse(array_slice($migrations, -2)))
            ->once()->andReturn(true);

        $result = $this->migrations->revertTo('FileB');

        self::assertTrue($result);
    }

    /** @test */
    public function throwsWhenNoFileMatches()
    {
        $migrations = [
            Migration::createInstance([
                'file' => '@breyta/CreateMigrationTable.php',
                'status' => 'done',
            ]),
        ];
        $this->setProtectedProperty($this->migrations, 'migrations', $migrations);

        self::expectException(\LogicException::class);
        self::expectExceptionMessage('No migration found matching FileB');

        $this->migrations->revertTo('FileB');
    }

    /** @test */
    public function filtersByFilesAfterTime()
    {
        $migrations = [
            Migration::createInstance([
                'file' => 'WithoutTime.php',
                'status' => 'done',
            ]),
            Migration::createInstance([
                'file' => '2018-01-01T00.00.00Z Before.php',
                'status' => 'done',
            ]),
            Migration::createInstance([
                'file' => '2018-01-02T00.00.00Z Equal.php',
                'status' => 'done',
            ]),
            Migration::createInstance([
                'file' => '2018-01-03T00.00.00Z After.php',
                'status' => 'done',
            ]),
            Migration::createInstance([
                'file' => '2018-01-04T00.00.00Z After.php',
                'status' => 'done',
            ]),
        ];
        $this->setProtectedProperty($this->migrations, 'migrations', $migrations);

        $this->migrations->shouldReceive('down')->withArgs(array_reverse(array_slice($migrations, -2)))
            ->once()->andReturn(true);

        $result = $this->migrations->revertTo('2018-01-02T00:00:00Z');

        self::assertTrue($result);
    }

    /** @test */
    public function filtersNotDoneAfterSearch()
    {
        $migrations = [
            Migration::createInstance([
                'file' => 'FileA.php',
                'status' => 'failed',
            ]),
            Migration::createInstance([
                'file' => 'FileB.php',
                'status' => 'new',
            ]),
            Migration::createInstance([
                'file' => 'FileC.php',
                'status' => 'done',
            ]),
            Migration::createInstance([
                'file' => 'FileD.php',
                'status' => 'done',
            ]),
        ];
        $this->setProtectedProperty($this->migrations, 'migrations', $migrations);

        $this->migrations->shouldReceive('down')->withArgs(array_reverse(array_slice($migrations, -2)))
            ->once()->andReturn(true);

        $result = $this->migrations->revertTo('FileA');

        self::assertTrue($result);
    }
}
