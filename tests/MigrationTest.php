<?php

namespace Breyta\Test;

use Breyta\Adapter\BasicAdapter;
use Breyta\Test\Example\CreateAnimalsTable;
use Mockery as m;

class MigrationTest extends TestCase
{
    /** @test */
    public function executesTheStatementOnDb()
    {
        $pdo = m::mock(BasicAdapter::class);
        $migration = new CreateAnimalsTable($pdo);

        $pdo->shouldReceive('exec')->andReturn(true)->byDefault();
        $pdo->shouldReceive('exec')->with(m::pattern('/^\s*create table animals\s*\(/i'))
            ->once()->andReturn(true);

        $migration->up();
    }
}
