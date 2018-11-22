<?php

namespace Breyta;

class Migrations
{
    /** @var \PDO */
    protected $db;

    /** @var string */
    protected $path;

    /** @var array */
    protected $migrations;

    public function __construct(\PDO $db, string $path)
    {
        if (!file_exists($path) || !is_dir($path)) {
            throw new \InvalidArgumentException('The path to migrations is not valid');
        }

        $this->db = $db;
        $this->path = $path;
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
        $migrations = [(object)[
            'file' => '@breyta/CreateMigrationTable.php',
            'status' => 'new',
        ]];

        $migrations = array_merge($migrations, array_map(function ($path) {
            return (object)[
                'file' => basename($path),
                'status' => 'new',
            ];
        }, array_filter(scandir($this->path), function ($path) {
            return $path !== '..' && $path !== '.';
        })));

        return $migrations;
    }
}
