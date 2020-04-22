<?php

namespace Illuminate\Database\Vertica;

use Doctrine\DBAL\Driver\PDOPgSql\Driver as DoctrineDriver;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Vertica\Query\Grammars\VerticaGrammar as QueryGrammar;
use Illuminate\Database\Vertica\Query\Processors\VerticaProcessor;
use Illuminate\Database\Vertica\Schema\Grammars\VerticaGrammar as SchemaGrammar;
use Illuminate\Database\Schema\PostgresBuilder;

class VerticaConnection extends PostgresConnection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Vertica\Query\Grammars\VerticaGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\PostgresBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new PostgresBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Vertica\Query\Grammars\VerticaGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Vertica\Query\Processors\VerticaProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new VerticaProcessor();
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Doctrine\DBAL\Driver\PDOPgSql\Driver
     */
    protected function getDoctrineDriver()
    {
        return new DoctrineDriver;
    }
}
