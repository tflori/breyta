<?php

namespace Breyta;

use Breyta\Migration\CreateMigrationTable;
use Breyta\Model;

class Migrations
{
    /** @var \PDO */
    protected $db;

    /** @var string */
    protected $path;

    /** @var array|Model\Migration[] */
    protected $migrations;

    /** @var array|String[] */
    protected $classes;

    /** @var array|Model\Migration[] */
    protected $missingMigrations = [];

    /** @var array|Model\Statement[] */
    protected $statements = [];

    /** @var AdapterInterface */
    protected $adapter;

    /** @var callable */
    protected $resolver;

    public function __construct(\PDO $db, string $path, callable $resolver = null)
    {
        if (!file_exists($path) || !is_dir($path)) {
            throw new \InvalidArgumentException('The path to migrations is not valid');
        }

        // force the error mode to exception
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->db = $db;
        $this->path = rtrim($path, '/');

        $this->resolver = $resolver ?? function ($class, ...$args) {
            if ($class === AdapterInterface::class) {
                return new BasicAdapter(...$args);
            }
            return new $class(...$args);
        };
    }

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

    public function migrate(string $file = null): bool
    {
        $status = $this->getStatus();
        $toExecute = array_filter($status->migrations, function (Model\Migration $migration) {
            return $migration->status !== 'done';
        });

        if ($file && !isset($toExecute[$file])) {
            return true; // nothing to migrate?
        } elseif ($file) {
            $toExecute = [$toExecute[$file]];
        }

        /**
         * @var string $file
         * @var Model\Migration $migration
         */
        foreach ($toExecute as $file => $migration) {
            $this->statements = [];
            $start = microtime(true);
            try {
                $this->db->beginTransaction();
                /** @var AbstractMigration $migrationInstance */
                $migrationInstance = call_user_func($this->resolver, $this->classes[$file], $this->getAdapter());
                $migrationInstance->up();

                $this->saveMigration($migration, 'done', microtime(true) - $start);
                $this->db->commit();
            } catch (\Exception $exception) {
                $this->db->rollBack();
                $this->saveMigration($migration, 'failed', microtime(true) - $start);
                return false;
            }
        }

        return true;
    }

    protected function saveMigration(Model\Migration $migration, $status, $executionTime)
    {
        // delete it first (if there is an old line it is outdated)
        $this->db->prepare("DELETE FROM migrations WHERE file = ?")->execute([$migration->file]);

        $migration->executed = new \DateTime('now', new \DateTimeZone('UTC'));
        $migration->statements = $this->statements;
        $migration->status = $status;
        $migration->executionTime = $executionTime;

        $this->db->prepare("INSERT INTO migrations
            (file, executed, status, statements, executionTime) VALUES
            (?, ?, ?, ?, ?)
        ")->execute([
            $migration->file,
            $migration->executed->format('c'),
            $migration->status,
            json_encode($migration->statements),
            $migration->executionTime
        ]);
    }

    protected function loadMigrations(): array
    {
        if (!$this->migrations) {
            $this->migrations = $this->findMigrations();

            // get the status of migrations from database
            try {
                $statement = $this->db->query('SELECT * FROM migrations');
                if ($statement) {
                    $statement->setFetchMode(\PDO::FETCH_CLASS, Model\Migration::class);
                    while ($migration = $statement->fetch()) {
                        if (!isset($this->migrations[$migration->file])) {
                            $this->missingMigrations[] = $migration;
                            continue;
                        }
                        $this->migrations[$migration->file] = $migration;
                    }
                }
            } catch (\PDOException $exception) {
                // the table does not exist - so nothing to do here
            }
        }

        return $this->migrations;
    }

    protected function findMigrations(): array
    {
        $this->classes['@breyta/CreateMigrationTable.php'] = CreateMigrationTable::class;
        $migrations = [Model\Migration::createInstance([
            'file' => '@breyta/CreateMigrationTable.php',
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
            $this->classes[$file] = $className;
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
            $leftHasCreationDate = (int)preg_match(
                '/^(\d{4}-\d{2}-\d{2}T\d{2}-\d{2}-\d{2}Z)_/',
                $leftBaseName,
                $leftCreationDate
            );
            $rightHasCreationDate = (int)preg_match(
                '/^(\d{4}-\d{2}-\d{2}T\d{2}-\d{2}-\d{2}Z)_/',
                $rightBaseName,
                $rightCreationDate
            );
            if ($leftHasCreationDate !== $rightHasCreationDate) {
                return $leftHasCreationDate - $rightHasCreationDate;
            }

            // sort criteria 3: by creation date
            if (@$leftCreationDate[1] !== @$rightCreationDate[1]) {
                list($leftDate, $leftTime) = explode('T', $leftCreationDate[1]);
                list($rightDate, $rightTime) = explode('T', $rightCreationDate[1]);
                list($leftTime, $rightTime) = str_replace('-', ':', [$leftTime, $rightTime]);
                return strtotime($leftDate . 'T' . $leftTime) - strtotime($rightDate . 'T' . $rightTime);
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
        $start = microtime(true);
        try {
            $statement->result = $this->db->exec($statement);
            $statement->exception = null;
        } catch (\PDOException $exception) {
            $statement->exception = $exception;
            throw $exception;
        } finally {
            $statement->executionTime = microtime(true) - $start;
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
}
