# breyta

[![Build Status](https://travis-ci.org/tflori/breyta.svg?branch=master)](https://travis-ci.org/tflori/breyta)
[![Coverage Status](https://coveralls.io/repos/github/tflori/breyta/badge.svg?branch=master)](https://coveralls.io/github/tflori/breyta?branch=master)
[![Latest Stable Version](https://poser.pugx.org/tflori/breyta/v/stable.svg)](https://packagist.org/packages/tflori/breyta) 
[![Total Downloads](https://poser.pugx.org/tflori/breyta/downloads.svg)](https://packagist.org/packages/tflori/breyta) 
[![License](https://poser.pugx.org/tflori/breyta/license.svg)](https://packagist.org/packages/tflori/breyta)

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

class CreateAnimalsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->exec('DROP TABLE IF EXISTS animals');
        $this->exec('CREATE TABLE animals (
             id MEDIUMINT NOT NULL AUTO_INCREMENT,
             name CHAR(30) NOT NULL,
             PRIMARY KEY (id)
        )');
    }

    public function down(): void
    {
        $this->exec('DROP TABLE IF EXISTS animals');
    }
}
```

Control structure:

```php
<?php

namespace App\Cli\Commands;

class MigrateCommand extends AbstractCommand // implements \Breyta\ProgressInterface
{
    /**
     * Your database connection
     * @var PDO
     */
    protected $db;

    public function handle()
    {
        $breyta = new \Breyta\Migrations($this->db, '/path/to/migrations', function($class, ...$args) {
            // return app()->make($class, $args);
            // the closure is optional. default:
            if ($class === \Breyta\AdapterInterface::class) {
                return new \Breyta\BasicAdapter(...$args); // first arg = closure $executor
            }
            return new $class(...$args); // first arg = AdapterInterface $adapter
        });
        
        // register handler (optional)
        /** @var \Breyta\CallbackProgress $callbackProgress */
        $callbackProgress = $breyta->getProgress();
        $callbackProgress->onStart([$this, 'start'])
            ->onBeforeMigration([$this, 'beginMigration'])
            ->onBeforeExecution([$this, 'beforeExecution'])
            ->onAfterExecution([$this, 'afterExecution'])
            ->onAfterMigration([$this, 'finishMigration'])
            ->onFinish([$this, 'finish']);
        
        // alternative: implement \Breyta\ProgressInterface and register
        // $breyta->setProgress($this);
        
        $breyta->migrate();
    }
}
```

Please also have a look at the [reference](reference.md) for a better overview of the api.  
