# breyta

Breyta is a **library** for database migrations. There are a lot of applications for database migrations but no
actual library (without any user interface).

## Trivia

I created this library after trying ruckusing-migrations. It's the only one that does not require any additional library
to provide a user interface and is mentioned in awesome-php. Unfortunately it seems to be not actively developed and
maintained. Also I'm missing some functionality and it is very old, not using PSR-2/4 and namespaces.

The name for this library comes again from the Icelandic language and means "to change".

## Concept

* A migration is a set of database statements
* A migration has to run in a transaction
* Logging is important and goes to the migration table
* No code generating from this library (pure SQL)
* No user interface (API only)
* Generate fancy output and support progress bars

## Installation

We only support and suggest using composer - everything else on your own risk.

```console
$ composer require tflori/breyta
```

## Usage

> This might change in the next days till version 1.

Migration Script:

```php
<?php

namespace Breyta\Migrations;

use Breyta\AbstractMigration;

class CreateUsersTable extends AbstractMigration
{
    public function up()
    {
        $this->exec('CREATE TABLE "users" (
            "id" bigserial,
            "name" character varying (255) NOT NULL,
            PRIMARY KEY ("id")
        )');
    }

    public function down()
    {
        $this->exec('DROP TABLE "users"');
    }
}
```

Control structure:

```php

namespace App\Cli\Commands;

class MigrateCommand extends AbstractCommand
{
    /**
     * Your database connection
     * @var PDO
     */
    protected $db;

    public function handle()
    {
        $breyta = new Breyta\Migrations($this->db, '/path/to/migrations');
        $breyta->migrate(function ($message) {
            $this->info($message); // how ever you output messages
        });

        $this->info($breyta->status());
    }
}
