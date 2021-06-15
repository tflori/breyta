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
            reverted TIMESTAMP NULL DEFAULT NULL,
            status CHARACTER VARYING (16) NOT NULL DEFAULT 'done',
            statements TEXT,
            execution_time DOUBLE PRECISION,
            PRIMARY KEY (file)
        )");
        $this->exec("CREATE INDEX {$table}_executed_index ON {$table} (executed)");
        $this->exec("CREATE INDEX {$table}_status_index ON {$table} (status)");
        $this->exec("CREATE INDEX {$table}_execution_time_index ON {$table} (execution_time)");
    }

    /** @codeCoverageIgnore */
    public function down(): void
    {
        // we never delete this table again ;-)
    }
}
