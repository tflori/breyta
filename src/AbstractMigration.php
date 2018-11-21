<?php

namespace Breyta;

use Breyta\Adapter\BasicAdapter;

abstract class AbstractMigration
{
    private $adapter;

    public function __construct(BasicAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Bring the migration up
     */
    abstract public function up(): void;

    /**
     * Bring the migration down
     */
    abstract public function down(): void;

    protected function exec($statement)
    {
        return $this->adapter->exec($statement);
    }
}
