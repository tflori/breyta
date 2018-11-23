<?php

namespace Breyta\Test;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

abstract class TestCase extends MockeryTestCase
{
    /** @var m\Mock|\PDO */
    protected $pdo;

    protected function setUp()
    {
        parent::setUp();

        $pdo = $this->pdo = m::mock(\PDO::class);
        $pdo->shouldReceive('query')->andReturn(false)->byDefault();
    }
}
