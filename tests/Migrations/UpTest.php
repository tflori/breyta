<?php

namespace Breyta\Test\Migrations;

use Breyta\Migration\CreateMigrationTable;
use Breyta\Model\Statement;
use Breyta\Test\Example\CreateAnimalsTable;
use Breyta\Test\TestCase;

class UpTest extends TestCase
{
    /** @test */
    public function executesMigrationsInSeparateTransactions()
    {
        list($migrationTableMigration, $animalsTableMigration) = $this->mockMigrations([
            'file' => '@breyta/CreateMigrationTable.php',
            'class' => CreateMigrationTable::class,
            'status' => 'new'
        ], [
            'file' => 'CreateAnimalsTable.php',
            'class' => CreateAnimalsTable::class,
            'status' => 'new'
        ]);

        $this->pdo->shouldReceive('beginTransaction')->once()->ordered();
        $migrationTableMigration->mock->shouldReceive('up')->once()->ordered();
        $this->pdo->shouldReceive('commit')->once()->ordered();
        $this->pdo->shouldReceive('beginTransaction')->once()->ordered();
        $animalsTableMigration->mock->shouldReceive('up')->once()->ordered();
        $this->pdo->shouldReceive('commit')->once()->ordered();

        $result = $this->migrations->up($migrationTableMigration->model, $animalsTableMigration->model);

        self::assertTrue($result);
    }

    /** @test */
    public function savesTheMigrationStatus()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->mock->shouldReceive('up')->with()->once()->ordered();
        $this->mockPreparedStatement('/^insert into migrations/i')
            ->shouldReceive('execute')->withArgs(function (array $values) {
                self::assertCount(5, $values);
                self::assertSame('@breyta/CreateMigrationTable.php', array_shift($values));
                self::assertSame(date('c'), array_shift($values));
                self::assertSame('done', array_shift($values));
                self::assertSame('[]', array_shift($values));
                self::assertInternalType('double', array_shift($values));
                return true;
            })->once()->andReturn(1)->ordered();

        $result = $this->migrations->up($migration->model);

        self::assertTrue($result);
    }

    /** @test */
    public function updatesTheMigrationStatus()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class, 'failed');

        $migration->mock->shouldReceive('up')->with()->once()->ordered();
        $this->mockPreparedStatement('/^update migrations set/i')
            ->shouldReceive('execute')->withArgs(function (array $values) {
                self::assertCount(6, $values);
                self::assertSame(date('c'), array_shift($values));
                self::assertSame(null, array_shift($values));
                self::assertSame('done', array_shift($values));
                self::assertSame('[]', array_shift($values));
                self::assertInternalType('double', array_shift($values));
                self::assertSame('@breyta/CreateMigrationTable.php', array_shift($values));
                return true;
            })->once()->andReturn(1)->ordered();

        $result = $this->migrations->up($migration->model);

        self::assertTrue($result);
    }

    /** @test */
    public function pdoExceptionCausesARollback()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->mock->shouldReceive('up')->with()->once()->andThrows(\PDOException::class)->ordered();
        $this->pdo->shouldReceive('rollback')->once()->ordered();

        self::expectException(\PDOException::class);
        $this->migrations->up($migration->model);
    }

    /** @test */
    public function savesFailedStatus()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->mock->shouldReceive('up')->with()->once()->andThrows(\PDOException::class)->ordered();
        $this->mockPreparedStatement('/^insert into migrations/i')
            ->shouldReceive('execute')->withArgs(function (array $values) {
                self::assertCount(5, $values);
                self::assertSame('@breyta/CreateMigrationTable.php', array_shift($values));
                self::assertSame(date('c'), array_shift($values));
                self::assertSame('failed', array_shift($values));
                self::assertSame('[]', array_shift($values));
                self::assertInternalType('double', array_shift($values));
                return true;
            })->once()->andReturn(1)->ordered();

        self::expectException(\PDOException::class);
        $this->migrations->up($migration->model);
    }

    /** @test */
    public function executorRequiresAStatement()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->mock->shouldReceive('up')->with()
            ->once()->andReturnUsing(function () {
                call_user_func($this->executor, 'CREATE TABLE migrations (col INT NOT NULL, PRIMARY KEY (col))');
            });

        self::expectException(\Error::class);
        self::expectExceptionMessage(' must be an instance of Breyta\Model\Statement, string given');

        $this->migrations->up($migration->model);
    }

    /** @test */
    public function executorExecutesTheStatement()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->mock->shouldReceive('up')->with()
            ->once()->andReturnUsing(function () {
                call_user_func($this->executor, Statement::createInstance([
                    'raw' => 'CREATE TABLE migrations (col INT NOT NULL, PRIMARY KEY (col))',
                ]));
            })->ordered();
        $this->pdo->shouldReceive('exec')->with('CREATE TABLE migrations (col INT NOT NULL, PRIMARY KEY (col))')
            ->once()->andReturn(1);

        $this->migrations->up($migration->model);
    }

    /** @test */
    public function addsExecutionTimeToStatement()
    {
        $statement = Statement::createInstance([
            'raw' => 'CREATE TABLE migrations (col INT NOT NULL, PRIMARY KEY (col))',
        ]);

        $this->mockExecuteStatement($statement);

        self::assertInternalType('double', $statement->executionTime);
    }

    /** @test */
    public function addsThrownExceptionToStatement()
    {
        $statement = Statement::createInstance([
                'raw' => 'CREATE TABLE migrations (col INT NOT NULL, PRIMARY KEY (col))',
        ]);

        try {
            $this->mockExecuteStatement($statement, new \PDOException('Failed'));
            $this->fail('Expected PDOException');
        } catch (\PDOException $exception) {
            self::assertSame($exception, $statement->exception);
            self::assertInternalType('double', $statement->executionTime);
        }
    }

    /** @test */
    public function addsStatementsToMigrationStatus()
    {
        $statement = Statement::createInstance([
            'raw' => 'CREATE TABLE migrations (col INT NOT NULL, PRIMARY KEY (col))',
            'teaser' => 'CREATE TABLE migrations',
            'action' => 'create',
            'type' => 'table',
            'name' => 'migrations',
        ]);

        $this->mockPreparedStatement('/^insert into migrations/i')
            ->shouldReceive('execute')->withArgs(function (array $values) use ($statement) {
                self::assertCount(5, $values);
                self::assertSame(
                    json_encode([$statement]),
                    $values[3]
                );
                return true;
            })->once()->andReturn(1);

        $this->mockExecuteStatement($statement);
    }
}
