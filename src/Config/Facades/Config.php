<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Config\Facades;

use Dartmoon\Support\Facade;

/** @method static \Dartmoon\Config\Config for(object $scope) */
class Config extends Facade
{
    protected static function serviceClass(): string
    {
        return \Dartmoon\Config\Config::class;
    }

    protected static function create(object $scope): object
    {
        if (!$scope instanceof \Module) {
            throw new \LogicException('Bind a repository with swap() or supply a Module scope.');
        }

        return new \Dartmoon\Config\Config($scope->getLocalPath() . '/config');
    }
}
