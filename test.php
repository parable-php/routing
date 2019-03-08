<?php

use Parable\Routing\Route;
use Parable\Routing\Router;

require __DIR__ . '/vendor/autoload.php';

$router = new Router();

class Controller {
    public function action() {
        echo 'yeah' . PHP_EOL;
    }

    public function action2($username) {
        echo 'yeah: ' . $username . PHP_EOL;
    }
}

$route1 = new Route(
    ['GET'],
    'test',
    'test',
    [new Controller, 'action']
);

$route2 = new Route(
    ['GET', 'POST'],
    'test-complex',
    'test/{username}/hello',

    function (string $username) {
        echo 'yeah: ' . $username . PHP_EOL;
    }
);

foreach (range(0, 98) as $value) {
    $router->addRoute(new Route(
        ['GET'],
        'test' . $value,
        'test' . $value,
        [new Controller, 'action']
    ));
}

$router->addRoutes($route1, $route2);

$start = microtime(true);

var_dump($route = $router->match('GET', 'test/robin/hello'));

$memory_used = number_format(memory_get_usage() / (1024 * 1024), 2);

//echo PHP_EOL;
//echo 'Memory used: ' . $memory_used . ' MiB' . PHP_EOL;
//echo 'Time to route in ' . count($router->getRoutes()) . ' routes: ' . number_format(microtime(true) - $start, 5) . PHP_EOL;
//
//return;


$route->getCallable()(...$route->getParameterValues()->getAll());
