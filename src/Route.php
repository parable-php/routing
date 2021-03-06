<?php declare(strict_types=1);

namespace Parable\Routing;

use Parable\Routing\Route\Metadata;
use Parable\Routing\Route\ParameterValues;

class Route
{
    /** @var string[] */
    protected array $httpMethods = [];

    protected string $name;
    protected string $url;

    /** @var callable|null */
    protected $callable;

    protected ?string $controller = null;
    protected ?string $action = null;
    protected Metadata $metadata;
    protected ParameterValues $parameterValues;

    /**
     * @param string[] $httpMethods
     */
    public function __construct(
        array $httpMethods,
        string $name,
        string $url,
        mixed $callable,
        array $metadata = []
    ) {
        $this->httpMethods = $httpMethods;
        $this->name = $name;
        $this->url = '/' . trim($url, '/');
        $this->metadata = new Metadata($metadata);

        if (is_array($callable) && count($callable) === 2) {
            [$this->controller, $this->action] = $callable;
        } elseif (is_callable($callable)) {
            $this->callable = $callable;
        }

        $this->parameterValues = new ParameterValues();
    }

    /**
     * @return string[]
     */
    public function getHttpMethods(): array
    {
        return $this->httpMethods;
    }

    public function supportsHttpMethod(string $httpMethod): bool
    {
        return in_array($httpMethod, $this->httpMethods, true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getCallable(): ?callable
    {
        return $this->callable;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setParameterValues(ParameterValues $values): void
    {
        $parameters = $this->getParameters();

        if (count($values->getAll()) !== count($parameters)) {
            throw new RoutingException('Number of values do not match Route parameters.');
        }

        if (array_diff($values->getNames(), $parameters)) {
            throw new RoutingException('Values names do not match Route parameters.');
        }

        $this->parameterValues = $values;
    }

    public function getParameterValue(string $name): mixed
    {
        return $this->parameterValues->get($name);
    }

    public function getParameterValues(): ParameterValues
    {
        return $this->parameterValues;
    }

    public function hasParameterValues(): bool
    {
        return count($this->parameterValues->getAll()) > 0;
    }

    public function getParameters(): array
    {
        if (!$this->hasParameters()) {
            return [];
        }

        $urlParts = explode('/', $this->url);

        $parameters = [];
        foreach ($urlParts as $part) {
            if (!str_contains($part, '{')) {
                continue;
            }

            $parameters[] = trim($part, '{}');
        }

        return $parameters;
    }

    public function hasParameters(): bool
    {
        return str_contains($this->url, '{');
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function hasMetadataValues(): bool
    {
        return count($this->metadata->getAll()) > 0;
    }

    public function getMetadataValue(string $name)
    {
        return $this->metadata->get($name);
    }
}
