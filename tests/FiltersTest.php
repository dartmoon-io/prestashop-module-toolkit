<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\Filters\Filters\AbstractFilter;
use Dartmoon\Filters\FilterService;
use PHPUnit\Framework\TestCase;

final class FiltersTest extends TestCase
{
    public function testBuiltInPipelinePreservesZeroAndUnicode(): void
    {
        $filter = new FilterService();
        self::assertSame('Red Chair!', $filter->filter(' <red> CHAIR ', 'clean|title|append:!'));
        self::assertSame('0', $filter->filter('0', 'clean'));
        self::assertSame('caffe-creme', $filter->filter('Caffè crème', 'slugify'));
        self::assertSame('n-a', $filter->filter('!!!', 'slugify'));
        self::assertSame('È Già Pronto', $filter->filter('è già pronto', 'title'));
        self::assertSame('xhttps://host/path', $filter->filter('x', 'append:https://host/path'));
        self::assertSame('xa|b:c', $filter->filter('x', ['append' => ['a|b:c']]));
        self::assertNull($filter->filter(null, ''));
    }

    public function testCustomFiltersBelongToTheirServiceInstance(): void
    {
        $custom = new class extends AbstractFilter {
            protected $name = 'title';

            public function filter(mixed $value, array $params): string
            {
                return 'custom';
            }
        };
        self::assertSame('custom', (new FilterService([$custom]))->filter('hello', 'title'));
        self::assertSame('Hello', (new FilterService())->filter('hello', 'title'));
    }

    /** @dataProvider invalidFilters */
    public function testInvalidPipelinesAreReported(string|array $pipeline): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new FilterService())->filter('text', $pipeline);
    }

    public static function invalidFilters(): array
    {
        return [['missing'], ['append'], [['append' => []]], ['clean||title'], [[123]]];
    }

    public function testInvalidUtf8IsReported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new FilterService())->filter("\xFF", 'clean');
    }
}
