# Parable Routing

## 0.3.1

_Fixes_
- With php8 come new challenges -- `?string` values need to be set to `= null` by default.

## 0.3.0

_Changes_
- Dropped support for php7, php8 only from now on.

## 0.2.3

_Bugfix_

- Small fix in `Router`, where while replacing parameters in a url we're matching would not check if the original url part was actually a parameter.

## 0.2.2

_Changes_

- Added `Router::add($httpMethods, $name, $url, $callable, $metadata)`, which does the same as `addRoute()` but will create a new `Route` instance for you. Utility function, but nice.
- Added `Route::hasMetadataValues(): bool`.

## 0.2.1

_Changes_

Well, that had unintentional effects, didn't it? The whole idea of named routes was that names were unique. So requiring an HTTP method to work with them that way is against that exact notion.

The following methods _obviously_ don't require passing in the HTTP method:
- `getRouteByName(string $name): ?Route`
- `buildRouteUrl(string $name, array $parameters = []): string`

Ahh, the beauty of pre-release software 😅

## 0.2.0

_Changes_

Due to how it's possible to add a route more than once, for multiple methods, storing them by url turned out to be a problem. As such, the following methods now require an HTTP method to be passed:
  - `getRoutes(string $httpMethod): Route[]`
  - `getRouteByName(string $httpMethod, string $name): ?Route`
  - `buildRouteUrl(string $httpMethod, string $name, array $parameters = []): string`
  
The unintended but welcome benefit of this is that matching parametered routes is now significantly faster, since we can immediately ignore all irrelevant HTTP methods.

## 0.1.1

_Changes_
- `Route`-related `Metadata` has been added. Pass an array as the last parameter when creating a new `Route` to use it. You can then use `$route->getMetadataValue('name')` to retrieve the data. This data can be altered during runtime.

## 0.1.0

_Changes_
- First release.
