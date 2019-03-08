<?php declare(strict_types=1);

namespace Parable\Routing\Tests;

use Parable\Routing\Exception;
use Parable\Routing\Route;
use Parable\Routing\Router;
use Parable\Routing\Tests\Classes\Controller;

class RouteTest extends \PHPUnit\Framework\TestCase
{
    public function testRouteCreation()
    {
        $route = new Route(
            ['GET'],
            'test-route',
            '/test',
            function() {
                return 'yeah';
            }
        );

        self::assertSame('test-route', $route->getName());
        self::assertSame('/test', $route->getUrl());
        self::assertSame('yeah', $route->getCallable()());

        self::assertFalse($route->hasParameters());
        self::assertSame([], $route->getParameters());

        self::assertFalse($route->hasParameterValues());
    }

    public function testParameteredRouteAndParameterValues()
    {
        $route = new Route(
            ['GET'],
            'test-route',
            '/test/{p1}/{p2}',
            function(string $p1, string $p2) {
                return 'yeah: ' . $p1 . '/' . $p2;
            }
        );

        self::assertSame('test-route', $route->getName());
        self::assertSame('/test/{p1}/{p2}', $route->getUrl());

        self::assertTrue($route->hasParameters());
        self::assertSame(
            ['p1', 'p2'],
            $route->getParameters()
        );

        self::assertFalse($route->hasParameterValues());

        $route->setParameterValues(new Route\ParameterValues([
            'p1' => 'test1',
            'p2' => 'test2',
        ]));

        self::assertTrue($route->hasParameterValues());

        self::assertSame('test1', $route->getParameterValue('p1'));
        self::assertSame('test2', $route->getParameterValue('p2'));
    }

    public function testSetValuesThrowsOnInvalidCount()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Number of values do not match Route parameters.');

        $route = new Route(
            ['GET'],
            'test-route',
            '/test/{p1}/{p2}',
            function(string $p1, string $p2) {
                return 'yeah: ' . $p1 . '/' . $p2;
            }
        );

        $route->setParameterValues(new Route\ParameterValues());
    }

    public function testSetValuesThrowsOnInvalidValueNames()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Values names do not match Route parameters.');

        $route = new Route(
            ['GET'],
            'test-route',
            '/test/{p1}/{p2}',
            function(string $p1, string $p2) {
                return 'yeah: ' . $p1 . '/' . $p2;
            }
        );

        $route->setParameterValues(new Route\ParameterValues([
            'p2' => 'test2',
            'p3' => 'test3',
        ]));
    }
}
