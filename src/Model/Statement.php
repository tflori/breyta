<?php

namespace Breyta\Model;

class Statement
{
    /** @var string */
    public $raw;

    /** @var string */
    public $teaser;

    /** @var string  */
    public $action;

    /** @var string  */
    public $type;

    /** @var string  */
    public $name;

    /** @var mixed */
    public $result = false;

    /** @var double */
    public $executionTime;

    /** @var \PDOException */
    public $exception;

    public static function createInstance(array $data = []): self
    {
        $new = new static;
        foreach ($data as $key => $value) {
            $new->$key = $value;
        }

        return $new;
    }

    public function __toString()
    {
        return $this->raw;
    }
}
