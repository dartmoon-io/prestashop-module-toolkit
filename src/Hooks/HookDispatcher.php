<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Hooks;

use Dartmoon\Hooks\Contracts\HookDispatcher as DispatcherContract;
use Dartmoon\Hooks\Contracts\HookGroup;

class HookDispatcher implements DispatcherContract
{
    private array $hooks = [];

    public function register(HookGroup $group): void
    {
        foreach ((array) $group->getHooks() as $hook) {
            if (!is_string($hook) || !preg_match('/^[a-z][a-z0-9_]*$/iD', $hook) || !is_callable([$group, $hook])) {
                throw new \InvalidArgumentException('Each hook must have a public handler with the same name.');
            }
            $key = strtolower($hook);
            $this->hooks[$key] ??= ['name' => $hook, 'handlers' => []];
            // Registering the same instance twice must not execute it twice.
            $this->hooks[$key]['handlers'][spl_object_id($group)] = $group;
        }
    }

    public function getAvailableHooks(): array
    {
        return array_column($this->hooks, 'name');
    }

    public function dispatch(string $hookName, array $params = []): mixed
    {
        $hook = $this->hooks[strtolower($hookName)] ?? null;
        // PrestaShop probes hooks through __call(), including hooks we do not implement.
        if ($hook === null) {
            return null;
        }
        $results = [];
        foreach ($hook['handlers'] as $handler) {
            $results[] = $handler->{$hook['name']}($params);
        }
        if (count($results) === 1) {
            return $results[0];
        }
        $output = '';
        foreach ($results as $result) {
            if ($result === null) {
                continue;
            }
            if (!is_string($result)) {
                throw new \LogicException('Multiple handlers are supported only for void or string (display) hooks.');
            }
            $output .= $result;
        }

        return $output === '' && count(array_filter($results, 'is_string')) === 0 ? null : $output;
    }
}
