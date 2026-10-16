<?php

declare(strict_types=1);

namespace Dartmoon\Hooks\Contracts;

interface HookGroup
{
    /** @return string|string[] Canonical PrestaShop hook names, without the hook prefix. */
    public function getHooks();
}
