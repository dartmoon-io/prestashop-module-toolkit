<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Utils;

class MultiLangText
{
    /** Include disabled languages so ObjectModel translations remain complete. */
    public function generate(string|array $text, ?array $languages = null): array
    {
        $translated = [];
        foreach ($languages ?? \Language::getLanguages(false) as $language) {
            $id = (int) $language['id_lang'];
            if (is_string($text)) {
                $translated[$id] = $text;
                continue;
            }
            $value = $text === [] ? '' : (string) reset($text);
            foreach (['locale', 'language_code', 'iso_code', 'id_lang'] as $key) {
                if (isset($language[$key]) && array_key_exists($language[$key], $text)) {
                    $value = (string) $text[$language[$key]];
                    break;
                }
            }
            $translated[$id] = $value;
        }

        return $translated;
    }
}
