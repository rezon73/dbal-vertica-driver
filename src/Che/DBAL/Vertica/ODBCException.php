<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\DBAL\Vertica;

use Exception;

/**
 * Base exception class for ODBC errors
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class ODBCException extends \RuntimeException
{
    /**
     * ODBCException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($message, $code, Exception $previous = null)
    {
        parent::__construct(sprintf('[%s] %s', $code, $message), $code, $previous);
    }

    /**
     * @param resource $dbh
     *
     * @return ODBCException
     */
    public static function fromConnection($dbh)
    {
        return new self(odbc_errormsg($dbh), odbc_error($dbh));
    }
}
