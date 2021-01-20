<?php declare(strict_types=1);

namespace Parable\Routing\Route;

class ParameterValues
{
    protected array $values = [];

    /**
     * @var string[]
     */
    protected array $valueNamesInOrder = [];

    public function __construct(array $values = [])
    {
        $this->setMany($values);
    }

    public function set(string $name, $value): void
    {
        $this->values[$name] = $value;
        $this->valueNamesInOrder[] = $name;
    }

    public function setMany(array $values): void
    {
        foreach ($values as $name => $value) {
            $this->set($name, $value);
        }
    }

    public function get(string $name)
    {
        return $this->values[$name] ?? null;
    }

    public function getAll(): array
    {
        $return = [];

        foreach ($this->valueNamesInOrder as $name) {
            $return[] = $this->values[$name];
        }

        return $return;
    }

    public function getNames(): array
    {
        return $this->valueNamesInOrder;
    }
}
