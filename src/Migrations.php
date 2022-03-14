<?php

namespace Breyta;

use Breyta\Concerns\ExecutesMigrations;
use Breyta\Concerns\SearchesForMigrations;
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
    use SearchesForMigrations;
    use ExecutesMigrations;

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
            $this->executeMigration('up', $migration);
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
            $this->executeMigration('down', $migration);
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

    protected function loadMigrations()
    {
        if ($this->migrations) {
            return;
        }
        $migrations = $this->findMigrations();

        // get the status of migrations from database
        try {
            $statement = $this->db->query('SELECT * FROM migrations');
            $statement->setFetchMode(\PDO::FETCH_CLASS, Model\Migration::class);
            while ($migration = $statement->fetch()) {
                isset($migrations[$migration->file]) ?
                    $migrations[$migration->file] = $migration :
                    $this->missingMigrations[] = $migration;
            }
        } catch (\PDOException $exception) {
            // the table does not exist - so nothing to do here
        }

        $this->migrations = array_values($migrations);
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
