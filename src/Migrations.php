<?php

namespace Breyta;

use Breyta\Migration\CreateMigrationTable;

class Migrations
{
    /** @var \PDO */
    protected $db;

    /** @var string */
    protected $path;

    /** @var array */
    protected $migrations;

    /** @var array */
    protected $classes;

    public function __construct(\PDO $db, string $path)
    {
        if (!file_exists($path) || !is_dir($path)) {
            throw new \InvalidArgumentException('The path to migrations is not valid');
        }

        $this->db = $db;
        $this->path = rtrim($path, '/');
    }

    public function getStatus(): \stdClass
    {
        return (object)[
            'migrations' => $this->getMigrations(),
            'count' => 0,
        ];
    }

    protected function getMigrations(): array
    {
        if (!$this->migrations) {
            $this->migrations = $this->findMigrations();
        }

        return $this->migrations;
    }

    protected function findMigrations(): array
    {
        $this->classes['@breyta/CreateMigrationTable.php'] = CreateMigrationTable::class;
        $migrations = [(object)[
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'new',
        ]];

        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path)) as $fileInfo) {
            if (is_dir($fileInfo->getPathname()) ||
                $fileInfo->getFilename()[0] === '.' ||
                substr($fileInfo->getFilename(), -4) !== '.php'
            ) {
                continue;
            }

            $className = $this->getClassFromFile($fileInfo->getPathname());
            if (!$className) {
                continue;
            }
            require_once $fileInfo->getPathname();
            if (!is_subclass_of($className, AbstractMigration::class)) {
                continue;
            }

            $file = substr($fileInfo->getPathname(), strlen($this->path) + 1);
            $this->classes[$file] = $className;
            $migrations[] = (object)[
                'file' => $file,
                'status' => 'new'
            ];
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
        return $migrations;
    }

    protected function getClassFromFile(string $path): ?string
    {
        $fp = fopen($path, 'r');
        $buffer = '';
        $i = 0;
        $class = $namespace = null;

        while (!$class) {
            if (feof($fp)) {
                return null;
            }

            $buffer .= fread($fp, 512);
            $tokens = token_get_all($buffer);

            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (; $i < count($tokens); $i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j][0] === T_STRING) {
                            $namespace .= '\\' . $tokens[$j][1];
                        } else {
                            if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                                break;
                            }
                        }
                    }
                }

                if ($tokens[$i][0] === T_CLASS) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i+2][1];
                        }
                    }
                }
            }
        }

        return $class ? $namespace . '\\' . $class : null;
    }
}
