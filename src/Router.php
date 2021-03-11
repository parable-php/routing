<?php declare(strict_types=1);

namespace Parable\Routing;

use Parable\Routing\Route\ParameterValues;

class Router
{
    /** @var Route[][] */
    protected array $routes = [];

    /** @var string[][] */
    protected array $routeNames = [];

    /**
     * @param string[] $httpMethods
     */
    public function add(
        array $httpMethods,
        string $name,
        string $url,
        mixed $callable,
        array $metadata = []
    ): void {
        $this->addRoute(
            new Route($httpMethods, $name, $url, $callable, $metadata)
        );
    }

    public function addRoute(Route $route): void
    {
        foreach ($route->getHttpMethods() as $httpMethod) {
            $this->addRouteForHttpMethod($httpMethod, $route);
        }
    }

    protected function addRouteForHttpMethod(string $httpMethod, Route $route): void
    {
        if (!array_key_exists($httpMethod, $this->routes)) {
            $this->routes[$httpMethod] = [];
        }

        $this->routes[$httpMethod][$route->getUrl()] = $route;
        $this->routeNames[$httpMethod][$route->getName()] = $route->getUrl();
    }

    public function addRoutes(Route ...$routes): void
    {
        foreach ($routes as $route) {
            $this->addRoute($route);
        }
    }

    public function getRoutes(string $httpMethod): array
    {
        return $this->routes[$httpMethod] ?? [];
    }

    public function getRouteByName(string $name): ?Route
    {
        foreach ($this->routeNames as $httpMethod => $routeNames) {
            $routeUrl = $routeNames[$name] ?? null;

            if ($routeUrl !== null) {
                return $this->routes[$httpMethod][$routeUrl] ?? null;
            }
        }

        return null;
    }

    public function buildRouteUrl(string $name, array $parameters = []): string
    {
        $route = $this->getRouteByName($name);

        if ($route === null) {
            throw new RoutingException(sprintf("Route '%s' not found.", $name));
        }

        if (!$route->hasParameters()) {
            return $route->getUrl();
        }

        $url = $route->getUrl();

        foreach ($parameters as $parameterName => $value) {
            $parameter = '{' . $parameterName . '}';

            if (!str_contains($url, $parameter)) {
                throw new RoutingException(sprintf(
                    "Parameter '%s' not found in url '%s'.",
                    $parameterName,
                    $url
                ));
            }

            $url = str_replace($parameter, (string)$value, $url);
        }

        return $url;
    }

    public function match(string $httpMethod, string $urlToMatch): ?Route
    {
        $urlToMatch = '/' . trim($urlToMatch, '/');

        return $this->matchDirect($httpMethod, $urlToMatch)
            ?? $this->matchParametered($httpMethod, $urlToMatch);
    }

    protected function matchDirect(string $httpMethod, string $urlToMatch): ?Route
    {
        $route = $this->routes[$httpMethod][$urlToMatch] ?? null;

        if ($route === null || !$route->supportsHttpMethod($httpMethod)) {
            return null;
        }

        return $route;
    }

    protected function matchParametered(string $httpMethod, string $urlToMatch): ?Route
    {
        if (!array_key_exists($httpMethod, $this->routes)) {
            return null;
        }

        foreach ($this->routes[$httpMethod] as $routeUrl => $route) {
            if (!str_contains($routeUrl, '{') || !$route->supportsHttpMethod($httpMethod)) {
                continue;
            }

            $explodedUrlToMatch = explode('/', trim($urlToMatch, '/'));
            $explodedRouteUrl = explode('/', trim($routeUrl, '/'));

            if (count($explodedUrlToMatch) !== count($explodedRouteUrl)) {
                continue;
            }

            $providedValues = array_diff($explodedUrlToMatch, $explodedRouteUrl);

            $valuesWithParameterAsKey = [];

            foreach ($providedValues as $key => $value) {
                $parameter = $explodedRouteUrl[$key];

                if (!str_contains($parameter, '{')) {
                    continue;
                }

                $valuesWithParameterAsKey[trim($parameter, '{}')] = $value;

                $explodedUrlToMatch[$key] = $parameter;
            }

            if ($explodedUrlToMatch === $explodedRouteUrl) {
                $route->setParameterValues(new ParameterValues($valuesWithParameterAsKey));
                return $route;
            }
        }

        return null;
    }
}
