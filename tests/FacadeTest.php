<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\Filters\Facades\Filter;
use Dartmoon\Filters\Filters\AbstractFilter;
use Dartmoon\Filters\FilterService;
use Dartmoon\Utils\Facades\MultiLangText;
use PHPUnit\Framework\TestCase;

final class FacadeTest extends TestCase
{
    public function testBindingsAreIsolatedByScopeAndFacade(): void
    {
        $one = new \stdClass();
        $two = new \stdClass();
        $custom = new class extends AbstractFilter {
            protected $name = 'title';

            public function filter(mixed $value, array $params): string
            {
                return 'custom';
            }
        };
        Filter::swap($one, new FilterService([$custom]));
        self::assertSame('custom', Filter::for($one)->filter('hello', 'title'));
        self::assertSame('Hello', Filter::for($two)->filter('hello', 'title'));
        self::assertInstanceOf(\Dartmoon\Utils\MultiLangText::class, MultiLangText::for($one));
        self::assertSame(Filter::for($one), Filter::for($one));
        Filter::forget($one);
        self::assertSame('Hello', Filter::for($one)->filter('hello', 'title'));
    }

    public function testReleasedModuleDoesNotRemainInRegistry(): void
    {
        $scope = new \stdClass();
        $reference = \WeakReference::create($scope);
        Filter::for($scope);
        unset($scope);
        self::assertNull($reference->get());
    }

    public function testWrongBindingIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Filter::swap(new \stdClass(), new \stdClass());
    }
}
