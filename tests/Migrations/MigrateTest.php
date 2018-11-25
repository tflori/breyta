<?php

namespace Breyta\Test\Migrations;

use Breyta\AdapterInterface;
use Breyta\BasicAdapter;
use Breyta\Migration\CreateMigrationTable;
use Breyta\Migrations;
use Breyta\Model\Migration;
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

    protected function setUp()
    {
        parent::setUp();

        $resolver = $this->resolver = m::spy(function ($class, ...$args) {
            return new $class(...$args);
        });
        $this->migrations = m::mock(Migrations::class, [$this->pdo, __DIR__ . '/../Example', $resolver])
            ->makePartial();
        $resolver->shouldReceive('__invoke')->with(AdapterInterface::class, m::type(\Closure::class))
            ->andReturn(m::mock(BasicAdapter::class))->byDefault();
        $this->mockPreparedStatement('/^insert into migrations/i', true);
        $this->mockPreparedStatement('/^delete from migrations/i', true, 0);
    }

    /** @test */
    public function returnsSuccessWhenNoMigrationsNeedToBeExecuted()
    {
        $this->mockStatus(Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'done'
        ]))->once();

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesNewMigrations()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);
        $this->mockStatus(Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'new'
        ]))->once()->ordered();

        $migration->shouldReceive('up')->with()->once()->ordered();

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesFailedMigrations()
    {
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);
        $this->mockStatus(Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'failed'
        ]))->once()->ordered();

        $migration->shouldReceive('up')->with()->once()->ordered();

        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }

    /** @test */
    public function executesOnlyMatchingMigrations()
    {
        $migrationTableMigration = $this->mockMigration(
            '@breyta/CreateMigrationTable.php',
            CreateMigrationTable::class
        );
        $animalsTableMigration = $this->mockMigration('CreateAnimalsTable.php', CreateAnimalsTable::class);
        $this->mockStatus(Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'new'
        ]), Migration::createInstance([
            'file' => 'CreateAnimalsTable.php',
            'status' => 'new'
        ]))->once()->ordered();

        $migrationTableMigration->shouldReceive('up')->with()->once()->ordered();
        $animalsTableMigration->shouldNotReceive('up');

        $result = $this->migrations->migrate('MigrationTable');

        self::assertTrue($result);
    }

    /** @test */
    public function executesMigrationsInSeparateTransactions()
    {
        $migrationTableMigration = $this->mockMigration(
            '@breyta/CreateMigrationTable.php',
            CreateMigrationTable::class
        );
        $animalsTableMigration = $this->mockMigration('CreateAnimalsTable.php', CreateAnimalsTable::class);
        $this->mockStatus(Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'new'
        ]), Migration::createInstance([
            'file' => 'CreateAnimalsTable.php',
            'status' => 'new'
        ]))->once()->ordered();

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
        $this->mockStatus(Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'new'
        ]))->once()->ordered();

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
        $migration = $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class);
        $this->mockStatus(Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'failed'
        ]))->once()->ordered();

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
        $this->mockStatus(Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'new'
        ]))->once()->ordered();

        $migration->shouldReceive('up')->with()->once()->andThrows(\PDOException::class)->ordered();
        $this->pdo->shouldReceive('rollback')->once()->ordered();

        $result = $this->migrations->migrate();

        self::assertFalse($result);
    }

    /**
     * @param Migration ...$migrations
     * @return m\CompositeExpectation|m\Expectation
     */
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

    /**
     * @param string $file
     * @param string $class
     * @return m\MockInterface
     */
    protected function mockMigration(string $file, string $class): m\MockInterface
    {
        // add the file -> class mapping
        $reflection = new \ReflectionClass($this->migrations);
        $property = $reflection->getProperty('classes');
        $property->setAccessible(true);
        $classes = $property->getValue($this->migrations);
        $classes[$file] = $class;
        $property->setValue($this->migrations, $classes);

        $this->resolver->shouldReceive('__invoke')->with($class, m::type(AdapterInterface::class))
            ->andReturn($migrationInstance = m::mock(CreateMigrationTable::class));

        return $migrationInstance;
    }
}
