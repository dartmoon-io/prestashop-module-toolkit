<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Support;

/** Explicit scope avoids a process-wide current module, including in workers. */
abstract class Facade
{
    private static ?\WeakMap $scopes = null;

    abstract protected static function serviceClass(): string;

    protected static function create(object $scope): object
    {
        $class = static::serviceClass();

        return new $class();
    }

    /** Resolve once per module object; release the scope when the module is released. */
    public static function for(object $scope): object
    {
        self::$scopes ??= new \WeakMap();
        $services = self::$scopes[$scope] ?? [];
        $services[static::class] ??= static::create($scope);
        self::$scopes[$scope] = $services;

        return $services[static::class];
    }

    /** Bind a subclass/implementation for this facade and module only. */
    public static function swap(object $scope, object $service): void
    {
        $class = static::serviceClass();
        if (!$service instanceof $class) {
            throw new \InvalidArgumentException('Expected an implementation of ' . $class);
        }
        self::$scopes ??= new \WeakMap();
        $services = self::$scopes[$scope] ?? [];
        $services[static::class] = $service;
        self::$scopes[$scope] = $services;
    }

    public static function forget(object $scope): void
    {
        if (self::$scopes !== null && isset(self::$scopes[$scope])) {
            $services = self::$scopes[$scope];
            unset($services[static::class]);
            self::$scopes[$scope] = $services;
        }
    }
}
