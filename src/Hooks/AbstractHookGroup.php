<?php

declare(strict_types=1);

namespace Dartmoon\Hooks;

use Context;
use Dartmoon\Hooks\Contracts\HookGroup;
use Module;

abstract class AbstractHookGroup implements HookGroup
{
    protected $hooks = [];

    public function __construct(protected Module $module, protected Context $context)
    {
    }

    public function getHooks()
    {
        return $this->hooks;
    }
}
