<?php declare(strict_types=1);

namespace Parable\Routing;

use Parable\Routing\Route\ParameterValues;

class Router
{
    /**
     * @var Route[][]
     */
    protected $routes = [];

    /**
     * @var string[][]
     */
    protected $routeNames = [];

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
            throw new Exception(sprintf("Route '%s' not found.", $name));
        }

        if (!$route->hasParameters()) {
            return $route->getUrl();
        }

        $url = $route->getUrl();

        foreach ($parameters as $name => $value) {
            $parameter = '{' . $name . '}';

            if (strpos($url, $parameter) === false) {
                throw new Exception(sprintf("Parameter '%s' not found in url '%s'.", $name, $url));
            }

            $url = str_replace('{' . $name . '}', $value, $url);
        }

        return $url;
    }

    public function match(string $httpMethod, string $urlToMatch)
    {
        $urlToMatch = '/' . trim($urlToMatch, '/');

        return $this->matchDirect($httpMethod, $urlToMatch)
            ?? $this->matchParametered($httpMethod, $urlToMatch)
            ?? null;
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
            if (strpos($routeUrl, '{') === false || !$route->supportsHttpMethod($httpMethod)) {
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
