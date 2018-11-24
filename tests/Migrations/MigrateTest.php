<?php

namespace Breyta\Test\Migrations;

use Breyta\Migrations;
use Breyta\Model\Migration;
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
        $migrations = $this->migrations = m::mock(Migrations::class, [$this->pdo, __DIR__ . '/../Example', $resolver])
            ->makePartial();

        $migrations->shouldReceive('getStatus')->with()
            ->andReturn((object)[
                'migrations' => [
                    Migration::createInstance([
                        'file' => '@breyta/CreateMigrationTable.php',
                        'status' => 'done'
                    ]),
                ],
                'count' => 0,
            ])->byDefault();
    }

    /** @test */
    public function returnsSuccessAsBoolean()
    {
        $result = $this->migrations->migrate();

        self::assertTrue($result);
    }
}
