<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Renderers;

final class HtmlAttributes
{
    public static function render(array $attributes): string
    {
        $result = [];
        foreach ($attributes as $name => $value) {
            if (!is_string($name) || !preg_match('/^[a-zA-Z_:][a-zA-Z0-9_.:-]*$/D', $name) || preg_match('/^on/i', $name)) {
                throw new \InvalidArgumentException('Invalid HTML attribute name.');
            }
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $result[] = $name;
            } elseif (is_scalar($value) || $value instanceof \Stringable) {
                $result[] = $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            } else {
                throw new \InvalidArgumentException('HTML attribute values must be scalar.');
            }
        }

        return implode(' ', $result);
    }
}
