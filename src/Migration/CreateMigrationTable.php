<?php

namespace Breyta\Migration;

use Breyta\AbstractMigration;

class CreateMigrationTable extends AbstractMigration
{
    public function up(): void
    {
        $this->exec('CREATE TABLE migrations (
            file CHARACTER VARYING (64) NOT NULL,
            executed TIMESTAMP NOT NULL,
            status CHARACTER VARYING (16) NOT NULL DEFAULT \'done\',
            info TEXT,
            PRIMARY KEY (file)
        )');
    }

    public function down(): void
    {
        $this->exec('DROP TABLE migrations');
    }
}

