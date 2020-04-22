<?php

namespace Illuminate\Database\Vertica\Query\Processors;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\PostgresProcessor;

class VerticaProcessor extends PostgresProcessor
{
    /**
     * Process an "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        return 0;
    }
}
