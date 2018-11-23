<?php

namespace Breyta;

class Migration
{
    /** @var string */
    public $file;

    /** @var \DateTime */
    public $executed;

    /** @var string */
    public $status;

    /** @var string|array|Execution[] */
    public $executions;

    /** @var double */
    public $executionTime;

    public function __construct()
    {
        if (!empty($this->executed) && is_string($this->executed)) {
            $this->executed = new \DateTime($this->executed, new \DateTimeZone('UTC'));
        }

        if (!empty($this->executions) && is_string($this->executions)) {
            $this->executions = array_map(function ($data) {
                return Execution::createInstance($data);
            }, json_decode($this->executions, true));
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
