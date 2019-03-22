<?php

namespace Breyta;

use Breyta\Model\Statement;

class BasicAdapter implements AdapterInterface
{
    /** @var callable */
    protected $executor;

    public function __construct(callable $executor)
    {
        $this->executor = $executor;
    }

    public function exec(string $sql)
    {
        $statement = $this->getStatement($sql);
        call_user_func($this->executor, $statement);
        return $statement->result;
    }

    public function getStatement(string $sql): Statement
    {
        $statement = new Statement;
        $statement->raw = $sql;
        $sql = preg_replace('/\s+/', ' ', $sql);
        $statement->teaser = substr($sql, 0, 50);

        $delimPattern = '(?>`|")?'; // optional delimiter pattern
        $namePattern = $delimPattern . '(?>[a-z0-9_]+' . $delimPattern . '\.' . $delimPattern . ')?' . // schema
                       '[a-z0-9_]+' . $delimPattern;
        if (preg_match(
            '/^(alter|create|drop) ' .  // action
            '(?>[a-z=]+ )*?' . // something between like 'OR REPLACE', 'DEFINER = user' etc...
            '(?>(table|index|function|trigger|view|procedure|type) )' . // type
            '(' . $namePattern . ')' . // name
            '(?> |$|;)/i',
            $statement,
            $match
        )) {
            $statement->teaser = implode(' ', [strtoupper($match[1]), strtoupper($match[2]), $match[3]]);
            $statement->action = strtolower($match[1]);
            $statement->type = strtolower($match[2]);
            $statement->name = str_replace(['"', '`'], '', $match[3]);
        } elseif (preg_match(
            '/^(update|delete) ' .  // action
            '(' . $namePattern . ')' . // name
            ' /i',
            $statement,
            $match
        )) {
            $statement->teaser = implode(' ', [strtoupper($match[1]), $match[2]]);
            $statement->action = strtolower($match[1]);
            $statement->name = str_replace(['"', '`'], '', $match[2]);
        }

        return $statement;
    }
}
