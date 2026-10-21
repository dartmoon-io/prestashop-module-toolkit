<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Filters\Facades;

use Dartmoon\Support\Facade;

/** @method static \Dartmoon\Filters\FilterService for(object $scope) */
class Filter extends Facade
{
    protected static function serviceClass(): string
    {
        return \Dartmoon\Filters\FilterService::class;
    }
}
