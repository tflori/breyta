<?php

namespace Breyta;

/**
 * Interface AdapterInterface
 *
 * You may want to define an adapter with additional helpers like creating tables etc. The only adapter provided in this
 * library is a BasicAdapter that just executes sql statements.
 *
 * @package Breyta
 * @author Thomas Flori <thflori@gmail.com>
 */
interface AdapterInterface
{
    /**
     * Adapter gets a callable $executor
     *
     * The executor requires a Breyta\Model\Statement argument and is the only way an adapter can interact with
     * the database.
     *
     * @param callable $executor
     */
    public function __construct(callable $executor);

    /**
     * Execute an sql statement
     *
     * Returns false on error and an integer of affected rows on success.
     *
     * @param string $sql
     * @return mixed
     * @see http://php.net/manual/en/pdo.exec.php for a details about the return statement
     */
    public function exec(string $sql);
}
