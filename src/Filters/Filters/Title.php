<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Filters\Filters;

class Title extends AbstractFilter
{
    protected $name = 'title';

    public function filter(mixed $value, array $params): string
    {
        if (!function_exists('mb_convert_case')) {
            throw new \LogicException('The title filter requires ext-mbstring.');
        }

        return mb_convert_case((string) $value, MB_CASE_TITLE, 'UTF-8');
    }
}
