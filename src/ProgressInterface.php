<?php

namespace Breyta;

interface ProgressInterface
{
    /**
     * Output information about starting the migration process
     *
     * Info contains:
     *  - `migrations` - an array of \stdClass containing the following info:
     *    - `file` - the file name (and unique identifier)  of the migration
     *    - `status` - the current status of the migration (new, done or failed)
     *    - `executed` - UTC date time string when the migration was executed (only done and failed)
     *    - `executionTime` - the time it required to execute the migration (only done and failed)
     *  - `count` - an integer how many migrations are going to be executed
     *
     * @param \stdClass $info
     */
    public function start(\stdClass $info);
    
    /**
     * Output information about the $migration (before the migration)
     *
     * Migration contains:
     *  - `file` - the file name (and unique identifier)  of the migration
     *  - `status` - the current status of the migration (new, done or failed)
     */
    public function beforeMigration(\stdClass $migration);
    
    /**
     * Output information about the $statement (before it gets executed)
     *
     * Statement contains:
     *  - `teaser` - a brief text (without line breaks) that describes the query (e. g. CREATE TABLE migrations)
     *  - `action` - **optional** the database action to execute (e. g. create)
     *  - `type` - **optional** the type on which the action gets executed (e. g. table)
     *  - `name` - **optional** the name of the object (e. g. migrations)
     */
    public function beforeExecution(\stdClass $statement);
    
    /**
     * Output information about the $statement (after it gets executed)
     *
     * Statement contains:
     *  - `teaser` - a brief text (without line breaks) that describes the query (e. g. CREATE TABLE migrations)
     *  - `action` - **optional** the database action to execute (e. g. create)
     *  - `type` - **optional** the type on which the action gets executed (e. g. table)
     *  - `name` - **optional** the name of the object (e. g. migrations)
     *  - `executionTime` - the time it required to execute the satement (in seconds)
     */
    public function afterExecution(\stdClass $statement);
    
    /**
     * Output information about the $migration (after the migration)
     *
     * Migration contains:
     *  - `file` - the file name (and unique identifier)  of the migration
     *  - `status` - the current status of the migration (new, done or failed)
     *  - `executionTime` - the time it required to execute the migration (in seconds)
     *  - `statements` - an array of statements that got executed
     */
    public function afterMigration(\stdClass $migration);
    
    /**
     * Output information about what just happened
     *
     * Info contains:
     *  - `migrations` - an array of \stdClass containing the following info:
     *    - `file` - the file name (and unique identifier)  of the migration
     *    - `status` - the current status of the migration (new, done or failed)
     *    - `executed` - UTC date time string when the migration was executed (only done and failed)
     *    - `executionTime` - the time it required to execute the migration (only done and failed)
     *  - `executed` - an array of migrations that just got executed
     */
    public function finish(\stdClass $info);
}
