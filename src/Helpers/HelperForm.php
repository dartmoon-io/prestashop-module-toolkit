<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Helpers;

use Dartmoon\Renderers\HtmlAttributes;

/** Extend field definitions while letting the current core render its form. */
class HelperForm extends \HelperForm
{
    public function generate()
    {
        $this->fields_form = self::prepareFields($this->fields_form, $this->fields_value);

        return parent::generate();
    }

    public static function prepareFields(array $fieldsets, array $values): array
    {
        foreach ($fieldsets as &$fieldset) {
            foreach ($fieldset['form']['input'] ?? [] as $key => $input) {
                if (isset($input['condition']) && !$input['condition']) {
                    unset($fieldset['form']['input'][$key]);
                    continue;
                }
                $type = $input['type'] ?? '';
                if (!in_array($type, ['number', 'vector3', 'separator', 'tab-description'], true)) {
                    if ($type === 'switch') {
                        $fieldset['form']['input'][$key]['values'] ??= [];
                    }
                    continue;
                }
                $name = $input['name'] ?? '';
                $escape = static fn (mixed $text): string => htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                if ($type === 'separator' || $type === 'tab-description') {
                    $html = '<h4>' . $escape($input['content'] ?? '') . '</h4>';
                } else {
                    $attributes = $input['attributes'] ?? [];
                    if (!is_array($attributes)) {
                        throw new \InvalidArgumentException('Field attributes must be an array.');
                    }
                    // Definitions cannot replace the field identity, type or submitted value.
                    unset($attributes['name'], $attributes['id'], $attributes['type'], $attributes['value']);
                    if ($type === 'number') {
                        $html = '<input ' . HtmlAttributes::render(array_merge($attributes, [
                            'type' => 'number', 'name' => $name, 'id' => $input['id'] ?? $name,
                            'value' => $values[$name] ?? '', 'required' => (bool) ($input['required'] ?? false),
                            'disabled' => (bool) ($input['disabled'] ?? false),
                        ])) . '>';
                    } else {
                        $coordinates = preg_split('/\s+/', trim((string) ($values[$name] ?? '0 0 0')));
                        if (count($coordinates) !== 3 || count(array_filter($coordinates, 'is_numeric')) !== 3) {
                            throw new \InvalidArgumentException('A vector3 value requires three numbers.');
                        }
                        $html = '<div data-dartmoon-vector><input ' . HtmlAttributes::render([
                            'type' => 'hidden', 'name' => $name, 'value' => implode(' ', $coordinates),
                            'disabled' => (bool) ($input['disabled'] ?? false),
                        ]) . '><div class="row">';
                        foreach (['x', 'y', 'z'] as $axis => $label) {
                            $html .= '<div class="col-lg-4"><input ' . HtmlAttributes::render(array_merge($attributes, [
                                'type' => 'number', 'step' => $attributes['step'] ?? 'any', 'data-axis' => $label,
                                'aria-label' => ($input['label'] ?? $name) . ' ' . $label,
                                'value' => $coordinates[$axis], 'required' => true,
                                'disabled' => (bool) ($input['disabled'] ?? false),
                            ])) . '></div>';
                        }
                        $html .= '</div></div>';
                    }
                }
                $fieldset['form']['input'][$key] = array_replace($input, ['type' => 'html', 'html_content' => $html]);
            }
        }
        unset($fieldset);

        return $fieldsets;
    }
}
