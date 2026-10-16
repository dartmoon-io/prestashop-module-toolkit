<?php

declare(strict_types=1);

namespace Dartmoon\Config\Facades;

use Dartmoon\Config\Config as Repository;
use LogicException;

/** Compatibility facade. Prefer injecting a module-specific Repository. */
class Config
{
    private static ?Repository $repository = null;

    public static function setRepository(?Repository $repository): void
    {
        self::$repository = $repository;
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        if (self::$repository === null) {
            throw new LogicException('Configure the Config facade with setRepository() before use.');
        }

        return self::$repository->{$name}(...$arguments);
    }
}
