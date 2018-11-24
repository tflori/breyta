<?php

namespace Breyta\Test\Migrations;

use Breyta\Migration;
use Breyta\Migrations;
use Breyta\Test\TestCase;
use Mockery as m;

class StatusTest extends TestCase
{
    /** @test */
    public function queriesAllAppliedMigrations()
    {
        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $this->pdo->shouldReceive('query')->with(m::pattern('/SELECT .* FROM migrations/'))
            ->once()->andReturn(false);

        $migrations->getStatus();
    }

    /** @test */
    public function appliesCurrentStatusToMigrationList()
    {
        $migration = Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'executed' => date('Y-m-d\TH:i:s\Z', strtotime('-1 Hour')),
            'status' => 'done',
            'executions' => json_encode([
                [
                    'teaser' => 'CREATE TABLE migrations',
                    'action' => 'create',
                    'type' => 'table',
                    'name' => 'migrations',
                    'executionTime' => 0.1,
                ],
            ]),
            'executionTime' => 0.1,
        ]);

        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $this->pdo->shouldReceive('query')->with(m::pattern('/SELECT .* FROM migrations/'))
            ->once()->andReturn($statement = m::mock(\PDOStatement::class));
        $statement->shouldReceive('setFetchMode')->with(\PDO::FETCH_CLASS, Migration::class)
            ->once()->andReturn(true);
        $statement->shouldReceive('fetch')->with()
            ->twice()->andReturn($migration, false);

        $status = $migrations->getStatus();

        self::assertEquals($migration, $status->migrations['@breyta/CreateMigrationTable.php']);
    }

    /** @test */
    public function returnsACountOfNotAppliedMigrations()
    {
        $migration = Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'executed' => date('Y-m-d\TH:i:s\Z', strtotime('-1 Hour')),
            'status' => 'done',
            'executions' => json_encode([
                [
                    'teaser' => 'CREATE TABLE migrations',
                    'action' => 'create',
                    'type' => 'table',
                    'name' => 'migrations',
                    'executionTime' => 0.1,
                ],
            ]),
            'executionTime' => 0.1,
        ]);

        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $this->pdo->shouldReceive('query')->with(m::pattern('/SELECT .* FROM migrations/'))
            ->once()->andReturn($statement = m::mock(\PDOStatement::class));
        $statement->shouldReceive('setFetchMode')->with(\PDO::FETCH_CLASS, Migration::class)
            ->once()->andReturn(true);
        $statement->shouldReceive('fetch')->with()
            ->twice()->andReturn($migration, false);

        $status = $migrations->getStatus();

        self::assertSame(count($status->migrations) - 1, $status->count); // the expected count is one less
    }

    /** @test */
    public function ignoresExceptionsFromQuerying()
    {
        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $this->pdo->shouldReceive('query')->with(m::pattern('/SELECT .* FROM migrations/'))
            ->once()->andThrow(new \PDOException('unknown table migrations'));

        $status = $migrations->getStatus();

        self::assertObjectHasAttribute('migrations', $status);
        self::assertObjectHasAttribute('count', $status);
    }

    /** @test */
    public function returnsAnArrayOfMissingMigrations()
    {
        $migration = Migration::createInstance([
            'file' => 'manually executed',
            'executed' => date('Y-m-d\TH:i:s\Z', strtotime('-1 Hour')),
            'status' => 'done',
            'executions' => json_encode([]),
            'executionTime' => 0.1,
        ]);

        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $this->pdo->shouldReceive('query')->with(m::pattern('/SELECT .* FROM migrations/'))
            ->once()->andReturn($statement = m::mock(\PDOStatement::class));
        $statement->shouldReceive('setFetchMode')->with(\PDO::FETCH_CLASS, Migration::class)
            ->once()->andReturn(true);
        $statement->shouldReceive('fetch')->with()
            ->twice()->andReturn($migration, false);

        $status = $migrations->getStatus();

        self::assertObjectHasAttribute('missing', $status);
        self::assertSame(1, count($status->missing));
        self::assertContains($migration, $status->missing);
    }
}
