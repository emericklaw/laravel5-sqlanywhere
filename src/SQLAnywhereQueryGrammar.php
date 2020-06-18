<?php

namespace josueneo\laravel5sqlanywhere;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class SQLAnywhereQueryGrammar extends Grammar
{
    /**
     * The components that make up a select clause.
     *
     * @var string[]
     */
    protected $selectComponents = [
        'limit',
        'offset',
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'unions',
        'lock',
    ];


    /**
     * Compile a select query into SQL.
     *
     * @param Builder $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        if (is_null($query->columns))
        {
            $query->columns = array('*');
        }
        return 'select ' . trim($this->concatenate($this->compileComponents($query)));
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param Builder $query
     * @param array $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        $column = $this->columnize($aggregate['columns']);

        if ($query->distinct && $column !== '*')
        {
            $column = 'distinct ' . $column;
        }
        return $aggregate['function'] . '(' . $column . ') as aggregate';
    }

    /**
     * Compile the "select *" portion of the query.
     *
     * @param Builder $query
     * @param array $columns
     * @return string|void|null
     */
    protected function compileColumns(Builder $query, $columns)
    {
        if (!is_null($query->aggregate))
        {
            return;
        }
        $select = $query->distinct ? 'distinct ' : '';
        return $select . $this->columnize($columns);
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param Builder $query
     * @param int $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        return 'top ' . (int)$limit;
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param Builder $query
     * @param int $offset
     * @return string
     */
    protected function compileOffset(Builder $query, $offset)
    {
        return 'start at ' . ((int)$offset + 1);
    }

    /**
     * Compile an exists statement into SQL.
     *
     * @param Builder $query
     * @return string
     */
    public function compileExists(Builder $query)
    {
        $existsQuery = clone $query;
        $existsQuery->columns = [];
        return $this->compileSelect($existsQuery->selectRaw('1 [exists]')->limit(1));
    }
}
