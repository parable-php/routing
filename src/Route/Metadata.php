<?php declare(strict_types=1);

namespace Parable\Routing\Route;

class Metadata
{
    /**
     * @var array
     */
    protected $values = [];

    public function __construct(array $values = [])
    {
        $this->setMany($values);
    }

    public function set(string $name, $value): void
    {
        $this->values[$name] = $value;
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
        return $this->values;
    }
}
