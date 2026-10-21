<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Renderers;

class TemplateRenderer
{
    public function __construct(private \Module $module)
    {
    }

    public function render(string $template, array $data = []): string
    {
        if (!preg_match('~^[a-zA-Z0-9_/-]+\.tpl$~D', $template) || str_starts_with($template, '/')) {
            throw new \InvalidArgumentException('Use a relative Smarty template path without traversal.');
        }
        $view = \Context::getContext()->smarty->createTemplate(
            'module:' . $this->module->name . '/views/templates/' . $template
        );
        $view->assign(array_replace([
            'module_path' => $this->module->getLocalPath(),
            'module_path_uri' => $this->module->getPathUri(),
        ], $data));

        return $view->fetch();
    }
}
