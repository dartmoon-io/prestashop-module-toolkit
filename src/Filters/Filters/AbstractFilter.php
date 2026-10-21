<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Filters\Filters;

abstract class AbstractFilter
{
    protected $name = 'filter';

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function filter(mixed $value, array $params): mixed;
}
