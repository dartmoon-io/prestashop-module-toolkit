<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Helpers;

class HelperList extends \HelperList
{
    public function generateList($list, $fields_display)
    {
        return parent::generateList(self::prepareRows($list, $fields_display), $fields_display);
    }

    /** Core invokes a column callback only when the row contains its field key. */
    public static function prepareRows(array $rows, array $fields): array
    {
        foreach ($rows as &$row) {
            foreach ($fields as $name => $definition) {
                if (isset($definition['callback'])) {
                    $parts = explode('!', $name, 2);
                    $key = $parts[1] ?? $parts[0];
                    if (!array_key_exists($key, $row)) {
                        $row[$key] = null;
                    }
                }
            }
        }
        unset($row);

        return $rows;
    }
}
