<?php

namespace Breyta;

class Execution
{
    /** @var string */
    public $teaser;

    /** @var string  */
    public $action;

    /** @var string  */
    public $type;

    /** @var string  */
    public $name;

    /** @var double */
    public $executionTime;

    public static function createInstance(array $data = []): self
    {
        $new = new static;
        foreach ($data as $key => $value) {
            $new->$key = $value;
        }

        return $new;
    }
}
