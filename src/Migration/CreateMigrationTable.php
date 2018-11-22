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
            status CHARACTER VARYING (16) NOT NULL DEFAULT 'done',
            executions TEXT,
            executionTime DOUBLE PRECISION,
            PRIMARY KEY (file)
        )");
        $this->exec("CREATE INDEX migrations_executed_index ON migrations (executed)");
        $this->exec("CREATE INDEX migrations_status_index ON migrations (status)");
        $this->exec("CREATE INDEX migrations_execution_time ON migrations (executionTime)");
    }

    public function down(): void
    {
        $this->exec('DROP TABLE migrations');
    }
}

