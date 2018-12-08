<?php

namespace Breyta\Model;

use Breyta\AbstractMigration;
use Breyta\AdapterInterface;

class Migration
{
    /** @var string */
    public $file;

    /** @var \DateTime */
    public $executed;

    /** @var \DateTime */
    public $reverted;

    /** @var string */
    public $status;

    /** @var string|array|Statement[] */
    public $statements = [];

    /** @var double */
    public $execution_time;

    public function __construct()
    {
        if (!empty($this->executed) && is_string($this->executed)) {
            $this->executed = new \DateTime($this->executed, new \DateTimeZone('UTC'));
        }

        if (!empty($this->reverted) && is_string($this->reverted)) {
            $this->reverted = new \DateTime($this->reverted, new \DateTimeZone('UTC'));
        }

        if (!empty($this->statements) && is_string($this->statements)) {
            $this->statements = array_map(function ($data) {
                return Statement::createInstance($data);
            }, json_decode($this->statements, true));
        }
    }

    public static function createInstance(array $data = []): self
    {
        $new = new static;
        foreach ($data as $key => $value) {
            $new->$key = $value;
        }

        $new->__construct();
        return $new;
    }
}
