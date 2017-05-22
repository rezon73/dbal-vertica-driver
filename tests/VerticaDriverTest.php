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
            [[], 'Driver=Vertica;Servername=localhost;Port=5433;Database=vmartdb;'],
        ];
    }

    /**
     * @param array $params
     * @param $expected
     *
     * @dataProvider dpConstructDsn
     */
    public function testConstructDsn(array $params, $expected)
    {
        $driver = new VerticaDriver();
        $method = new \ReflectionMethod($driver, 'constructDsn');
        $method->setAccessible(true);
        self::assertSame($expected, $method->invoke($driver, $params));
    }
}
