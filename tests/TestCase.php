<?php

namespace Breyta\Test;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

abstract class TestCase extends MockeryTestCase
{
    /** @var m\Mock */
    protected $pdo;

    protected function setUp()
    {
        date_default_timezone_set('UTC');

        $pdo = $this->pdo = m::mock(\PDO::class);
        $pdo->shouldReceive('setAttribute')->with(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION)
            ->andReturn(true)->byDefault();
        $pdo->shouldReceive('beginTransaction')->byDefault();
        $pdo->shouldReceive('commit')->byDefault();
        $pdo->shouldReceive('rollback')->byDefault();
        $pdo->shouldReceive('query')->andReturn(false)->byDefault();
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
}
