<?php

declare(strict_types=1);

namespace Dartmoon\Hooks\Traits;

use BadMethodCallException;
use Dartmoon\Hooks\HookDispatcher;

trait HasHookDispatcher
{
    protected ?HookDispatcher $hookDispatcher = null;

    protected function initHookDispatcher(): void
    {
        $this->hookDispatcher = new HookDispatcher();
        foreach ($this->hooks ?? [] as $hookClass) {
            $this->hookDispatcher->register(new $hookClass($this, $this->context));
        }
    }

    public function getHookDispatcher(): HookDispatcher
    {
        if ($this->hookDispatcher === null) {
            $this->initHookDispatcher();
        }

        return $this->hookDispatcher;
    }

    public function __call(string $methodName, array $arguments): mixed
    {
        if (strncasecmp($methodName, 'hook', 4) !== 0) {
            throw new BadMethodCallException(sprintf('Unknown method %s::%s()', static::class, $methodName));
        }

        return $this->getHookDispatcher()->dispatch(substr($methodName, 4), $arguments[0] ?? []);
    }
}
