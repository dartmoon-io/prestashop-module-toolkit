<?php

declare(strict_types=1);

namespace Dartmoon\Utils;

use Language;

class MultiLangText
{
    /** Include disabled languages so ObjectModel translations remain complete. */
    public function generate(string|array $text, ?array $languages = null): array
    {
        $translated = [];
        foreach ($languages ?? Language::getLanguages(false) as $language) {
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
