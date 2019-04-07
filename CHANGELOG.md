# Parable Routing

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
