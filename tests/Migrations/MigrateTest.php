<?php

namespace Breyta\Test\Migrations;

use Breyta\Migrations;
use Breyta\Test\TestCase;

class MigrateTest extends TestCase
{
    /** @test */
    public function returnsSuccessAsBoolean()
    {
        $migrations = new Migrations($this->pdo, __DIR__ . '/../Example');

        $result = $migrations->migrate();

        self::assertTrue($result);
    }
}
