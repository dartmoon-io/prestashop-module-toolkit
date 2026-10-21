<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Filters\Filters;

class Clean extends AbstractFilter
{
    protected $name = 'clean';

    public function filter(mixed $value, array $params): string
    {
        $value = preg_replace('/[<>;=#{}]+/u', '', (string) $value);
        if ($value === null) {
            throw new \InvalidArgumentException('The clean filter requires valid UTF-8 text.');
        }

        return trim($value);
    }
}
