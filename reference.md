## API Reference


### Breyta

* [AbstractMigration](#breytaabstractmigration)
* [AdapterInterface](#breytaadapterinterface)
* [BasicAdapter](#breytabasicadapter)
* [CallbackProgress](#breytacallbackprogress)
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

