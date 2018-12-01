## API Reference


### Breyta

* [AbstractMigration](#breytaabstractmigration)
* [AdapterInterface](#breytaadapterinterface)
* [BasicAdapter](#breytabasicadapter)
* [CallbackProgress](#breytacallbackprogress)
* [Migrations](#breytamigrations)
* [ProgressInterface](#breytaprogressinterface)


### Breyta\Migration

* [CreateMigrationTable](#breytamigrationcreatemigrationtable)


### Breyta\Model

* [Migration](#breytamodelmigration)
* [Statement](#breytamodelstatement)


---

### Breyta\AdapterInterface



#### Interface AdapterInterface

You may want to define an adapter with additional helpers like creating tables etc. The only adapter provided in this
library is a BasicAdapter that just executes sql statements.






#### Methods

* [__construct](#breytaadapterinterface__construct) Adapter gets a callable $executor
* [exec](#breytaadapterinterfaceexec) Execute an sql statement

#### Breyta\AdapterInterface::__construct

```php
public function __construct( callable $executor ): AdapterInterface
```

##### Adapter gets a callable $executor

The executor requires a Breyta\Model\Statement argument and is the only way an adapter can interact with
the database.

**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$executor` | **callable**  |  |



#### Breyta\AdapterInterface::exec

```php
public function exec( string $sql ): mixed
```

##### Execute an sql statement

Returns false on error and an integer of affected rows on success.

**Visibility:** this method is **public**.
<br />
 **Returns**: this method returns **mixed**
<br />

##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sql` | **string**  |  |



**See Also:**

* http://php.net/manual/en/pdo.exec.php - for a details about the return statement


---

### Breyta\ProgressInterface











#### Methods

* [afterExecution](#breytaprogressinterfaceafterexecution) Output information about the $statement (after it gets executed)
* [afterMigration](#breytaprogressinterfaceaftermigration) Output information about the $migration (after the migration)
* [beforeExecution](#breytaprogressinterfacebeforeexecution) Output information about the $statement (before it gets executed)
* [beforeMigration](#breytaprogressinterfacebeforemigration) Output information about the $migration (before the migration)
* [finish](#breytaprogressinterfacefinish) Output information about what just happened
* [start](#breytaprogressinterfacestart) Output information about starting the migration process

#### Breyta\ProgressInterface::afterExecution

```php
public function afterExecution( \Breyta\Model\Statement $execution )
```

##### Output information about the $statement (after it gets executed)



**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$execution` | **Model\Statement**  |  |



#### Breyta\ProgressInterface::afterMigration

```php
public function afterMigration( \Breyta\Model\Migration $migration )
```

##### Output information about the $migration (after the migration)



**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$migration` | **Model\Migration**  |  |



#### Breyta\ProgressInterface::beforeExecution

```php
public function beforeExecution( \Breyta\Model\Statement $execution )
```

##### Output information about the $statement (before it gets executed)



**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$execution` | **Model\Statement**  |  |



#### Breyta\ProgressInterface::beforeMigration

```php
public function beforeMigration( \Breyta\Model\Migration $migration )
```

##### Output information about the $migration (before the migration)



**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$migration` | **Model\Migration**  |  |



#### Breyta\ProgressInterface::finish

```php
public function finish( \stdClass $info )
```

##### Output information about what just happened

Info contains:
 - `migrations` - an array of Breyta\Model\Migration
 - `task` - the task that is going to be executed (migrate or revert)
 - `count` - an integer how many migrations are going to be executed
 - `executed` - an array of migrations that just got executed

**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$info` | **\stdClass**  |  |



#### Breyta\ProgressInterface::start

```php
public function start( \stdClass $info )
```

##### Output information about starting the migration process

Info contains:
 - `migrations` - an array of Breyta\Model\Migration
 - `task` - the task that is going to be executed (migrate or revert)
 - `count` - an integer how many migrations are going to be executed
 - `toExecute` - an array of migrations that are going to be executed

**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$info` | **\stdClass**  |  |





---

### Breyta\Model\Migration









#### Properties

| Visibility | Name | Type | Description                           |
|------------|------|------|---------------------------------------|
| **public** | `$file` | **string** |  |
| **public** | `$executed` | ** \ DateTime** |  |
| **public** | `$reverted` | ** \ DateTime** |  |
| **public** | `$status` | **string** |  |
| **public** | `$statements` | **string &#124; array &#124; array&lt;Statement>** |  |
| **public** | `$executionTime` | **double** |  |



#### Methods

* [__construct](#breytamodelmigration__construct) 
* [createInstance](#breytamodelmigrationcreateinstance) 

#### Breyta\Model\Migration::__construct

```php
public function __construct(): Migration
```




**Visibility:** this method is **public**.
<br />




#### Breyta\Model\Migration::createInstance

```php
public static function createInstance( array $data = array() )
```




**Static:** this method is **static**.
<br />**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array**  |  |





---

### Breyta\Model\Statement









#### Properties

| Visibility | Name | Type | Description                           |
|------------|------|------|---------------------------------------|
| **public** | `$raw` | **string** |  |
| **public** | `$teaser` | **string** |  |
| **public** | `$action` | **string** |  |
| **public** | `$type` | **string** |  |
| **public** | `$name` | **string** |  |
| **public** | `$result` | **mixed** |  |
| **public** | `$executionTime` | **double** |  |
| **public** | `$exception` | ** \ PDOException** |  |



#### Methods

* [__toString](#breytamodelstatement__tostring) 
* [createInstance](#breytamodelstatementcreateinstance) 

#### Breyta\Model\Statement::__toString

```php
public function __toString()
```




**Visibility:** this method is **public**.
<br />




#### Breyta\Model\Statement::createInstance

```php
public static function createInstance( array $data = array() )
```




**Static:** this method is **static**.
<br />**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array**  |  |





---

### Breyta\Migrations




The migration engine that puts all parts together.



#### Constants

| Name | Value |
|------|-------|
| INTERNAL_PREFIX | `'@breyta/'` |


#### Properties

| Visibility | Name | Type | Description                           |
|------------|------|------|---------------------------------------|
| **public static** | `$table` | **string** | The name of the migration table |
| **public static** | `$templatePath` | **string** | The path to the template for migrations |
| **protected** | `$db` | ** \ PDO** |  |
| **protected** | `$path` | **string** |  |
| **protected** | `$migrations` | **array &#124; array&lt;Model \ Migration>** |  |
| **protected** | `$missingMigrations` | **array &#124; array&lt;Model \ Migration>** |  |
| **protected** | `$statements` | **array &#124; array&lt;Model \ Statement>** |  |
| **protected** | `$adapter` | **AdapterInterface** |  |
| **protected** | `$resolver` | **callable** |  |
| **protected** | `$progress` | **ProgressInterface** |  |



#### Methods

* [__construct](#breytamigrations__construct) 
* [createMigration](#breytamigrationscreatemigration) Creates a migration
* [down](#breytamigrationsdown) Revert specific migrations
* [executeStatement](#breytamigrationsexecutestatement) 
* [findMigrations](#breytamigrationsfindmigrations) 
* [getAdapter](#breytamigrationsgetadapter) 
* [getProgress](#breytamigrationsgetprogress) 
* [getStatus](#breytamigrationsgetstatus) Returns the status of the migrations
* [internalClass](#breytamigrationsinternalclass) 
* [isInternal](#breytamigrationsisinternal) 
* [loadMigrations](#breytamigrationsloadmigrations) 
* [migrate](#breytamigrationsmigrate) Migrate all migrations that are not migrated yet
* [migrateTo](#breytamigrationsmigrateto) Migrate all migrations to a specific migration or date time
* [revert](#breytamigrationsrevert) Revert all migrations that have been migrated
* [revertTo](#breytamigrationsrevertto) Revert all migrations to a specific migration or date time
* [saveMigration](#breytamigrationssavemigration) 
* [setProgress](#breytamigrationssetprogress) 
* [up](#breytamigrationsup) Migrate specific migrations

#### Breyta\Migrations::__construct

```php
public function __construct(
    \PDO $db, \Breyta\string $path, callable $resolver = null, 
    \Breyta\ProgressInterface $progress = null
): Migrations
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db` | **\PDO**  |  |
| `$path` | **string**  |  |
| `$resolver` | **callable**  |  |
| `$progress` | **ProgressInterface**  |  |



#### Breyta\Migrations::createMigration

```php
public function createMigration( string $name ): boolean
```

##### Creates a migration

We recommend StudlyCase naming for PSR2 compatibility. Also the files will get a namespace.

**Visibility:** this method is **public**.
<br />
 **Returns**: this method returns **boolean**
<br />

##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string**  |  |



#### Breyta\Migrations::down

```php
public function down( \Breyta\Model\Migration $migrations ): boolean
```

##### Revert specific migrations



**Visibility:** this method is **public**.
<br />
 **Returns**: this method returns **boolean**
<br />

##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$migrations` | **Model\Migration**  |  |



#### Breyta\Migrations::executeStatement

```php
protected function executeStatement( \Breyta\Model\Statement $statement )
```




**Visibility:** this method is **protected**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$statement` | **Model\Statement**  |  |



#### Breyta\Migrations::findMigrations

```php
protected function findMigrations()
```




**Visibility:** this method is **protected**.
<br />




#### Breyta\Migrations::getAdapter

```php
protected function getAdapter()
```




**Visibility:** this method is **protected**.
<br />




#### Breyta\Migrations::getProgress

```php
public function getProgress()
```




**Visibility:** this method is **public**.
<br />




#### Breyta\Migrations::getStatus

```php
public function getStatus(): \stdClass
```

##### Returns the status of the migrations

It contains an array of all migrations, the count of migrations that are not migrated yet and an array of
migrations that got removed (if files where removed).

**Visibility:** this method is **public**.
<br />
 **Returns**: this method returns **\stdClass**
<br />



#### Breyta\Migrations::internalClass

```php
protected static function internalClass( \Breyta\string $file )
```




**Static:** this method is **static**.
<br />**Visibility:** this method is **protected**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **string**  |  |



#### Breyta\Migrations::isInternal

```php
protected static function isInternal( \Breyta\string $file )
```




**Static:** this method is **static**.
<br />**Visibility:** this method is **protected**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **string**  |  |



#### Breyta\Migrations::loadMigrations

```php
protected function loadMigrations()
```




**Visibility:** this method is **protected**.
<br />




#### Breyta\Migrations::migrate

```php
public function migrate(): boolean
```

##### Migrate all migrations that are not migrated yet



**Visibility:** this method is **public**.
<br />
 **Returns**: this method returns **boolean**
<br />



#### Breyta\Migrations::migrateTo

```php
public function migrateTo( string $file ): boolean
```

##### Migrate all migrations to a specific migration or date time

$file can either be a relative file name (or a portion matched with `strpos()`) or a date time string to execute
all migrations to that time.

**Visibility:** this method is **public**.
<br />
 **Returns**: this method returns **boolean**
<br />

##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **string**  |  |



#### Breyta\Migrations::revert

```php
public function revert(): boolean
```

##### Revert all migrations that have been migrated



**Visibility:** this method is **public**.
<br />
 **Returns**: this method returns **boolean**
<br />



#### Breyta\Migrations::revertTo

```php
public function revertTo( string $file ): boolean
```

##### Revert all migrations to a specific migration or date time

$file can either be a relative file name (or a portion matched with `strpos()`) or a date time string to execute
all migrations to that time.

**Note:** This will not revert the migration matched the pattern. It is resetting to the state of the database
to the state when <file> was executed.

**Visibility:** this method is **public**.
<br />
 **Returns**: this method returns **boolean**
<br />

##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **string**  |  |



#### Breyta\Migrations::saveMigration

```php
protected function saveMigration(
    \Breyta\Model\Migration $migration, $status, $executionTime
)
```




**Visibility:** this method is **protected**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$migration` | **Model\Migration**  |  |
| `$status` |   |  |
| `$executionTime` |   |  |



#### Breyta\Migrations::setProgress

```php
public function setProgress( \Breyta\ProgressInterface $progress )
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$progress` | **ProgressInterface**  |  |



#### Breyta\Migrations::up

```php
public function up( \Breyta\Model\Migration $migrations ): boolean
```

##### Migrate specific migrations



**Visibility:** this method is **public**.
<br />
 **Returns**: this method returns **boolean**
<br />

##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$migrations` | **Model\Migration**  |  |





---

### Breyta\AbstractMigration









#### Properties

| Visibility | Name | Type | Description                           |
|------------|------|------|---------------------------------------|
| **private** | `$adapter` |  |  |



#### Methods

* [__call](#breytaabstractmigration__call) 
* [__construct](#breytaabstractmigration__construct) 
* [down](#breytaabstractmigrationdown) Bring the migration down
* [up](#breytaabstractmigrationup) Bring the migration up

#### Breyta\AbstractMigration::__call

```php
public function __call( $method, $args )
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$method` |   |  |
| `$args` |   |  |



#### Breyta\AbstractMigration::__construct

```php
public function __construct(
    \Breyta\AdapterInterface $adapter
): AbstractMigration
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$adapter` | **AdapterInterface**  |  |



#### Breyta\AbstractMigration::down

```php
abstract public function down()
```

##### Bring the migration down



**Visibility:** this method is **public**.
<br />




#### Breyta\AbstractMigration::up

```php
abstract public function up()
```

##### Bring the migration up



**Visibility:** this method is **public**.
<br />






---

### Breyta\CallbackProgress


**Implements:** [Breyta\ProgressInterface](#breytaprogressinterface)


Stores callbacks for migration progress.




#### Properties

| Visibility | Name | Type | Description                           |
|------------|------|------|---------------------------------------|
| **protected** | `$startCallback` | **callable** |  |
| **protected** | `$beforeMigrationCallback` | **callable** |  |
| **protected** | `$beforeExecutionCallback` | **callable** |  |
| **protected** | `$afterExecutionCallback` | **callable** |  |
| **protected** | `$afterMigrationCallback` | **callable** |  |
| **protected** | `$finishCallback` | **callable** |  |



#### Methods

* [afterExecution](#breytacallbackprogressafterexecution) Output information about the $statement (after it gets executed)
* [afterMigration](#breytacallbackprogressaftermigration) Output information about the $migration (after the migration)
* [beforeExecution](#breytacallbackprogressbeforeexecution) Output information about the $statement (before it gets executed)
* [beforeMigration](#breytacallbackprogressbeforemigration) Output information about the $migration (before the migration)
* [finish](#breytacallbackprogressfinish) Output information about what just happened
* [onAfterExecution](#breytacallbackprogressonafterexecution) 
* [onAfterMigration](#breytacallbackprogressonaftermigration) 
* [onBeforeExecution](#breytacallbackprogressonbeforeexecution) 
* [onBeforeMigration](#breytacallbackprogressonbeforemigration) 
* [onFinish](#breytacallbackprogressonfinish) 
* [onStart](#breytacallbackprogressonstart) 
* [start](#breytacallbackprogressstart) Output information about starting the migration process

#### Breyta\CallbackProgress::afterExecution

```php
public function afterExecution( \Breyta\Model\Statement $execution )
```

##### Output information about the $statement (after it gets executed)



**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$execution` | **Model\Statement**  |  |



#### Breyta\CallbackProgress::afterMigration

```php
public function afterMigration( \Breyta\Model\Migration $migration )
```

##### Output information about the $migration (after the migration)



**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$migration` | **Model\Migration**  |  |



#### Breyta\CallbackProgress::beforeExecution

```php
public function beforeExecution( \Breyta\Model\Statement $execution )
```

##### Output information about the $statement (before it gets executed)



**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$execution` | **Model\Statement**  |  |



#### Breyta\CallbackProgress::beforeMigration

```php
public function beforeMigration( \Breyta\Model\Migration $migration )
```

##### Output information about the $migration (before the migration)



**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$migration` | **Model\Migration**  |  |



#### Breyta\CallbackProgress::finish

```php
public function finish( \stdClass $info )
```

##### Output information about what just happened

Info contains:
 - `migrations` - an array of Breyta\Model\Migration
 - `executed` - an array of migrations that just got executed

**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$info` | **\stdClass**  |  |



#### Breyta\CallbackProgress::onAfterExecution

```php
public function onAfterExecution( callable $callback )
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$callback` | **callable**  |  |



#### Breyta\CallbackProgress::onAfterMigration

```php
public function onAfterMigration( callable $callback )
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$callback` | **callable**  |  |



#### Breyta\CallbackProgress::onBeforeExecution

```php
public function onBeforeExecution( callable $callback )
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$callback` | **callable**  |  |



#### Breyta\CallbackProgress::onBeforeMigration

```php
public function onBeforeMigration( callable $callback )
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$callback` | **callable**  |  |



#### Breyta\CallbackProgress::onFinish

```php
public function onFinish( callable $callback )
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$callback` | **callable**  |  |



#### Breyta\CallbackProgress::onStart

```php
public function onStart( callable $callback )
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$callback` | **callable**  |  |



#### Breyta\CallbackProgress::start

```php
public function start( \stdClass $info )
```

##### Output information about starting the migration process

Info contains:
 - `migrations` - an array of Breyta\Model\Migration
 - `count` - an integer how many migrations are going to be executed

**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$info` | **\stdClass**  |  |





---

### Breyta\BasicAdapter


**Implements:** [Breyta\AdapterInterface](#breytaadapterinterface)







#### Properties

| Visibility | Name | Type | Description                           |
|------------|------|------|---------------------------------------|
| **protected** | `$executor` | **callable** |  |



#### Methods

* [__construct](#breytabasicadapter__construct) Adapter gets a callable $executor
* [exec](#breytabasicadapterexec) Execute an sql statement
* [getStatement](#breytabasicadaptergetstatement) 

#### Breyta\BasicAdapter::__construct

```php
public function __construct( callable $executor ): BasicAdapter
```

##### Adapter gets a callable $executor

The executor requires a Breyta\Model\Statement argument and is the only way an adapter can interact with
the database.

**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$executor` | **callable**  |  |



#### Breyta\BasicAdapter::exec

```php
public function exec( \Breyta\string $sql ): mixed
```

##### Execute an sql statement

Returns false on error and an integer of affected rows on success.

**Visibility:** this method is **public**.
<br />
 **Returns**: this method returns **mixed**
<br />

##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sql` | **string**  |  |



#### Breyta\BasicAdapter::getStatement

```php
public function getStatement( \Breyta\string $sql )
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sql` | **string**  |  |





---

### Breyta\Migration\CreateMigrationTable

**Extends:** [Breyta\AbstractMigration](#breytaabstractmigration)








#### Properties

| Visibility | Name | Type | Description                           |
|------------|------|------|---------------------------------------|
| **private** | `$adapter` |  |  |



#### Methods

* [__call](#breytamigrationcreatemigrationtable__call) 
* [__construct](#breytamigrationcreatemigrationtable__construct) 
* [down](#breytamigrationcreatemigrationtabledown) Bring the migration down
* [up](#breytamigrationcreatemigrationtableup) Bring the migration up

#### Breyta\Migration\CreateMigrationTable::__call

```php
public function __call( $method, $args )
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$method` |   |  |
| `$args` |   |  |



#### Breyta\Migration\CreateMigrationTable::__construct

```php
public function __construct(
    \Breyta\AdapterInterface $adapter
): AbstractMigration
```




**Visibility:** this method is **public**.
<br />


##### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$adapter` | **\Breyta\AdapterInterface**  |  |



#### Breyta\Migration\CreateMigrationTable::down

```php
public function down()
```

##### Bring the migration down



**Visibility:** this method is **public**.
<br />




#### Breyta\Migration\CreateMigrationTable::up

```php
public function up()
```

##### Bring the migration up



**Visibility:** this method is **public**.
<br />






---

