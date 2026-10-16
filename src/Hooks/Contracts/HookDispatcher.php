<?php

declare(strict_types=1);

namespace Dartmoon\Hooks\Contracts;

interface HookDispatcher
{
    public function register(HookGroup $group): void;
    public function getAvailableHooks(): array;
    public function dispatch(string $hookName, array $params = []): mixed;
}
