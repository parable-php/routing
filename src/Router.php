<?php declare(strict_types=1);

namespace Parable\Routing;

use Parable\Routing\Route\ParameterValues;

class Router
{
    /**
     * @var Route[]
     */
    protected $routes = [];

    /**
     * @var string[]
     */
    protected $routeNames = [];

    public function addRoute(Route $route): void
    {
        $this->routes[$route->getUrl()] = $route;
        $this->routeNames[$route->getName()] = $route->getUrl();
    }

    public function addRoutes(Route ...$routes): void
    {
        foreach ($routes as $route) {
            $this->addRoute($route);
        }
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getRouteByName(string $name): ?Route
    {
        $routeUrl = $this->routeNames[$name] ?? null;

        if ($routeUrl === null) {
            return null;
        }

        return $this->routes[$routeUrl] ?? null;
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
        $route = $this->routes[$urlToMatch] ?? null;

        if ($route === null || !$route->supportsHttpMethod($httpMethod)) {
            return null;
        }

        return $route;
    }

    protected function matchParametered(string $httpMethod, string $urlToMatch): ?Route
    {
        foreach ($this->routes as $routeUrl => $route) {
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
