<?php declare(strict_types=1);

namespace Parable\Routing\Tests;

use Parable\Routing\RoutingException;
use Parable\Routing\Route;
use Parable\Routing\Route\Metadata;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testRouteCreation(): void
    {
        $route = new Route(
            ['GET'],
            'test-route',
            '/test',
            fn() => 'yeah'
        );

        self::assertSame('test-route', $route->getName());
        self::assertSame('/test', $route->getUrl());
        self::assertSame('yeah', $route->getCallable()());

        self::assertFalse($route->hasParameters());
        self::assertSame([], $route->getParameters());

        self::assertFalse($route->hasParameterValues());
    }

    public function testParameteredRouteAndParameterValues(): void
    {
        $route = new Route(
            ['GET'],
            'test-route',
            '/test/{p1}/{p2}',
            fn(string $p1, string $p2) => 'yeah: ' . $p1 . '/' . $p2
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

    public function testSetValuesThrowsOnInvalidCount(): void
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Number of values do not match Route parameters.');

        $route = new Route(
            ['GET'],
            'test-route',
            '/test/{p1}/{p2}',
            fn(string $p1, string $p2) => 'yeah: ' . $p1 . '/' . $p2
        );

        $route->setParameterValues(new Route\ParameterValues());
    }

    public function testSetValuesThrowsOnInvalidValueNames(): void
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Values names do not match Route parameters.');

        $route = new Route(
            ['GET'],
            'test-route',
            '/test/{p1}/{p2}',
            fn(string $p1, string $p2) => 'yeah: ' . $p1 . '/' . $p2
        );

        $route->setParameterValues(new Route\ParameterValues([
            'p2' => 'test2',
            'p3' => 'test3',
        ]));
    }

    public function testNoMetadataIsHandledCorrectly(): void
    {
        $route = new Route(['GET'], 'name', 'url', fn() => null);

        self::assertFalse($route->hasMetadataValues());
        self::assertEmpty($route->getMetadata()->getAll());
    }

    public function testMetadataOnRouteCreation(): void
    {
        $route = new Route(
            ['GET'],
            'test-route',
            '/test',
            fn() => 'yeah',
            [
                'template' => 'yeah.phtml',
            ]
        );

        self::assertTrue($route->hasMetadataValues());
        self::assertSame('yeah.phtml', $route->getMetadataValue('template'));
    }

    public function testMetadataGetSet(): void
    {
        $metadata = new Metadata();

        self::assertNull($metadata->get('test'));

        $metadata->set('test', true);

        self::assertTrue($metadata->get('test'));
    }

    public function testMetadataGetAllAndSetMany(): void
    {
        $metadata = new Metadata();

        self::assertSame([], $metadata->getAll());

        $metadata->setMany([
            'test' => true,
            'more' => 'also true',
        ]);

        self::assertSame(
            [
                'test' => true,
                'more' => 'also true',
            ],
            $metadata->getAll()
        );
    }
}
