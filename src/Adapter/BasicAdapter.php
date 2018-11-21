<?php

namespace Breyta\Adapter;

class BasicAdapter
{
    /** @var callable */
    protected $executor;

    public function __construct(callable $executor)
    {
        $this->executor = $executor;
    }

    public function exec(string $statement)
    {
        return call_user_func($this->executor, $statement);
    }

    public function getInfo(string $statement): \stdClass
    {
        $statement = preg_replace('/\s+/', ' ', $statement);
        $info = (object)['teaser' => substr($statement, 0, 50)];
        $delimPattern = '(?>`|")?'; // optional delimiter pattern
        $namePattern = $delimPattern . '(?>[a-z0-9_]+' . $delimPattern . '\.' . $delimPattern . ')?' . // schema
                       '[a-z0-9_]+' . $delimPattern;

        if (preg_match(
            '/^(alter|create|drop) ' .  // action
            '(?>[a-z=]+ )*?' . // something between like 'OR REPLACE', 'DEFINER = user' etc...
            '(?>(table|index|function|trigger|view|procedure) )' . // type
            '(' . $namePattern . ')' . // name
            ' /i',
            $statement,
            $match
        )) {
            $info->teaser = implode(' ', [strtoupper($match[1]), strtoupper($match[2]), $match[3]]);
            $info->action = strtolower($match[1]);
            $info->type = strtolower($match[2]);
            $info->name = str_replace(['"', '`'], '', $match[3]);
        } elseif (preg_match(
            '/^(update|delete) ' .  // action
            '(' . $namePattern . ')' . // name
            ' /i',
            $statement,
            $match
        )) {
            $info->teaser = implode(' ', [strtoupper($match[1]), $match[2]]);
            $info->action = strtolower($match[1]);
            $info->name = str_replace(['"', '`'], '', $match[2]);
        }

        return $info;
    }
}
