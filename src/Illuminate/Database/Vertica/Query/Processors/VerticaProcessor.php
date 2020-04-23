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
        $result = $query->getConnection()->selectFromWriteConnection($sql, $values);

        if (empty($result)) {
            return null;
        }

        $sequence = $sequence ?: 'id';

        $id = is_object($result) ? $result->{$sequence} : $result[$sequence];

        return is_numeric($id) ? (int) $id : $id;
    }
}
