<?php

namespace Breyta;

use Breyta\Model\Migration;
use Breyta\Model\Statement;

/**
 * Class CallbackProgress
 *
 * Stores callbacks for migration progress.
 *
 * @package Breyta
 * @author Thomas Flori <thflori@gmail.com>
 * @codeCoverageIgnore This class has no own code it just stores callbacks
 */
class CallbackProgress implements ProgressInterface
{
    /** @var callable */
    protected $startCallback;

    /** @var callable */
    protected $beforeMigrationCallback;

    /** @var callable */
    protected $beforeExecutionCallback;

    /** @var callable */
    protected $afterExecutionCallback;

    /** @var callable */
    protected $afterMigrationCallback;

    /** @var callable */
    protected $finishCallback;

    /**
     * Output information about starting the migration process
     *
     * Info contains:
     *  - `migrations` - an array of Breyta\Model\Migration
     *  - `count` - an integer how many migrations are going to be executed
     *
     * @param \stdClass $info
     */
    public function start(\stdClass $info)
    {
        !$this->startCallback || call_user_func($this->startCallback, $info);
    }

    public function onStart(callable $callback): self
    {
        $this->startCallback = $callback;
        return $this;
    }

    /**
     * Output information about the $migration (before the migration)
     *
     * @param Migration $migration
     */
    public function beforeMigration(Migration $migration)
    {
        !$this->beforeMigrationCallback || call_user_func($this->beforeMigrationCallback, $migration);
    }

    public function onBeforeMigration(callable $callback): self
    {
        $this->beforeMigrationCallback = $callback;
        return $this;
    }

    /**
     * Output information about the $statement (before it gets executed)
     *
     * @param Statement $execution
     */
    public function beforeExecution(Statement $execution)
    {
        !$this->beforeExecutionCallback || call_user_func($this->beforeExecutionCallback, $execution);
    }

    public function onBeforeExecution(callable $callback): self
    {
        $this->beforeExecutionCallback = $callback;
        return $this;
    }

    /**
     * Output information about the $statement (after it gets executed)
     *
     * @param Statement $execution
     */
    public function afterExecution(Statement $execution)
    {
        !$this->afterExecutionCallback || call_user_func($this->afterExecutionCallback, $execution);
    }

    public function onAfterExecution(callable $callback): self
    {
        $this->afterExecutionCallback = $callback;
        return $this;
    }

    /**
     * Output information about the $migration (after the migration)
     *
     * @param Migration $migration
     */
    public function afterMigration(Migration $migration)
    {
        !$this->afterMigrationCallback || call_user_func($this->afterMigrationCallback, $migration);
    }

    public function onAfterMigration(callable $callback): self
    {
        $this->afterMigrationCallback = $callback;
        return $this;
    }

    /**
     * Output information about what just happened
     *
     * Info contains:
     *  - `migrations` - an array of Breyta\Model\Migration
     *  - `executed` - an array of migrations that just got executed
     *
     * @param \stdClass $info
     */
    public function finish(\stdClass $info)
    {
        !$this->finishCallback || call_user_func($this->finishCallback, $info);
    }

    public function onFinish(callable $callback): self
    {
        $this->finishCallback = $callback;
        return $this;
    }
}
