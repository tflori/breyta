<?php

namespace Breyta\Test\Example;

use Breyta\AbstractMigration;

class CreateAnimalsTable extends AbstractMigration
{
    /**
     * Bring the migration up
     */
    public function up(): void
    {
        $this->exec('DROP TABLE IF EXISTS animals');
        $this->exec('CREATE TABLE animals (
             id MEDIUMINT NOT NULL AUTO_INCREMENT,
             name CHAR(30) NOT NULL,
             PRIMARY KEY (id)
        )');
    }

    /**
     * Bring the migration down
     */
    public function down(): void
    {
        $this->exec('DROP TABLE IF EXISTS animals');
    }
}
