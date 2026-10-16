<?php

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\Hooks\Contracts\HookGroup;
use Dartmoon\Hooks\HookDispatcher;
use Dartmoon\Hooks\Traits\HasHookDispatcher;
use PHPUnit\Framework\TestCase;

final class HooksTest extends TestCase
{
    public function testCanonicalNamesCaseAndMultipleHandlers(): void
    {
        $group = new class implements HookGroup {
            public function getHooks() { return 'displayHeader'; }
            public function displayHeader(array $params) { return $params['text']; }
        };
        $dispatcher = new HookDispatcher();
        $dispatcher->register($group);
        $dispatcher->register($group);
        self::assertSame(['displayHeader'], $dispatcher->getAvailableHooks());
        self::assertSame('one', $dispatcher->dispatch('DISPLAYHEADER', ['text' => 'one']));
        $dispatcher->register(clone $group);
        self::assertSame('oneone', $dispatcher->dispatch('displayHeader', ['text' => 'one']));
        self::assertNull($dispatcher->dispatch('unknown'));
    }
    public function testSingleNonDisplayResultIsPreserved(): void
    {
        $group = new class implements HookGroup {
            public function getHooks() { return ['filterProduct']; }
            public function filterProduct(array $params) { return $params; }
        };
        $dispatcher = new HookDispatcher();
        $dispatcher->register($group);
        self::assertSame(['id' => 1], $dispatcher->dispatch('filterProduct', ['id' => 1]));
        $dispatcher->register(clone $group);
        $this->expectException(\LogicException::class);
        $dispatcher->dispatch('filterProduct', ['id' => 1]);
    }
    public function testMissingHandlerFailsAtRegistration(): void
    {
        $group = new class implements HookGroup { public function getHooks() { return ['missing']; } };
        $this->expectException(\InvalidArgumentException::class);
        (new HookDispatcher())->register($group);
    }
    public function testMagicCallsOnlyDispatchHooks(): void
    {
        $module = new class extends \Module { use HasHookDispatcher; };
        self::assertNull($module->hookUnknown([]));
        $this->expectException(\BadMethodCallException::class);
        $module->typo();
    }
}
