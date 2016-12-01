<?php
/**
 * This file is a part of dbal-vertica-driver project.
 *
 * @author Andrey Kolchenko <andreydev@clickadu.net>
 */

namespace Che\DBAL\Vertica\Tests;

/**
 * Class ODBCStatementTest
 *
 * @package Che\DBAL\Vertica\Tests
 * @author Andrey Kolchenko <andreydev@clickadu.net>
 */
class ODBCStatementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test parsing query
     */
    public function testParseQuery()
    {
        $class = new \ReflectionClass('Che\\DBAL\\Vertica\\ODBCStatement');
        $method = $class->getMethod('parseQuery');
        $method->setAccessible(true);
        $object = $class->newInstanceWithoutConstructor();
        $method->invoke($object, 'SELECT * FROM log WHERE log.date = :date AND log.hour = :hour GROUP BY 1, 2, 3;');
        $query = $class->getProperty('query');
        $query->setAccessible(true);
        $query = $query->getValue($object);
        self::assertSame('SELECT * FROM log WHERE log.date = ? AND log.hour = ? GROUP BY 1, 2, 3;', $query);
        $map = $class->getProperty('paramMap');
        $map->setAccessible(true);
        self::assertSame([':date' => 0, ':hour' => 1], $map->getValue($object));
    }
}
