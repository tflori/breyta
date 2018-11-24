<?php

namespace Breyta;

use Breyta\Model\Migration;
use Breyta\Model\Statement;

interface ProgressInterface
{
    /**
     * Output information about starting the migration process
     *
     * Info contains:
     *  - `migrations` - an array of Breyta\Model\Migration
     *  - `count` - an integer how many migrations are going to be executed
     *
     * @param \stdClass $info
     */
    public function start(\stdClass $info);

    /**
     * Output information about the $migration (before the migration)
     * @param Migration $migration
     */
    public function beforeMigration(Migration $migration);

    /**
     * Output information about the $statement (before it gets executed)
     * @param Statement $execution
     */
    public function beforeExecution(Statement $execution);

    /**
     * Output information about the $statement (after it gets executed)
     * @param Statement $execution
     */
    public function afterExecution(Statement $execution);

    /**
     * Output information about the $migration (after the migration)
     * @param Migration $migration
     */
    public function afterMigration(Migration $migration);

    /**
     * Output information about what just happened
     *
     * Info contains:
     *  - `migrations` - an array of Breyta\Model\Migration
     *  - `executed` - an array of migrations that just got executed
     *
     * @param \stdClass $info
     */
    public function finish(\stdClass $info);
}
