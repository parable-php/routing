<?php declare(strict_types=1);

namespace Parable\Routing\Tests\Classes;

class Controller
{
    public function simple(): string
    {
        return 'simple action';
    }

    public static function complex(string $id, string $name): string
    {
        return 'complex action: ' . $id . '/' . $name;
    }

    public static function catchAll(string $id, string $name): string
    {
        return 'catchall action: ' . $id . '/' . $name;
    }
}
