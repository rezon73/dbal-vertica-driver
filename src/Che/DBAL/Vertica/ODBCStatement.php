<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\DBAL\Vertica;

use Doctrine\DBAL\Driver\Statement;
use Iterator;

/**
 * Statement implementation for ODBC connection
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class ODBCStatement implements Iterator, Statement
{
    /**
     * @var resource
     */
    private $dbh;
    /**
     * @var string
     */
    private $originalQuery;
    /**
     * @var string
     */
    private $query;
    /**
     * @var resource
     */
    private $sth;
    /**
     * @var array
     */
    private $options;
    /**
     * @var int
     */
    private $defaultFetchMode = \PDO::FETCH_BOTH;
    /**
     * @var array
     */
    private $paramMap = [];
    /**
     * @var array
     */
    private $params = [];
    /**
     * @var bool
     */
    private $executed = false;
    /**
     * @var bool
     */
    private $started = false;
    /**
     * @var int
     */
    private $key = -1;
    /**
     * @var mixed
     */
    private $current = null;

    /**
     * ODBCStatement constructor.
     *
     * @param resource $dbh
     * @param string $query
     * @param array $options
     */
    public function __construct($dbh, $query, array $options = [])
    {
        $this->options = $options;
        $this->dbh = $dbh;
        $this->query = $this->parseQuery($query);
        $this->prepare();
    }

    /**
     * {@inheritDoc}
     */
    public function bindValue($param, $value, $type = null)
    {
        $this->bindParam($param, $value, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function bindParam($column, &$variable, $type = null, $length = null)
    {
        if (!in_array($column, $this->paramMap, true)) {
            throw new ODBCException(
                sprintf('Parameter identifier "%s" is not presented in the query "%s"', $column, $this->originalQuery)
            );
        }
        $this->params[$column] = &$variable;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        $this->defaultFetchMode = $fetchMode;
    }

    /**
     * {@inheritDoc}
     */
    public function fetch($fetchMode = null)
    {
        if (!odbc_fetch_row($this->sth)) {
            return false;
        }
        $fetchMode = $fetchMode ?: $this->defaultFetchMode;
        $numFields = odbc_num_fields($this->sth);
        $row = [];
        switch ($fetchMode) {
            case \PDO::FETCH_ASSOC:
                for ($i = 1; $i <= $numFields; $i++) {
                    $row[odbc_field_name($this->sth, $i)] = odbc_result($this->sth, $i);
                }
                break;

            case \PDO::FETCH_NUM:
                for ($i = 1; $i <= $numFields; $i++) {
                    $row[] = odbc_result($this->sth, $i);
                }
                break;
            case \PDO::FETCH_BOTH:
                for ($i = 1; $i <= $numFields; $i++) {
                    $value = odbc_result($this->sth, $i);
                    $row[] = $value;
                    $row[odbc_field_name($this->sth, $i)] = $value;
                }
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Unsupported fetch mode "%s"', $fetchMode));
        }

        return $row;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($params = null)
    {
        if (!empty($this->options[ODBCConnection::OPTION_EMULATE_MULTIPLE_EXEC]) && $this->executed) {
            $this->prepare();
            $this->executed = false;
        }

        if (!empty($params) && is_array($params)) {
            foreach ($params as $pos => $value) {
                if (is_int($pos)) {
                    $pos += 1;
                }
                $this->bindValue($pos, $value);
            }
        }
        $requiredParameters = array_diff(array_unique($this->paramMap), array_keys($this->params));
        if (!empty($requiredParameters)) {
            throw new ODBCException(sprintf('Parameters "%s" has no values.', join(', ', $requiredParameters)));
        }

        $params = array_map(
            function ($name) {
                return $this->params[$name];
            },
            $this->paramMap
        );
        if (!@odbc_execute($this->sth, $params)) {
            throw ODBCException::fromConnection($this->dbh);
        }

        $this->executed = true;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAll($fetchMode = null)
    {
        $rows = [];
        while ($row = $this->fetch($fetchMode)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        $fetched = odbc_fetch_row($this->sth);
        if (!$fetched) {
            return false;
        }

        return odbc_result($this->sth, $columnIndex + 1);
    }

    /**
     * {@inheritDoc}
     */
    public function columnCount()
    {
        return odbc_num_fields($this->sth);
    }

    /**
     * {@inheritDoc}
     */
    public function rowCount()
    {
        return odbc_num_rows($this->sth);
    }

    /**
     * {@inheritDoc}
     */
    public function closeCursor()
    {
        return odbc_free_result($this->sth);
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode()
    {
        return odbc_error($this->dbh);
    }

    /**
     * {@inheritDoc}
     */
    public function errorInfo()
    {
        return [
            'code' => odbc_error($this->dbh),
            'message' => odbc_errormsg($this->dbh),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        if ($this->started) {
            throw new ODBCException('Statement can not be rewound after iteration is started');
        }

        $this->next();
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        if (!$this->executed) {
            $this->execute();
        }
        $this->key++;
        $this->started = true;
        $this->current = $this->fetch();
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->key >= 0 ? $this->key : null;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return $this->current !== false;
    }

    /**
     * Parses query to replace named parameters with positional
     *
     * @param string $query
     *
     * @return string
     */
    protected function parseQuery($query)
    {
        $this->originalQuery = $query;
        $this->query = $query;
        $this->paramMap = [];
        $this->params = [];
        $counter = 1;

        return preg_replace_callback(
            '/(?<!\w)(?:(?<!:):[a-z]\w*|\?)/i',
            function (array $match) use (&$counter) {
                $name = $match[0];
                if ($name === '?') {
                    $this->paramMap[] = $counter++;
                } else {
                    $this->paramMap[] = substr($name, 1);
                }

                return '?';
            },
            $query
        );
    }

    /**
     * Prepare parsed query
     *
     * @throws ODBCException
     */
    protected function prepare()
    {
        $this->sth = @odbc_prepare($this->dbh, $this->query);
        if (!$this->sth) {
            throw ODBCException::fromConnection($this->dbh);
        }
    }
}
