<?php

namespace Breyta\Migration;

use Breyta\AbstractMigration;

class CreateMigrationTable extends AbstractMigration
{
    public function up(): void
    {
        $this->exec("CREATE TABLE migrations (
            file CHARACTER VARYING (64) NOT NULL,
            executed TIMESTAMP NOT NULL,
            reverted TIMESTAMP,
            status CHARACTER VARYING (16) NOT NULL DEFAULT 'done',
            statements TEXT,
            executionTime DOUBLE PRECISION,
            PRIMARY KEY (file)
        )");
        $this->exec("CREATE INDEX migrations_executed_index ON migrations (executed)");
        $this->exec("CREATE INDEX migrations_status_index ON migrations (status)");
        $this->exec("CREATE INDEX migrations_execution_time ON migrations (executionTime)");
    }

    /** @codeCoverageIgnore */
    public function down(): void
    {
        // we never delete this table again ;-)
    }
}
