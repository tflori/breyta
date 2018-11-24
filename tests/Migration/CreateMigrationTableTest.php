<?php

namespace Breyta\Test\Migration;

use Breyta\BasicAdapter;
use Breyta\Migration\CreateMigrationTable;
use Breyta\Test\TestCase;
use Mockery as m;

class CreateMigrationTableTest extends TestCase
{
    /** @test */
    public function createsATable()
    {
        $adapter = m::mock(BasicAdapter::class)->shouldIgnoreMissing();
        $migration = new CreateMigrationTable($adapter);

        $migration->up();

        $adapter->shouldHaveReceived('exec')->with(m::pattern('/create table migrations/i'))->once();
    }

    /** @test */
    public function createIndexesOnMigrationTable()
    {
        $adapter = m::mock(BasicAdapter::class)->shouldIgnoreMissing();
        $migration = new CreateMigrationTable($adapter);

        $migration->up();

        $adapter->shouldHaveReceived('exec')
            ->with(m::pattern('/create index.* on migrations\s*\(\s*executed\s*\)/i'))
            ->once();
        $adapter->shouldHaveReceived('exec')
            ->with(m::pattern('/create index.* on migrations\s*\(\s*status\s*\)/i'))
            ->once();
        $adapter->shouldHaveReceived('exec')
            ->with(m::pattern('/create index.* on migrations\s*\(\s*executionTime\s*\)/i'))
            ->once();
    }

    /** @test */
    public function dropsTheMigrationTable()
    {
        $adapter = m::mock(BasicAdapter::class)->shouldIgnoreMissing();
        $migration = new CreateMigrationTable($adapter);

        $migration->down();

        $adapter->shouldHaveReceived('exec')->with(m::pattern('/drop table migrations/i'));
    }
}
