<?php declare(strict_types=1);

namespace Parable\Routing\Tests;

use Parable\Routing\Route;
use Parable\Routing\Route\ParameterValues;
use Parable\Routing\Router;
use Parable\Routing\RoutingException;
use Parable\Routing\Tests\Classes\Controller;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    protected Router $router;

    public function setUp(): void
    {
        $this->router = new Router();
    }

    protected function setUpDefaultRoutesAndAssert(): void
    {
        self::assertCount(0, $this->router->getRoutes('GET'));

        $this->router->addRoutes(
            new Route(
                ['GET'],
                'simple',
                '/simple',
                [Controller::class, 'simple'],
            ),
            new Route(
                ['GET', 'POST'],
                'complex',
                '/complex/{id}/{name}',
                [Controller::class, 'complex'],
            ),
            new Route(
                ['GET'],
                'callable',
                '/callable/{parameter}',
                fn($parameter) => 'callable received: ' . $parameter,
            ),
            new Route(
                ['GET'],
                'catchall',
                '/catchall/*',
                [Controller::class, 'catchAll'],
            ),
            new Route(
                ['GET'],
                'catchall2',
                '/catch/all/*',
                [Controller::class, 'catchAll'],
            ),
            new Route(
                ['GET'],
                'catchall3',
                '/catch/{param}/*',
                [Controller::class, 'catchAll'],
            ),
        );

        self::assertCount(6, $this->router->getRoutes('GET'));
        self::assertCount(1, $this->router->getRoutes('POST'));
    }

    public function testAddRouteAndGetRouteByName(): void
    {
        $this->setUpDefaultRoutesAndAssert();

        $route = $this->router->getRouteByName('simple');

        self::assertSame(['GET'], $route->getHttpMethods());
        self::assertSame('/simple', $route->getUrl());
        self::assertSame(Controller::class, $route->getController());
        self::assertSame('simple', $route->getAction());

        self::assertNull($route->getCallable());

        self::assertFalse($route->hasParameterValues());
        self::assertFalse($route->hasMetadataValues());
    }

    public function testAddAndGetRouteByName(): void
    {
        $this->router->add(['GET'], 'name-of-route', 'url', function() { return 'ran'; }, ['metadata' => true]);

        $route = $this->router->getRouteByName('name-of-route');

        self::assertSame(['GET'], $route->getHttpMethods());
        self::assertSame('/url', $route->getUrl());
        self::assertIsCallable($route->getCallable());

        self::assertFalse($route->hasParameterValues());
        self::assertTrue($route->hasMetadataValues());

        self::assertTrue($route->getMetadata()->get('metadata'));
    }

    public function testInvalidGetRouteByNameReturnsNull(): void
    {
        self::assertNull($this->router->getRouteByName('la-dee-dah'));
    }

    public function testInvalidMatchReturnsNull(): void
    {
        $this->setUpDefaultRoutesAndAssert();

        self::assertNull($this->router->match('GET', 'la-dee-dah'));
    }

    public function testNoRoutesExistingReturnsNull(): void
    {
        self::assertNull((new Router())->match('GET', 'la-dee-dah'));
    }

    public function testInvalidMatchOnMethodReturnsNull(): void
    {
        $this->setUpDefaultRoutesAndAssert();

        self::assertNotNull($this->router->match('GET', '/simple'));
        self::assertNull($this->router->match('POST', '/simple'));
    }

    public function testMatchUrlSimple(): void
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

    public function testUrlWithHtmlShouldNotMatch(): void
    {
        $this->router->addRoute(new Route(
            ['GET'],
            'callable2',
            '/this-should-work',
            fn() => "it did!"
        ));

        $route = $this->router->match('GET', '/<b>this-should-work</b>');

        self::assertNull($route);
    }

    public function testMatchUrlComplexMain(): void
    {
        $this->assertComplexUrl(
            'GET',
            '/complex/id-value/name-value',
            '/complex/{id}/{name}',
            new ParameterValues(['id' => 'id-value', 'name' => 'name-value'])
        );
    }

    public function testMatchUrlComplexMainPost(): void
    {
        $this->assertComplexUrl(
            'POST',
            '/complex/id-value/name-value',
            '/complex/{id}/{name}',
            new ParameterValues(['id' => 'id-value', 'name' => 'name-value'])
        );
    }

    public function testMatchUrlComplexZero1(): void
    {
        $this->assertComplexUrl(
            'GET',
            '/complex/id-value/0',
            '/complex/{id}/{name}',
            new ParameterValues(['id' => 'id-value', 'name' => '0'])
        );
    }

    public function testMatchUrlComplexZero2(): void
    {
        $this->assertComplexUrl(
            'GET',
            '/complex/0/something',
            '/complex/{id}/{name}',
            new ParameterValues(['id' => '0', 'name' => 'something'])
        );
    }

    public function testMatchUrlComplexZero3(): void
    {
        $this->assertComplexUrl(
            'GET',
            '/complex/123/00',
            '/complex/{id}/{name}',
            new ParameterValues(['id' => '123', 'name' => '00'])
        );
    }

    public function testMatchUrlComplexZero4(): void
    {
        $this->assertComplexUrl(
            'GET',
            '/complex/123/0.0',
            '/complex/{id}/{name}',
            new ParameterValues(['id' => '123', 'name' => '0.0'])
        );
    }

    public function testMatchUrlComplexZero5(): void
    {
        $this->assertComplexUrl(
            'GET',
            '/complex/123/0.00',
            '/complex/{id}/{name}',
            new ParameterValues(['id' => '123', 'name' => '0.00'])
        );
    }

    public function testMatchUrlComplexSpace(): void
    {
        $this->assertComplexUrl(
            'GET',
            '/complex/ /a',
            '/complex/{id}/{name}',
            new ParameterValues(['id' => ' ', 'name' => 'a'])
        );
    }

    public function testMatchUrlCallable(): void
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

    public function testBuildRouteUrl(): void
    {
        $this->setUpDefaultRoutesAndAssert();

        $route = $this->router->buildRouteUrl('complex', ['id' => 2, 'name' => 'stuff']);
        self::assertSame("/complex/2/stuff", $route);
    }

    public function testBuildRouteUrlThrowsOnUnknownName(): void
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage("Route 'nope' not found.");

        $this->router->buildRouteUrl('nope', ['id' => 2, 'name' => 'stuff']);
    }

    public function testBuildRouteUrlThrowsOnUrlWithWrongParameters(): void
    {
        $this->setUpDefaultRoutesAndAssert();

        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage("Parameter 'id2' not found in url '/complex/{id}/{name}'.");

        $this->router->buildRouteUrl('complex', ['id2' => 2, 'name2' => 'stuff']);
    }

    public function testRouteReturnsNullOnNonExistingValueKey(): void
    {
        $this->setUpDefaultRoutesAndAssert();

        $route = $this->router->match('GET', '/simple');

        self::assertInstanceOf(Route::class, $route);

        self::assertNull($route->getParameterValue('stuff'));
    }

    public function testRouteBuildUrlWithOrWithoutParameters(): void
    {
        $this->setUpDefaultRoutesAndAssert();

        self::assertSame('/simple', $this->router->buildRouteUrl('simple'));
        self::assertSame('/simple', $this->router->buildRouteUrl('simple', []));
        self::assertSame('/simple', $this->router->buildRouteUrl('simple', ['id2' => 2, 'name2' => 'stuff']));
    }

    public function testGetRoutesReturnsCorrectNumberOfRoutes(): void
    {
        $this->setUpDefaultRoutesAndAssert();

        self::assertCount(4, $this->router->getRoutes('GET'));
        self::assertCount(1, $this->router->getRoutes('POST'));
    }

    public function testAddMultipleRoutesDirectly(): void
    {
        self::assertCount(0, $this->router->getRoutes('GET'));

        $route1 = new Route(['GET'], 'route1', 'route1', fn() => null);
        $route2 = new Route(['GET'], 'route2', 'route2', fn() => null);
        $route3 = new Route(['GET'], 'route3', 'route3', fn() => null);

        $this->router->addRoutes($route1, $route2, $route3);

        self::assertCount(3, $this->router->getRoutes('GET'));
    }

    public function testRandomHttpMethodsAllowed(): void
    {
        $route = new Route(['TRACE'], 'traceroute', 'traceroute', fn() => null);

        $this->router->addRoute($route);

        $routeMatched = $this->router->match('TRACE', 'traceroute');

        self::assertSame($route, $routeMatched);
    }

    public function testSameUrlDifferentMethodIsMatchedCorrectly(): void
    {
        $routeGet = new Route(['GET'], 'traceroute-get', 'traceroute', fn() => null);
        $routePost = new Route(['POST'], 'traceroute-post', 'traceroute', fn() => null);

        $this->router->addRoutes($routeGet, $routePost);

        $routeMatchedGet = $this->router->match('GET', 'traceroute');

        self::assertSame($routeGet, $routeMatchedGet);

        $routeMatchedPost = $this->router->match('POST', 'traceroute');

        self::assertSame($routePost, $routeMatchedPost);
    }

    public function testSameUrlDifferentMethodIsMatchedEvenOnMultipleMethods(): void
    {
        $route = new Route(['GET', 'POST'], 'traceroute', 'traceroute', fn() => null);

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
    public function testPrefixedTrailingSlashesDoNotMatter(string $url): void
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

    public function testSimilarUrlsDoNotConflictAndParametersAreReplacedCorrectly(): void
    {
        $this->router->add(['GET'], 'route1', 'route1/{param}', fn() => null);
        $this->router->add(['GET'], 'route2', 'route2/{param}', fn() => null);

        $matched = $this->router->match('GET', 'route1/test');

        self::assertNotNull($matched);
        self::assertSame('route1', $matched->getName());

        $matched = $this->router->match('GET', 'route2/test');

        self::assertNotNull($matched);
        self::assertSame('route2', $matched->getName());
    }

    protected function assertComplexUrl(
        string $method,
        string $url,
        string $routeUrl,
        ParameterValues $values
    ): void {
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

    /**
     * @dataProvider provideCorrectCatchAllUrls
     */
    public function testFailCorrectCatchAllUrl(string $url): void
    {
        $this->router->addRoute(new Route(['GET'], 'catchAll', $url, [Controller::class, 'catchAll']));
        self::assertCount(1, $this->router->getRoutes('GET'));
    }
    private function provideCorrectCatchAllUrls(): array
    {
        return [
            ['*'],
            ['/catchall/*'],
            ['/catchall/*/'],
            ['/catchall/{some}/*'],
            ['/catchall/{some}/*/'],
            ['/catchall/{some}/{param}/*'],
            ['/catchall/{some}/{param}/*/'],
        ];
    }

    /**
     * @dataProvider provideIncorrectCatchAllUrls
     */
    public function testFailIncorrectCatchAllUrl(string $url): void
    {
        $this->expectException(RoutingException::class);
        $this->router->addRoute(new Route(['GET'], 'catchAll', $url, [Controller::class, 'catchAll']));
    }
    private function provideIncorrectCatchAllUrls(): array
    {
        return [
            ['/catchall/*/something'],
            ['/catchall/*/{param}'],
            ['/catchall/**'],
            ['/catchall/blah*'],
            ['/catchall/*blah'],
        ];
    }

    /**
     * @dataProvider provideCatchAllUrls
     */
    public function testCatchAllUrl(string $url, array $expectedParams, array $expectedCatchAllValues): void
    {
        $this->setUpDefaultRoutesAndAssert();
        $this->router->addRoute(new Route(['GET'], 'catchEverything', '*', fn() => null));

        $route = $this->router->match('GET', $url);

        self::assertNotNull($route);
        self::assertEquals(count($expectedParams), count($route->getParameters()));
        self::assertEquals($expectedParams, array_values($route->getParameterValues()->getAll()));
        self::assertEquals(count($expectedCatchAllValues), count($route->getCatchAllValues()));
        self::assertEquals($expectedCatchAllValues, $route->getCatchAllValues());
    }

    private function provideCatchAllUrls(): array
    {
        return [
            ['/catchall/something', [], ['something']],
            ['/catchall/something/else', [], ['something', 'else']],
            ['/catch/all/something', [], ['something']],
            ['/catch/all/something/else', [], ['something', 'else']],
            ['/catch/more/something', ['more'], ['something']],
            ['/catch/more/something/else', ['more'], ['something', 'else']],
            ['/everything', [], ['everything']],
            ['/catching/everything', [], ['catching', 'everything']],
            ['/and/catching/everything', [], ['and', 'catching','everything']],
        ];
    }
}
