<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Hooks\Contracts;

interface HookDispatcher
{
    public function register(HookGroup $group): void;

    public function getAvailableHooks(): array;

    public function dispatch(string $hookName, array $params = []): mixed;
}
