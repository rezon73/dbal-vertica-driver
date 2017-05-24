<?php
/**
 * This file is a part of dbal-vertica-driver project.
 *
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Che\DBAL\Vertica\Tests;

/**
 * Class ODBCStatementTest
 *
 * @package Che\DBAL\Vertica\Tests
 * @author Andrey Kolchenko <andrey@kolchenko.me>
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
        $query = $method->invoke(
            $object,
            <<<'SQL'
SELECT REGEXP_SUBSTR(s.url, '^(?:https?:)?//(?:www\.)?([^/]+)/', 1, 1, 'i', 1)
FROM s WHERE s.a IN(?,?, ? ,?) AND s.b IN(:a,:b, :c ,:d) AND s.e = ?;
SQL
        );
        self::assertSame(
            <<<'SQL'
SELECT REGEXP_SUBSTR(s.url, '^(?:https?:)?//(?:www\.)?([^/]+)/', 1, 1, 'i', 1)
FROM s WHERE s.a IN(?,?, ? ,?) AND s.b IN(?,?, ? ,?) AND s.e = ?;
SQL
            ,
            $query
        );
        $map = $class->getProperty('paramMap');
        $map->setAccessible(true);
        self::assertSame(
            [
                0 => 1,
                1 => 2,
                2 => 3,
                3 => 4,
                4 => 'a',
                5 => 'b',
                6 => 'c',
                7 => 'd',
                8 => 5,
            ],
            $map->getValue($object)
        );
    }
}
