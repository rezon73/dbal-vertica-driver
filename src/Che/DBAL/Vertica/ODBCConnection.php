<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\DBAL\Vertica;

use Doctrine\DBAL\Driver\Connection;

/**
 * Class for ODBC connections through odbc_* functions
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class ODBCConnection implements Connection
{
    /**
     *
     */
    const OPTION_EMULATE_MULTIPLE_EXEC = 'emulate_multiple_exec';
    /**
     * @var array
     */
    private static $DEFAULT_OPTIONS = [
        self::OPTION_EMULATE_MULTIPLE_EXEC => false,
    ];
    /**
     * @var resource
     */
    private $dbh;
    /**
     * @var array
     */
    private $options;

    /**
     * ODBCConnection constructor.
     *
     * @param $dsn
     * @param $user
     * @param $password
     * @param array $options
     */
    public function __construct($dsn, $user, $password, array $options = [])
    {
        $this->options = array_merge(self::$DEFAULT_OPTIONS, $options);
        $this->dbh = @odbc_connect($dsn, $user, $password);
        if (!$this->dbh) {
            $error = error_get_last();
            throw new ODBCException($error['message']);
        }

        if (!empty($this->options["search_path"])) {
            odbc_exec($this->dbh, "SET search_path to " . $this->options["search_path"]);
        }
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
        if (!isset($this->options[$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown option "%s"', $name));
        }

        return $this->options[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function prepare($prepareString)
    {
        return new ODBCStatement($this->dbh, $prepareString, $this->options);
    }

    /**
     * {@inheritDoc}
     */
    public function query()
    {
        $args = func_get_args();
        $sql = $args[0];
        $stmt = $this->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * @param string $input
     * @param int $type
     *
     * @return string
     */
    public function quote($input, $type = \PDO::PARAM_STR)
    {
        // Different databases uses different escape algorithms, we can use this as default only
        // TODO: use custom function
        if (is_int($input) || is_float($input)) {
            return $input;
        }

        return "'" . str_replace("'", "''", $input) . "'";
    }

    /**
     * @param string $statement
     *
     * @return int
     */
    public function exec($statement)
    {
        $stmt = $this->prepare($statement);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * @param null $name
     *
     * @return bool|mixed|string
     */
    public function lastInsertId($name = null)
    {
        return $this->query("SELECT CURRVAL('%s')")->fetchColumn();
    }

    /**
     * @return bool
     */
    public function inTransaction()
    {
        return !odbc_autocommit($this->dbh);
    }

    /**
     * @return mixed
     */
    public function beginTransaction()
    {
        $this->checkTransactionStarted(false);

        return odbc_autocommit($this->dbh, false);
    }

    /**
     * @return bool
     */
    public function commit()
    {
        $this->checkTransactionStarted();

        return odbc_commit($this->dbh) && odbc_autocommit($this->dbh, true);
    }

    /**
     * @return bool
     */
    public function rollBack()
    {
        $this->checkTransactionStarted();

        return odbc_rollback($this->dbh) && odbc_autocommit($this->dbh, true);
    }

    /**
     * @return string
     */
    public function errorCode()
    {
        return odbc_error($this->dbh);
    }

    /**
     * @return array
     */
    public function errorInfo()
    {
        return [
            'code' => odbc_error($this->dbh),
            'message' => odbc_errormsg($this->dbh),
        ];
    }

    /**
     * @param bool $flag
     */
    private function checkTransactionStarted($flag = true)
    {
        if ($flag && !$this->inTransaction()) {
            throw new ODBCException('Transaction was not started');
        }
        if (!$flag && $this->inTransaction()) {
            throw new ODBCException('Transaction was already started');
        }
    }
}
