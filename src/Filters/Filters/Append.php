<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Filters\Filters;

class Append extends AbstractFilter
{
    protected $name = 'append';

    public function filter(mixed $value, array $params): string
    {
        if (count($params) !== 1 || !is_scalar($params[0])) {
            throw new \InvalidArgumentException('The append filter requires one scalar suffix.');
        }

        return (string) $value . $params[0];
    }
}
