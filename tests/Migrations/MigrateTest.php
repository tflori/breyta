<?php

namespace Breyta\Test\Migrations;

use Breyta\AdapterInterface;
use Breyta\BasicAdapter;
use Breyta\Migration\CreateMigrationTable;
use Breyta\Migrations;
use Breyta\Model\Migration;
use Breyta\Model\Statement;
use Breyta\Test\Example\CreateAnimalsTable;
use Breyta\Test\TestCase;
use Mockery as m;

class MigrateTest extends TestCase
{
    /** @var m\Mock|\PDOStatement */
    protected $statement;

    /** @var m\Mock|Migrations */
    protected $migrations;

    /** @var m\Mock */
    protected $resolver;

    /** @var callable */
    protected $executor;

    protected function setUp()
    {
        parent::setUp();

        $resolver = $this->resolver = m::spy(function ($class, ...$args) {
            return new $class(...$args);
        });
        $this->migrations = m::mock(Migrations::class, [$this->pdo, __DIR__ . '/../Example', $resolver])
            ->makePartial();
        $resolver->shouldReceive('__invoke')->with(AdapterInterface::class, m::type(\Closure::class))
            ->andReturnUsing(function ($class, callable $executor) {
                $this->executor = m::spy($executor);
                return new BasicAdapter($this->executor);
            })->byDefault();
        $this->mockPreparedStatement('/^insert into migrations/i', true);
        $this->mockPreparedStatement('/^delete from migrations/i', true, 0);
    }

    /** @test */
    public function returnsSuccessWhenNoMigrationsNeedToBeExecuted()
    {
        $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class, 'done');

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesNewMigrations()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->shouldReceive('up')->with()->once();

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesFailedMigrations()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->shouldReceive('up')->with()->once();

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesOnlyMatchingMigrations()
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

        $migrationTableMigration->shouldReceive('up')->with()->once();
        $animalsTableMigration->shouldNotReceive('up');

        $result = $this->migrations->migrate('MigrationTable');

        self::assertTrue($result);
    }

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
        $migrationTableMigration->shouldReceive('up')->once()->ordered();
        $this->pdo->shouldReceive('commit')->once()->ordered();
        $this->pdo->shouldReceive('beginTransaction')->once()->ordered();
        $animalsTableMigration->shouldReceive('up')->once()->ordered();
        $this->pdo->shouldReceive('commit')->once()->ordered();

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function savesTheMigrationStatus()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->shouldReceive('up')->with()->once()->ordered();
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

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function removesPreviousStatus()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class, 'failed');

        $migration->shouldReceive('up')->with()->once()->ordered();
        $this->mockPreparedStatement('/^delete from migrations/i')
            ->shouldReceive('execute')->with(['@breyta/CreateMigrationTable.php'])
            ->once()->andReturn(1)->ordered();

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function pdoExceptionCausesARollback()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->shouldReceive('up')->with()->once()->andThrows(\PDOException::class)->ordered();
        $this->pdo->shouldReceive('rollback')->once()->ordered();

        self::expectException(\PDOException::class);
        $this->migrations->migrate();
    }

    /** @test */
    public function savesFailedStatus()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->shouldReceive('up')->with()->once()->andThrows(\PDOException::class)->ordered();
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
        $this->migrations->migrate();
    }

    /** @test */
    public function executorRequiresAStatement()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);


        $migration->shouldReceive('up')->with()
            ->once()->andReturnUsing(function () {
                call_user_func($this->executor, 'CREATE TABLE migrations (col INT NOT NULL, PRIMARY KEY (col))');
            });

        self::expectException(\Error::class);
        self::expectExceptionMessage(' must be an instance of Breyta\Model\Statement, string given');

        $this->migrations->migrate();
    }

    /** @test */
    public function executorExecutesTheStatement()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->shouldReceive('up')->with()
            ->once()->andReturnUsing(function () {
                call_user_func($this->executor, Statement::createInstance([
                    'raw' => 'CREATE TABLE migrations (col INT NOT NULL, PRIMARY KEY (col))',
                ]));
            })->ordered();
        $this->pdo->shouldReceive('exec')->with('CREATE TABLE migrations (col INT NOT NULL, PRIMARY KEY (col))')
            ->once()->andReturn(1);

        $this->migrations->migrate();
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

    protected function mockStatus(Migration ...$migrations): m\CompositeExpectation
    {
        $status = (object)[
            'migrations' => array_combine(array_map(function (Migration $migration) {
                return $migration->file;
            }, $migrations), $migrations),
            'count' => count(array_filter($migrations, function (Migration $migration) {
                return $migration->status !== 'done';
            }))
        ];

        return $this->migrations->shouldReceive('getStatus')->with()->andReturn($status);
    }

    protected function mockMigration(string $file, string $class, string $status = 'new'): m\MockInterface
    {
        return $this->mockMigrations(['file' => $file, 'class' => $class, 'status' => $status])[0];
    }

    protected function mockMigrations(...$migrations)
    {
        $instances = [];
        $classes = [];
        $migrationStatus = [];
        foreach ($migrations as $migration) {
            $classes[$migration['file']] = $migration['class'];
            $this->resolver->shouldReceive('__invoke')->with($migration['class'], m::type(AdapterInterface::class))
                ->andReturn($instances[] = m::mock(CreateMigrationTable::class));
            $migrationStatus[] = Migration::createInstance([
                'file' => $migration['file'],
                'status' => $migration['status'],
            ]);
        }

        // add the file -> class mapping
        $this->setProtectedProperty(
            $this->migrations,
            'classes',
            array_merge(
                $this->getProtectedProperty($this->migrations, 'classes') ?? [],
                $classes
            )
        );

        $this->mockStatus(...$migrationStatus);

        return $instances;
    }

    protected function mockExecuteStatement(Statement $statement, $result = 1)
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);

        $migration->shouldReceive('up')->with()
            ->once()->andReturnUsing(function () use ($statement) {
                call_user_func($this->executor, $statement);
            })->ordered();
        $expectation = $this->pdo->shouldReceive('exec')->with($statement->raw)
            ->once();
        if ($result instanceof \Exception) {
            $expectation->andThrow($result);
        } else {
            $expectation->andReturn($result);
        }

        $this->migrations->migrate();
    }
}
