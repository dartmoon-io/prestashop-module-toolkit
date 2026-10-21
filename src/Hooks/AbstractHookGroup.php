<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Hooks;

use Dartmoon\Hooks\Contracts\HookGroup;

abstract class AbstractHookGroup implements HookGroup
{
    protected $hooks = [];

    public function __construct(protected \Module $module, protected \Context $context)
    {
    }

    public function getHooks()
    {
        return $this->hooks;
    }
}
