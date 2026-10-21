<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\Renderers\TemplateRenderer;
use PHPUnit\Framework\TestCase;

final class TemplateRendererTest extends TestCase
{
    public function testRenderingUsesTheModulePathAndAnIsolatedTemplateScope(): void
    {
        \Context::getContext()->smarty = new class {
            public array $paths = [];

            public function assign($data): void
            {
                throw new \LogicException('Do not assign renderer data globally.');
            }

            public function createTemplate($path): object
            {
                $this->paths[] = $path;

                return new class {
                    private array $data = [];

                    public function assign($data): void
                    {
                        $this->data = $data;
                    }

                    public function fetch(): string
                    {
                        return json_encode($this->data, JSON_THROW_ON_ERROR);
                    }
                };
            }
        };
        $module = new class extends \Module {
            public $name = 'renderertest';

            public function getLocalPath(): string
            {
                return '/srv/modules/renderertest/';
            }

            public function getPathUri(): string
            {
                return '/shop/modules/renderertest/';
            }
        };
        $renderer = new TemplateRenderer($module);
        $first = json_decode($renderer->render('front/card.tpl', ['title' => 'First']), true);
        $second = json_decode($renderer->render('front/card.tpl'), true);
        self::assertSame('/shop/modules/renderertest/', $first['module_path_uri']);
        self::assertSame('First', $first['title']);
        self::assertArrayNotHasKey('title', $second);
        self::assertSame('module:renderertest/views/templates/front/card.tpl', \Context::getContext()->smarty->paths[0]);
        $this->expectException(\InvalidArgumentException::class);
        $renderer->render('../private.tpl');
    }
}
