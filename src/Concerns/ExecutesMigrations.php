<?php

namespace Breyta\Concerns;

use Breyta\AbstractMigration;
use Breyta\AdapterInterface;
use Breyta\FileHelper;
use Breyta\Model\Migration;
use Breyta\Model\Statement;

trait ExecutesMigrations
{


    protected function executeMigration($direction, Migration $migration)
    {
        $this->progress->beforeMigration($migration);
        $this->statements = $direction === 'down' ? $migration->statements : [];
        $start = microtime(true) - $migration->execution_time;
        try {
            // data definition language can usually not run in transactions
            // some database management systems even end transactions when a DDL is executed
            // @todo remove transaction as we can not be sure to run in a transaction
            $this->db->beginTransaction();
            $class = self::internalClass($migration->file) ??
                FileHelper::getClassFromFile($this->path . DIRECTORY_SEPARATOR . $migration->file);
            /** @var AbstractMigration $migrationInstance */
            $migrationInstance = call_user_func($this->resolver, $class, $this->getAdapter());
            call_user_func([$migrationInstance, $direction]);
            $this->saveMigration(
                $migration,
                $direction === 'down' ? 'reverted' : 'done',
                microtime(true) - $start
            );
            $this->db->inTransaction() && $this->db->commit();
        } catch (\PDOException $exception) {
            $this->db->inTransaction() && $this->db->rollBack();
            $direction === 'up' && $this->saveMigration($migration, 'failed', microtime(true) - $start);
            throw $exception;
        }
        $this->progress->afterMigration($migration);
    }

    protected function saveMigration(Migration $migration, $status, $executionTime)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $status === 'reverted' ? $migration->reverted = $now : $migration->executed = $now;

        $migration->statements = $this->statements;
        $migration->status = $status;
        $migration->execution_time = $executionTime;

        $migration->save($this->db, self::$table);
    }

    protected function executeStatement(Statement $statement)
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
                function (Statement $statement) {
                    $this->statements[] = $statement;
                    $this->executeStatement($statement);
                }
            );
        }

        return $this->adapter;
    }
}
