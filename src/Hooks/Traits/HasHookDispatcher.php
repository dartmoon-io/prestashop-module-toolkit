<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Hooks\Traits;

use Dartmoon\Hooks\Contracts\HookDispatcher as DispatcherContract;
use Dartmoon\Hooks\Contracts\HookGroup;
use Dartmoon\Hooks\HookDispatcher;

trait HasHookDispatcher
{
    protected ?DispatcherContract $hookDispatcher = null;

    protected function initHookDispatcher(): void
    {
        $this->hookDispatcher = $this->createHookDispatcher();
        foreach ($this->hooks ?? [] as $hookClass) {
            $this->hookDispatcher->register($this->createHookGroup($hookClass));
        }
    }

    protected function createHookDispatcher(): DispatcherContract
    {
        return new HookDispatcher();
    }

    /** Override to inject handler dependencies using your container or factories. */
    protected function createHookGroup(string|HookGroup $group): HookGroup
    {
        return $group instanceof HookGroup ? $group : new $group($this, $this->context);
    }

    public function getHookDispatcher(): DispatcherContract
    {
        if ($this->hookDispatcher === null) {
            $this->initHookDispatcher();
        }

        return $this->hookDispatcher;
    }

    public function __call(string $methodName, array $arguments): mixed
    {
        if (strncasecmp($methodName, 'hook', 4) !== 0) {
            throw new \BadMethodCallException(sprintf('Unknown method %s::%s()', static::class, $methodName));
        }

        return $this->getHookDispatcher()->dispatch(substr($methodName, 4), $arguments[0] ?? []);
    }
}
