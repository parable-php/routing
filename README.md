# Parable Routing

[![Build Status](https://travis-ci.org/parable-php/routing.svg?branch=master)](https://travis-ci.org/parable-php/routing)
[![Latest Stable Version](https://poser.pugx.org/parable-php/routing/v/stable)](https://packagist.org/packages/parable-php/routing)
[![Latest Unstable Version](https://poser.pugx.org/parable-php/routing/v/unstable)](https://packagist.org/packages/parable-php/routing)
[![License](https://poser.pugx.org/parable-php/routing/license)](https://packagist.org/packages/parable-php/routing)

Parable Routing is a fast, intuitive url routing library.

## Install

Php 7.1+ and [composer](https://getcomposer.org) are required.

```bash
$ composer require parable-php/routing
```

## Usage

```php
$router = new Router();

$route1 = new Route(
    ['GET'],
    'simple-route',
    'route/simple',
    [Controller::class, 'actionName']
);

$route2 = new Route(
    ['GET', 'POST'],
    'param-route',
    'route/{param}/hello',
    function (string $param) {
        echo 'Hello, ' . $username . '!';
    }
);

$router->addRoutes($route1, $route2);

$match = $router->match('GET', 'route/devvoh/hello');

echo $match->getName();
```

This would echo `param-route`.

Routing does not provide a direct way of executing a route, but it's easy enough:

```php
$callable = $match->getCallable();
$callable(...$match->getParameterValues()->getAll());
```

## Contributing

Any suggestions, bug reports or general feedback is welcome. Use github issues and pull requests, or find me over at [devvoh.com](https://devvoh.com).

## License

All Parable components are open-source software, licensed under the MIT license.
