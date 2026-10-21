<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Hooks\Contracts;

interface HookGroup
{
    /** @return string|string[] Canonical PrestaShop hook names, without the hook prefix. */
    public function getHooks();
}
