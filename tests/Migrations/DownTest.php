<?php

namespace Breyta\Test\Migrations;

use Breyta\Migration\CreateMigrationTable;
use Breyta\Model\Migration;
use Breyta\Model\Statement;
use Breyta\Test\Example\CreateAnimalsTable;
use Breyta\Test\TestCase;

class DownTest extends TestCase
{
    /** @test */
    public function executesMigrationsInSeparateTransactions()
    {
        list($migrationTableMigration, $animalsTableMigration) = $this->mockMigrations([
            'file' => '@breyta/CreateMigrationTable.php',
            'class' => CreateMigrationTable::class,
            'status' => 'done'
        ], [
            'file' => 'CreateAnimalsTable.php',
            'class' => CreateAnimalsTable::class,
            'status' => 'done'
        ]);

        $this->pdo->shouldReceive('beginTransaction')->once()->ordered();
        $migrationTableMigration->mock->shouldReceive('down')->once()->ordered();
        $this->pdo->shouldReceive('commit')->once()->ordered();
        $this->pdo->shouldReceive('beginTransaction')->once()->ordered();
        $animalsTableMigration->mock->shouldReceive('down')->once()->ordered();
        $this->pdo->shouldReceive('commit')->once()->ordered();

        $result = $this->migrations->down($migrationTableMigration->model, $animalsTableMigration->model);

        self::assertTrue($result);
    }

    /** @test */
    public function updatesTheMigrationStatus()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class, 'done');

        $migration->mock->shouldReceive('down')->with()->once()->ordered();
        $this->mockPreparedStatement('/^update migrations set/i')
            ->shouldReceive('execute')->withArgs(function (array $values) use ($migration) {
                self::assertCount(6, $values);
                self::assertSame($migration->model->executed->format('c'), array_shift($values));
                self::assertSame(date('c'), array_shift($values));
                self::assertSame('reverted', array_shift($values));
                self::assertSame('[]', array_shift($values));
                self::assertInternalType('double', array_shift($values));
                self::assertSame('@breyta/CreateMigrationTable.php', array_shift($values));
                return true;
            })->once()->andReturn(1)->ordered();

        $result = $this->migrations->down($migration->model);

        self::assertTrue($result);
    }

    /** @test */
    public function pdoExceptionCausesARollback()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class, 'done');

        $migration->mock->shouldReceive('down')->with()->once()->andThrows(\PDOException::class)->ordered();
        $this->pdo->shouldReceive('rollback')->once()->ordered();

        self::expectException(\PDOException::class);
        $this->migrations->down($migration->model);
    }

    /** @test */
    public function addsExecutedStatementsAndExecutionTime()
    {
        list($migration) = $this->mockMigrations([
            'model' => Migration::createInstance([
                'file' => 'CreateAnimalsTable.php',
                'status' => 'done',
                'executed' => date('c', strtotime('-1 hour')),
                'statements' => json_encode([Statement::createInstance([
                    'raw' => 'DROP TABLE IF EXISTS animals',
                    'teaser' => 'DROP TABLE animals',
                    'action' => 'drop',
                    'type' => 'table',
                    'name' => 'animals',
                ]), Statement::createInstance([
                    'raw' => 'CREATE TABLE animals (id MEDIUMINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id))',
                    'teaser' => 'CREATE TABLE animals',
                    'action' => 'create',
                    'type' => 'table',
                    'name' => 'animals',
                ])]),
                'executionTime' => 300
            ]),
            'class' => CreateAnimalsTable::class
        ]);

        $this->mockExecuteStatementOn($migration, 'down', $statement = Statement::createInstance([
            'raw' => 'DROP TABLE animals',
            'teaser' => 'DROP TABLE animals',
            'action' => 'drop',
            'type' => 'table',
            'name' => 'animals',
        ]));

        self::assertCount(3, $migration->model->statements);
        self::assertSame($statement, end($migration->model->statements));
    }
}
