<?php
/**
 * This file is a part of dbal-vertica-driver project.
 *
 * @author Andrey Kolchenko <andreydev@clickadu.net>
 */

declare(strict_types=1);

namespace Che\DBAL\Vertica\Tests;

use Che\DBAL\Vertica\VerticaDriver;

/**
 * Class VerticaDriverTest
 *
 * @package Che\DBAL\Vertica\Tests
 * @author Andrey Kolchenko <andreydev@clickadu.net>
 */
class VerticaDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dpConstructDsn()
    {
        return [
            [
                [],
                [],
                'Driver=Vertica;Servername=localhost;Port=5433;Database=vmartdb;',
            ],
            [
                [
                    'odbc_driver' => 'VerticaDSN',
                    'dbname' => 'testdb',
                    'user' => 'dbadmin',
                    'password' => 'pass',
                    'host' => '127.0.0.1',
                    'driverClass' => 'Che\\DBAL\\Vertica\\VerticaDriver',
                    'driverOptions' => ['label' => 'DEV', 'result_buffer_size' => 0],
                ],
                ['label' => 'DEV', 'result_buffer_size' => 0, 'odbc_driver' => 'VerticaDSN'],
                'Driver=VerticaDSN;Servername=127.0.0.1;Port=5433;Database=testdb;Label=DEV;ResultBufferSize=0;',
            ],
        ];
    }

    /**
     * @param array $params
     * @param array $driverOptions
     * @param string $expected
     *
     * @dataProvider dpConstructDsn
     */
    public function testConstructDsn(array $params, array $driverOptions, $expected)
    {
        $driver = new VerticaDriver();
        $method = new \ReflectionMethod($driver, 'constructDsn');
        $method->setAccessible(true);
        self::assertSame($expected, $method->invoke($driver, $params, $driverOptions));
    }
}
