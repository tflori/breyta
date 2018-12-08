<?php

namespace Breyta;

use Breyta\Migration\CreateMigrationTable;
use Breyta\Model;

/**
 * Class Migrations
 *
 * The migration engine that puts all parts together.
 *
 * @package Breyta
 * @author Thomas Flori <thflori@gmail.com>
 */
class Migrations
{
    const INTERNAL_PREFIX = '@breyta/';

    /** The name of the migration table
     * @var string */
    public static $table = 'migrations';

    /** The path to the template for migrations
     * @var string */
    public static $templatePath = __DIR__ . '/../resources/MigrationTemplate.php';

    /** @var \PDO */
    protected $db;

    /** @var string */
    protected $path;

    /** @var array|Model\Migration[] */
    protected $migrations;

    /** @var array|Model\Migration[] */
    protected $missingMigrations = [];

    /** @var array|Model\Statement[] */
    protected $statements = [];

    /** @var AdapterInterface */
    protected $adapter;

    /** @var callable */
    protected $resolver;

    /** @var ProgressInterface */
    protected $progress;

    public function __construct(\PDO $db, string $path, callable $resolver = null, ProgressInterface $progress = null)
    {
        if (!file_exists($path) || !is_dir($path)) {
            throw new \InvalidArgumentException('The path to migrations is not valid');
        }

        // force the error mode to exception
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->db = $db;
        $this->path = rtrim($path, '/');

        /** @codeCoverageIgnore the default resolver is a) trivial and b) not testable */
        $this->resolver = $resolver ?? function ($class, ...$args) {
            if ($class === AdapterInterface::class) {
                return new BasicAdapter(...$args);
            }
            return new $class(...$args);
        };

        $this->progress = $progress ?? new CallbackProgress();
    }

    /**
     * Returns the status of the migrations
     *
     * It contains an array of all migrations, the count of migrations that are not migrated yet and an array of
     * migrations that got removed (if files where removed).
     *
     * @return \stdClass
     */
    public function getStatus(): \stdClass
    {
        $this->loadMigrations();

        $status = (object)[
            'migrations' => $this->migrations,
            'count' => count(array_filter($this->migrations, function ($migration) {
                return $migration->status !== 'done';
            })),
        ];

        if (count($this->missingMigrations)) {
            $status->missing = $this->missingMigrations;
        }

        return $status;
    }

    /**
     * Migrate all migrations that are not migrated yet
     *
     * @return bool
     */
    public function migrate(): bool
    {
        $this->loadMigrations();

        /** @var Model\Migration[] $migrations */
        $migrations = array_filter($this->migrations, function (Model\Migration $migration) {
            return $migration->status !== 'done';
        });

        return $this->up(...$migrations);
    }

    /**
     * Migrate all migrations to a specific migration or date time
     *
     * $file can either be a relative file name (or a portion matched with `strpos()`) or a date time string to execute
     * all migrations to that time.
     *
     * @param string $file
     * @return bool
     */
    public function migrateTo(string $file)
    {
        $this->loadMigrations();

        $found = false;
        $migrations = [];
        foreach ($this->migrations as $migration) {
            $migrations[] = $migration;
            if (strpos($migration->file, $file) !== false) {
                $found = true;
                break;
            }
        }

        if (!$found && $time = strtotime($file)) {
            $migrations = array_filter($this->migrations, function (Model\Migration $migration) use ($time) {
                $migrationTime = FileHelper::getTimeFromFileName($migration->file);
                return is_null($migrationTime) || $migrationTime <= $time;
            });
        } elseif (!$found) {
            throw new \LogicException('No migration found matching ' . $file);
        }

        /** @var Model\Migration[] $migrations */
        $migrations = array_filter($migrations, function (Model\Migration $migration) {
            return $migration->status !== 'done';
        });

        return $this->up(...$migrations);
    }

    /**
     * Migrate specific migrations
     *
     * @param Model\Migration ...$migrations
     * @return bool
     */
    public function up(Model\Migration ...$migrations)
    {
        $started = microtime(true);
        $this->progress->start((object)[
            'migrations' => $this->migrations,
            'task' => 'migrate',
            'count' => count($migrations),
            'toExecute' => $migrations,
        ]);

        foreach ($migrations as $migration) {
            $this->progress->beforeMigration($migration);
            $this->statements = [];
            $start = microtime(true);
            try {
                $this->db->beginTransaction();
                $class = self::internalClass($migration->file) ??
                         FileHelper::getClassFromFile($this->path . DIRECTORY_SEPARATOR . $migration->file);
                /** @var AbstractMigration $migrationInstance */
                $migrationInstance = call_user_func($this->resolver, $class, $this->getAdapter());
                $migrationInstance->up();
                $this->saveMigration($migration, 'done', microtime(true) - $start);
                $this->db->commit();
            } catch (\PDOException $exception) {
                $this->db->rollBack();
                $this->saveMigration($migration, 'failed', microtime(true) - $start);
                throw $exception;
            }
            $this->progress->afterMigration($migration);
        }

        $this->progress->finish((object)[
            'migrations' => $this->migrations,
            'task' => 'migrate',
            'count' => count($migrations),
            'executed' => $migrations,
            'executionTime' => microtime(true) - $started
        ]);
        return true;
    }

    /**
     * Revert all migrations that have been migrated
     *
     * @return bool
     */
    public function revert()
    {
        $this->loadMigrations();

        /** @var Model\Migration[] $migrations */
        $migrations = array_filter($this->migrations, function (Model\Migration $migration) {
            return $migration->status === 'done' && !self::isInternal($migration->file);
        });

        return $this->down(...array_reverse($migrations));
    }

    /**
     * Revert all migrations to a specific migration or date time
     *
     * $file can either be a relative file name (or a portion matched with `strpos()`) or a date time string to execute
     * all migrations to that time.
     *
     * **Note:** This will not revert the migration matched the pattern. It is resetting to the state of the database
     * to the state when <file> was executed.
     *
     * @param string $file
     * @return bool
     */
    public function revertTo(string $file)
    {
        $this->loadMigrations();

        $found = false;
        $migrations = [];
        foreach (array_reverse($this->migrations) as $migration) {
            if (strpos($migration->file, $file) !== false) {
                $found = true;
                break;
            }
            $migrations[] = $migration;
        }

        if (!$found && $time = strtotime($file)) {
            $migrations = array_reverse(
                array_filter($this->migrations, function (Model\Migration $migration) use ($time) {
                    $migrationTime = FileHelper::getTimeFromFileName($migration->file);
                    return !is_null($migrationTime) && $migrationTime > $time;
                })
            );
        } elseif (!$found) {
            throw new \LogicException('No migration found matching ' . $file);
        }

        /** @var Model\Migration[] $toExecute */
        $migrations = array_filter($migrations, function (Model\Migration $migration) {
            return $migration->status === 'done' && !self::isInternal($migration->file);
        });

        return $this->down(...$migrations);
    }

    /**
     * Revert specific migrations
     *
     * @param Model\Migration ...$migrations
     * @return bool
     */
    public function down(Model\Migration ...$migrations)
    {
        $started = microtime(true);
        $this->progress->start((object)[
            'migrations' => $this->migrations,
            'task' => 'revert',
            'count' => count($migrations),
            'toExecute' => $migrations,
        ]);

        foreach ($migrations as $migration) {
            $this->progress->beforeMigration($migration);
            $this->statements = $migration->statements;
            $start = microtime(true) - $migration->execution_time;
            try {
                $this->db->beginTransaction();
                $class = self::internalClass($migration->file) ??
                         FileHelper::getClassFromFile($this->path . DIRECTORY_SEPARATOR . $migration->file);
                /** @var AbstractMigration $migrationInstance */
                $migrationInstance = call_user_func($this->resolver, $class, $this->getAdapter());
                $migrationInstance->down();
                $this->saveMigration($migration, 'reverted', microtime(true) - $start);
                $this->db->commit();
            } catch (\PDOException $exception) {
                $this->db->rollBack();
                throw $exception;
            }
            $this->progress->afterMigration($migration);
        }

        $this->progress->finish((object)[
            'migrations' => $this->migrations,
            'task' => 'revert',
            'count' => count($migrations),
            'executed' => $migrations,
            'executionTime' => microtime(true) - $started
        ]);
        return true;
    }

    /**
     * Creates a migration
     *
     * We recommend StudlyCase naming for PSR2 compatibility. Also the files will get a namespace.
     *
     * Returns the path to the generated migration.
     *
     * @param string $name
     * @return string
     */
    public function createMigration(string $name): string
    {
        static $template;
        if (is_null($template)) {
            $template = file_get_contents(self::$templatePath);
        }

        $path = explode('/', $name);
        $name = array_pop($path);
        $fileName = implode('/', array_merge($path, [date('Y-m-d\TH.i.s\Z') . '_' . $name . '.php']));
        $className = $name;
        $namespace = implode('\\', array_merge(['Migration'], $path));
        $fullPath = $this->path . DIRECTORY_SEPARATOR . $fileName;

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), umask() ^ 0777, true);
        }

        file_put_contents($fullPath, strtr($template, [
            'NAMESPACE_NAME' => $namespace,
            'CLASS_NAME' => $className,
            'FILE_NAME' => $fileName,
        ]));
        return $fullPath;
    }

    /** @codeCoverageIgnore */
    public function getProgress(): ProgressInterface
    {
        return $this->progress;
    }

    /** @codeCoverageIgnore */
    public function setProgress(ProgressInterface $progress)
    {
        $this->progress = $progress;
    }

    protected function saveMigration(Model\Migration $migration, $status, $executionTime)
    {
        $exists = (bool)$migration->executed;
        $table = self::$table;
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $status === 'reverted' ? $migration->reverted = $now : $migration->executed = $now;

        $migration->statements = $this->statements;
        $migration->status = $status;
        $migration->execution_time = $executionTime;

        if (!$exists) {
            $this->db->prepare("INSERT INTO {$table}
                (file, executed, status, statements, executionTime) VALUES
                (?, ?, ?, ?, ?)
            ")->execute([
                $migration->file,
                $migration->executed->format('c'),
                $migration->status,
                json_encode($migration->statements),
                $migration->execution_time
            ]);
        } else {
            $this->db->prepare("UPDATE {$table} SET
                executed = ?, reverted = ?, status = ?, statements = ?, executionTime = ?
                WHERE file = ?
            ")->execute([
                $migration->executed->format('c'),
                $migration->reverted ? $migration->reverted->format('c') : null,
                $migration->status,
                json_encode($migration->statements),
                $migration->execution_time,
                $migration->file
            ]);
        }
    }

    protected function loadMigrations()
    {
        if (!$this->migrations) {
            $migrations = $this->findMigrations();

            // get the status of migrations from database
            try {
                $statement = $this->db->query('SELECT * FROM migrations');
                if ($statement) {
                    $statement->setFetchMode(\PDO::FETCH_CLASS, Model\Migration::class);
                    while ($migration = $statement->fetch()) {
                        if (!isset($migrations[$migration->file])) {
                            $this->missingMigrations[] = $migration;
                            continue;
                        }
                        $migrations[$migration->file] = $migration;
                    }
                }
            } catch (\PDOException $exception) {
                // the table does not exist - so nothing to do here
            }
            $this->migrations = array_values($migrations);
        }
    }

    protected function findMigrations(): array
    {
        $migrations = [Model\Migration::createInstance([
            'file' => self::INTERNAL_PREFIX . 'CreateMigrationTable.php',
            'status' => 'new',
        ])];

        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path)) as $fileInfo) {
            if (is_dir($fileInfo->getPathname()) ||
                $fileInfo->getFilename()[0] === '.' ||
                substr($fileInfo->getFilename(), -4) !== '.php'
            ) {
                continue;
            }

            $className = FileHelper::getClassFromFile($fileInfo->getPathname());
            if (!$className) {
                continue;
            }

            require_once $fileInfo->getPathname();
            if (!is_subclass_of($className, AbstractMigration::class)) {
                continue;
            }

            $file = substr($fileInfo->getPathname(), strlen($this->path) + 1);
            $migrations[] = Model\Migration::createInstance([
                'file' => $file,
                'status' => 'new'
            ]);
        }

        usort($migrations, function ($left, $right) {
            // sort criteria 1: is from breyta
            $leftIsFromBreyta = substr($left->file, 0, 8) === '@breyta/';
            $rightIsFromBreyta = substr($right->file, 0, 8) === '@breyta/';
            if ($leftIsFromBreyta !== $rightIsFromBreyta) {
                return $rightIsFromBreyta - $leftIsFromBreyta;
            }

            $leftBaseName = basename($left->file);
            $rightBaseName = basename($right->file);

            // sort criteria 2: has creation date
            $leftTime = FileHelper::getTimeFromFileName($left->file);
            $rightTime = FileHelper::getTimeFromFileName($right->file);
            if (is_null($leftTime) !== is_null($rightTime)) {
                return is_null($rightTime) - is_null($leftTime);
            }

            // sort criteria 3: by creation date
            if ($leftTime !== $rightTime) {
                return $leftTime - $rightTime;
            }

            // sort criteria 4: alphabetically
            return strcmp($leftBaseName, $rightBaseName);
        });

        // key by identifier...
        $migrations = array_combine(array_map(function ($migration) {
            return $migration->file;
        }, $migrations), $migrations);

        return $migrations;
    }

    protected function executeStatement(Model\Statement $statement)
    {
        $this->progress->beforeExecution($statement);
        $start = microtime(true);
        try {
            $statement->result = $this->db->exec($statement->raw);
            $statement->exception = null;
        } catch (\PDOException $exception) {
            $statement->exception = $exception;
            throw $exception;
        } finally {
            $statement->executionTime = microtime(true) - $start;
            $this->progress->afterExecution($statement);
        }
    }

    protected function getAdapter(): AdapterInterface
    {
        if (!$this->adapter) {
            $this->adapter = call_user_func(
                $this->resolver,
                AdapterInterface::class,
                function (Model\Statement $statement) {
                     $this->statements[] = $statement;
                    $this->executeStatement($statement);
                }
            );
        }

        return $this->adapter;
    }

    protected static function internalClass(string $file): ?string
    {
        return self::isInternal($file) ? 'Breyta\\Migration\\' . substr($file, 8, -4) : null;
    }

    protected static function isInternal(string $file): bool
    {
        return strncmp($file, self::INTERNAL_PREFIX, strlen(self::INTERNAL_PREFIX)) === 0;
    }
}
