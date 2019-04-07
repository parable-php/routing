<?php declare(strict_types=1);

namespace Parable\Routing\Tests;

use Parable\Routing\Exception;
use Parable\Routing\Route;
use Parable\Routing\Router;
use Parable\Routing\Tests\Classes\Controller;

class RouterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Router
     */
    protected $router;

    public function setUp()
    {
        $this->router = new Router();
    }

    protected function setUpDefaultRoutesAndAssert()
    {
        self::assertCount(0, $this->router->getRoutes('GET'));

        $this->router->addRoutes(
            new Route(
                ['GET'],
                'simple',
                '/simple',
                [Controller::class, 'simple']
            ),
            new Route(
                ['GET', 'POST'],
                'complex',
                '/complex/{id}/{name}',
                [Controller::class, 'complex']
            ),
            new Route(
                ['GET'],
                'callable',
                '/callable/{parameter}',
                function ($parameter): string {
                    return 'callable received: ' . $parameter;
                }
            )
        );

        self::assertCount(3, $this->router->getRoutes('GET'));
        self::assertCount(1, $this->router->getRoutes('POST'));
    }

    public function testAddRouteAndGetRouteByName()
    {
        $this->setUpDefaultRoutesAndAssert();

        $route = $this->router->getRouteByName('GET', 'simple');

        self::assertSame(['GET'], $route->getHttpMethods());
        self::assertSame('/simple', $route->getUrl());
        self::assertSame(Controller::class, $route->getController());
        self::assertSame('simple', $route->getAction());

        self::assertNull($route->getCallable());

        self::assertFalse($route->hasParameterValues());
    }

    public function testInvalidGetRouteByNameReturnsNull()
    {
        self::assertNull($this->router->getRouteByName('GET', 'la-dee-dah'));
    }

    public function testInvalidMatchReturnsNull()
    {
        $this->setUpDefaultRoutesAndAssert();

        self::assertNull($this->router->match('GET', 'la-dee-dah'));
    }

    public function testNoRoutesExistingReturnsNull()
    {
        self::assertNull((new Router())->match('GET', 'la-dee-dah'));
    }

    public function testInvalidMatchOnMethodReturnsNull()
    {
        $this->setUpDefaultRoutesAndAssert();

        self::assertNotNull($this->router->match('GET', '/simple'));
        self::assertNull($this->router->match('POST', '/simple'));
    }

    public function testMatchUrlSimple()
    {
        $this->setUpDefaultRoutesAndAssert();

        $route = $this->router->match('GET', '/simple');

        self::assertNotNull($route);

        self::assertSame(['GET'], $route->getHttpMethods());
        self::assertSame('/simple', $route->getUrl());
        self::assertSame(Controller::class, $route->getController());
        self::assertSame('simple', $route->getAction());

        self::assertNull($route->getCallable());

        self::assertFalse($route->hasParameterValues());
    }

    public function testUrlWithHtmlShouldNotMatch()
    {
        $this->router->addRoute(new Route(
            ['GET'],
            'callable2',
            '/this-should-work',
            function () {
                return "it did!";
            }
        ));

        $route = $this->router->match('GET', '/<b>this-should-work</b>');

        self::assertNull($route);
    }

    private function assertComplexUrl(string $method, string $url, string $routeUrl, Route\ParameterValues $values)
    {
        $this->setUpDefaultRoutesAndAssert();

        $route = $this->router->match($method, $url);

        self::assertNotNull($route);

        self::assertSame(['GET', 'POST'], $route->getHttpMethods());
        self::assertSame($routeUrl, $route->getUrl());
        self::assertSame(Controller::class, $route->getController());
        self::assertSame('complex', $route->getAction());

        self::assertNull($route->getCallable());

        self::assertTrue($route->hasParameterValues());
        self::assertEquals($values, $route->getParameterValues());
    }

    public function testmatchUrlComplexMain()
    {
        self::assertComplexUrl(
            'GET',
            '/complex/id-value/name-value',
            '/complex/{id}/{name}',
            new Route\ParameterValues(['id' => 'id-value', 'name' => 'name-value'])
        );
    }

    public function testmatchUrlComplexMainPost()
    {
        self::assertComplexUrl(
            'POST',
            '/complex/id-value/name-value',
            '/complex/{id}/{name}',
            new Route\ParameterValues(['id' => 'id-value', 'name' => 'name-value'])
        );
    }

    public function testmatchUrlComplexZero1()
    {
        self::assertComplexUrl(
            'GET',
            '/complex/id-value/0',
            '/complex/{id}/{name}',
            new Route\ParameterValues(['id' => 'id-value', 'name' => '0'])
        );
    }

    public function testmatchUrlComplexZero2()
    {
        self::assertComplexUrl(
            'GET',
            '/complex/0/something',
            '/complex/{id}/{name}',
            new Route\ParameterValues(['id' => '0', 'name' => 'something'])
        );
    }

    public function testmatchUrlComplexZero3()
    {
        self::assertComplexUrl(
            'GET',
            '/complex/123/00',
            '/complex/{id}/{name}',
            new Route\ParameterValues(['id' => '123', 'name' => '00'])
        );
    }

    public function testmatchUrlComplexZero4()
    {
        self::assertComplexUrl(
            'GET',
            '/complex/123/0.0',
            '/complex/{id}/{name}',
            new Route\ParameterValues(['id' => '123', 'name' => '0.0'])
        );
    }

    public function testmatchUrlComplexZero5()
    {
        self::assertComplexUrl(
            'GET',
            '/complex/123/0.00',
            '/complex/{id}/{name}',
            new Route\ParameterValues(['id' => '123', 'name' => '0.00'])
        );
    }

    public function testmatchUrlComplexSpace()
    {
        self::assertComplexUrl(
            'GET',
            '/complex/ /a',
            '/complex/{id}/{name}',
            new Route\ParameterValues(['id' => ' ', 'name' => 'a'])
        );
    }

    public function testmatchUrlCallable()
    {
        $this->setUpDefaultRoutesAndAssert();

        $route = $this->router->match('GET', '/callable/stuff');

        self::assertNotNull($route);

        self::assertSame(['GET'], $route->getHttpMethods());
        self::assertSame('/callable/{parameter}', $route->getUrl());
        self::assertSame(
            'stuff',
            $route->getParameterValue('parameter')
        );

        self::assertNotNull($route->getCallable());

        $callable = $route->getCallable();

        $returnValue = $callable(...$route->getParameterValues()->getAll());

        self::assertSame('callable received: stuff', $returnValue);
    }

    public function testBuildRouteUrl()
    {
        $this->setUpDefaultRoutesAndAssert();

        $route = $this->router->buildRouteUrl('GET', 'complex', ['id' => 2, 'name' => 'stuff']);
        self::assertSame("/complex/2/stuff", $route);
    }

    public function testBuildRouteUrlThrowsOnUnknownName()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Route 'nope' not found.");

        $this->router->buildRouteUrl('GET', 'nope', ['id' => 2, 'name' => 'stuff']);
    }

    public function testBuildRouteUrlThrowsOnUrlWithWrongParameters()
    {
        $this->setUpDefaultRoutesAndAssert();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Parameter 'id2' not found in url '/complex/{id}/{name}'.");

        $this->router->buildRouteUrl('GET', 'complex', ['id2' => 2, 'name2' => 'stuff']);
    }

    public function testRouteReturnsNullOnNonExistingValueKey()
    {
        $this->setUpDefaultRoutesAndAssert();

        $route = $this->router->match('GET', '/simple');

        self::assertInstanceOf(Route::class, $route);

        self::assertNull($route->getParameterValue('stuff'));
    }

    public function testRouteBuildUrlWithOrWithoutParameters()
    {
        $this->setUpDefaultRoutesAndAssert();

        self::assertSame('/simple', $this->router->buildRouteUrl('GET', 'simple'));
        self::assertSame('/simple', $this->router->buildRouteUrl('GET', 'simple', []));
        self::assertSame('/simple', $this->router->buildRouteUrl('GET', 'simple', ['id2' => 2, 'name2' => 'stuff']));
    }

    public function testGetRoutesReturnsCorrectNumberOfRoutes()
    {
        $this->setUpDefaultRoutesAndAssert();

        self::assertCount(3, $this->router->getRoutes('GET'));
        self::assertCount(1, $this->router->getRoutes('POST'));
    }

    public function testAddMultipleRoutesDirectly()
    {
        self::assertCount(0, $this->router->getRoutes('GET'));

        $route1 = new Route(['GET'], 'route1', 'route1', function() {});
        $route2 = new Route(['GET'], 'route2', 'route2', function() {});
        $route3 = new Route(['GET'], 'route3', 'route3', function() {});

        $this->router->addRoutes($route1, $route2, $route3);

        self::assertCount(3, $this->router->getRoutes('GET'));
    }

    public function testRandomHttpMethodsAllowed()
    {
        $route = new Route(['TRACE'], 'traceroute', 'traceroute', function() {});

        $this->router->addRoute($route);

        $routeMatched = $this->router->match('TRACE', 'traceroute');

        self::assertSame($route, $routeMatched);
    }

    public function testSameUrlDifferentMethodIsMatchedCorrectly()
    {
        $routeGet = new Route(['GET'], 'traceroute-get', 'traceroute', function() {});
        $routePost = new Route(['POST'], 'traceroute-post', 'traceroute', function() {});

        $this->router->addRoutes($routeGet, $routePost);

        $routeMatchedGet = $this->router->match('GET', 'traceroute');

        self::assertSame($routeGet, $routeMatchedGet);

        $routeMatchedPost = $this->router->match('POST', 'traceroute');

        self::assertSame($routePost, $routeMatchedPost);
    }

    public function testSameUrlDifferentMethodIsMatchedEvenOnMultipleMethods()
    {
        $route = new Route(['GET', 'POST'], 'traceroute', 'traceroute', function() {});

        $this->router->addRoute($route);

        $routeMatchedGet = $this->router->match('GET', 'traceroute');
        $routeMatchedPost = $this->router->match('POST', 'traceroute');

        self::assertSame($route, $routeMatchedGet);
        self::assertSame($route, $routeMatchedPost);
        self::assertSame($routeMatchedGet, $routeMatchedPost);
    }

    /**
     * @dataProvider dpSimpleUrls
     */
    public function testPrefixedTrailingSlashesDoNotMatter(string $url)
    {
        $this->setUpDefaultRoutesAndAssert();

        $route = $this->router->match('GET', $url);

        self::assertInstanceOf(Route::class, $route);
        self::assertSame('simple', $route->getName());
    }

    public function dpSimpleUrls(): array
    {
        return [
            ['simple'],
            ['/simple'],
            ['simple/'],
            ['/simple/'],
        ];
    }
}
