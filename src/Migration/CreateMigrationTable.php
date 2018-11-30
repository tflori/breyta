<?php

namespace Breyta\Migration;

use Breyta\AbstractMigration;
use Breyta\Migrations;

class CreateMigrationTable extends AbstractMigration
{
    public function up(): void
    {
        $table = Migrations::$table ?? 'migrations';
        $this->exec("CREATE TABLE {$table} (
            file CHARACTER VARYING (64) NOT NULL,
            executed TIMESTAMP NOT NULL,
            reverted TIMESTAMP,
            status CHARACTER VARYING (16) NOT NULL DEFAULT 'done',
            statements TEXT,
            executionTime DOUBLE PRECISION,
            PRIMARY KEY (file)
        )");
        $this->exec("CREATE INDEX {$table}_executed_index ON {$table} (executed)");
        $this->exec("CREATE INDEX {$table}_status_index ON {$table} (status)");
        $this->exec("CREATE INDEX {$table}_executionTime_index ON {$table} (executionTime)");
    }

    /** @codeCoverageIgnore */
    public function down(): void
    {
        // we never delete this table again ;-)
    }
}
