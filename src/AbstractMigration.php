<?php

namespace Breyta;

/**
 * @method mixed exec(string $statement)
 */
abstract class AbstractMigration
{
    private $adapter;

    public function __construct(AdapterInterface $adapter)
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

    public function __call($method, $args)
    {
        return call_user_func_array([$this->adapter, $method], $args);
    }
}
