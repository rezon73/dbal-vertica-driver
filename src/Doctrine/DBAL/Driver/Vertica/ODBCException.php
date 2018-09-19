<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Doctrine\DBAL\Driver\Vertica;

/**
 * Base exception class for ODBC errors
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class ODBCException extends \RuntimeException
{
    /**
     * @param resource $dbh
     *
     * @return ODBCException
     */
    public static function fromConnection($dbh)
    {
        return new self(sprintf('[%s] %s', odbc_errormsg($dbh), odbc_error($dbh)));
    }
}
