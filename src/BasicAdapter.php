<?php

namespace Breyta;

use Breyta\Model\Statement;

class BasicAdapter implements AdapterInterface
{
    const PATTERN_DELIMITER = '(?>`|")?'; // optional delimiter pattern
    const PATTERN_NAME = '(?>' . self::PATTERN_DELIMITER . '[a-z0-9_]+' . self::PATTERN_DELIMITER . '\.)?' . // schema
        self::PATTERN_DELIMITER . '[a-z0-9_]+' . self::PATTERN_DELIMITER; // name
    const PATTERN_DDL_STATEMENT = '/^(alter|create|drop) ' .  // action
        '(?>[a-z=]+ )*?' . // something between like 'OR REPLACE', 'DEFINER = user' etc...
        '(?>(table|index|function|trigger|view|procedure|type) )' . // type
        '(?>IF (?>NOT )?EXISTS )?' . // 'IF EXISTS'
        '(' . self::PATTERN_NAME . ')' . // name
        '(?>\s|$|;)/i';
    const PATTERN_DML_STATEMENT = '/^(update|delete|insert into) ' .  // action
        '(' . self::PATTERN_NAME . ')' . // name
        ' /i';

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

        if (preg_match(self::PATTERN_DDL_STATEMENT, $statement, $match)) {
            $statement->teaser = implode(' ', [strtoupper($match[1]), strtoupper($match[2]), $match[3]]);
            $statement->action = strtolower($match[1]);
            $statement->type = strtolower($match[2]);
            $statement->name = str_replace(['"', '`'], '', $match[3]);
        } elseif (preg_match(self::PATTERN_DML_STATEMENT, $statement, $match)) {
            $statement->teaser = implode(' ', [strtoupper($match[1]), $match[2]]);
            $statement->action = strtolower($match[1]);
            $statement->name = str_replace(['"', '`'], '', $match[2]);
        }

        return $statement;
    }
}
