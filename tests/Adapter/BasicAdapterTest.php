<?php

namespace Breyta\Test\Adapter;

use Breyta\BasicAdapter;
use Breyta\Model\Statement;
use Breyta\Test\TestCase;
use Mockery as m;

class BasicAdapterTest extends TestCase
{
    /** @test */
    public function executesTheStatement()
    {
        $spy = m::spy(function ($statement) {
            return true;
        });

        $adapter = new BasicAdapter($spy);

        $adapter->exec('This is a test');

        $spy->shouldHaveBeenCalled()->with(m::on(function (Statement $statement) {
            return $statement->raw === 'This is a test';
        }))->once();
    }

    /** @dataProvider provideStatementInfos
     * @param string $statement
     * @param \stdClass $expected
     * @test */
    public function returnsInformationAboutTheStatement(string $statement, \stdClass $expected)
    {
        $adapter = new BasicAdapter(function () {
        });

        $statement = $adapter->getStatement($statement);

        foreach ($expected as $key => $value) {
            self::assertObjectHasAttribute($key, $statement);
            self::assertSame($value, $statement->$key);
        }
    }

    public function provideStatementInfos()
    {
        return [
            [
                'Any text is just cut of at 50 characters as teaser without further information',
                (object)[
                    'teaser' => 'Any text is just cut of at 50 characters as teaser'
                ]
            ],
            [
                'update the_table set a = b where a != b',
                (object)[
                    'teaser' => 'UPDATE the_table',
                    'action' => 'update',
                    'name' => 'the_table'
                ]
            ],
            [
                'UPDATE "users" SET a = b WHERE a != b',
                (object)[
                    'teaser' => 'UPDATE "users"',
                    'action' => 'update',
                    'name' => 'users'
                ]
            ],
            [
                'CREATE TABLE animals (col INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (col))',
                (object)[
                    'teaser' => 'CREATE TABLE animals',
                    'action' => 'create',
                    'type' => 'table',
                    'name' => 'animals'
                ]
            ],
            [
                'DROP INDEX "schema"."index" ON "schema"."table"',
                (object)[
                    'teaser' => 'DROP INDEX "schema"."index"',
                    'action' => 'drop',
                    'type' => 'index',
                    'name' => 'schema.index'
                ]
            ],
            [
                'CREATE OR REPLACE VIEW `some`.`name` AS SELECT * FROM a_table',
                (object)[
                    'teaser' => 'CREATE VIEW `some`.`name`',
                    'action' => 'create',
                    'type' => 'view',
                    'name' => 'some.name'
                ]
            ],
        ];
    }
}
