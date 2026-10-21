<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Filters\Filters;

class Slugify extends AbstractFilter
{
    protected $name = 'slugify';

    public function filter(mixed $value, array $params): string
    {
        $text = preg_replace('/[^\pL\pN]+/u', '-', (string) $value);
        if ($text === null) {
            throw new \InvalidArgumentException('The slugify filter requires valid UTF-8 text.');
        }
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = preg_replace('/[^a-z0-9-]+/', '', strtolower($text));
        $text = trim(preg_replace('/-+/', '-', $text), '-');

        return $text === '' ? 'n-a' : $text;
    }
}
