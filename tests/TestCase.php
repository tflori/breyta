<?php

namespace Breyta\Test;

use Breyta\AbstractMigration;
use Breyta\AdapterInterface;
use Breyta\BasicAdapter;
use Breyta\Migration\CreateMigrationTable;
use Breyta\Migrations;
use Breyta\Model\Migration;
use Breyta\Model\Statement;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

abstract class TestCase extends MockeryTestCase
{
    /** @var m\Mock */
    protected $pdo;

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
        date_default_timezone_set('UTC');

        $pdo = $this->pdo = m::mock(\PDO::class);
        $pdo->shouldReceive('setAttribute')->andReturn(true)->byDefault();
        $pdo->shouldReceive('beginTransaction')->byDefault();
        $pdo->shouldReceive('commit')->byDefault();
        $pdo->shouldReceive('rollback')->byDefault();
        $pdo->shouldReceive('query')->andReturn(false)->byDefault();
        $this->mockPreparedStatement('/^insert into migrations/i', true);
        $this->mockPreparedStatement('/^update migrations set/i', true, 0);

        $resolver = $this->resolver = m::spy(function ($class, ...$args) {
            return new $class(...$args);
        });
        $this->migrations = m::mock(Migrations::class, [$this->pdo, __DIR__ . '/Example', $resolver])
            ->makePartial();
        $resolver->shouldReceive('__invoke')->with(AdapterInterface::class, m::type(\Closure::class))
            ->andReturnUsing(function ($class, callable $executor) {
                $this->executor = m::spy($executor);
                return new BasicAdapter($this->executor);
            })->byDefault();
    }

    protected function mockPreparedStatement(string $pattern, $byDefault = false, $defaultResult = 1)
    {
        $statement = m::mock(\PDOStatement::class);
        $statement->shouldReceive('execute')->byDefault()->andReturn($defaultResult);

        $expectation = $this->pdo->shouldReceive('prepare')->with(m::pattern($pattern));
        if ($byDefault) {
            $expectation->byDefault()->andReturn($statement);
        } else {
            $expectation->once()->andReturn($statement);
        }

        return $statement;
    }

    protected function mockStatus(Migration ...$migrations): m\CompositeExpectation
    {
        $status = (object)[
            'migrations' => $migrations,
            'count' => count(array_filter($migrations, function (Migration $migration) {
                return $migration->status !== 'done';
            }))
        ];

        return $this->migrations->shouldReceive('getStatus')->with()->andReturn($status);
    }

    protected function mockMigration(string $file, string $class, string $status = 'new'): \stdClass
    {
        return $this->mockMigrations(['file' => $file, 'class' => $class, 'status' => $status])[0];
    }

    protected function mockMigrations(...$migrationProperties)
    {
        $migrations = [];
        foreach ($migrationProperties as $migration) {
            $migrations[] = (object)[
                'model' => isset($migration['model']) ? $migration['model'] : Migration::createInstance([
                    'file' => $migration['file'],
                    'status' => $migration['status'],
                    'executed' => $migration['status'] === 'new' ? null : date('c', strtotime('-1 hour')),
                ]),
                'mock' => $mock = m::mock($migration['class']),
            ];
            $this->resolver->shouldReceive('__invoke')->with($migration['class'], m::type(AdapterInterface::class))
                ->andReturn($mock);
        }

        return $migrations;
    }

    protected function mockExecuteStatement(Statement $statement, $result = 1)
    {
        $this->mockExecuteStatementOn(
            $this->mockMigration('@breyta/CreateMigrationTable.php', CreateMigrationTable::class),
            'up',
            $statement,
            $result
        );
    }

    protected function mockExecuteStatementOn(
        \stdClass $migration,
        string $direction,
        Statement $statement,
        $result = 1
    ) {
        $migration->mock->shouldReceive($direction)->with()
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

        $this->migrations->$direction($migration->model);
    }

    protected function setProtectedProperty($obj, $propertyName, $value)
    {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }

    protected function getProtectedProperty($obj, $propertyName)
    {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }
}
