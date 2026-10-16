<?php

declare(strict_types=1);

namespace Dartmoon\TabManager\Facades;

class TabManager
{
    public static function __callStatic(string $name, array $arguments): mixed
    {
        return (new \Dartmoon\TabManager\TabManager())->{$name}(...$arguments);
    }
}
