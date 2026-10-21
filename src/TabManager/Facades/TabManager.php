<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\TabManager\Facades;

use Dartmoon\Support\Facade;

/** @method static \Dartmoon\TabManager\TabManager for(object $scope) */
class TabManager extends Facade
{
    protected static function serviceClass(): string
    {
        return \Dartmoon\TabManager\TabManager::class;
    }
}
