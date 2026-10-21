<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\Config\Config;
use Dartmoon\Config\Facades\Config as Facade;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/dm-config-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
        file_put_contents($this->directory . '/module.php', "<?php return ['nested' => ['value' => 42, 'null' => null, 'false' => false]];");
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    public function testNestedValuesNullFalseAndDefaults(): void
    {
        $config = new Config($this->directory);
        self::assertSame(42, $config->get('module.nested.value'));
        self::assertNull($config->get('module.nested.null', 'fallback'));
        self::assertFalse($config->get('module.nested.false', 'fallback'));
        self::assertSame('fallback', $config->get('module.nested.missing', 'fallback'));
        self::assertSame([], $config->get('missing'));
        self::assertSame('fallback', $config->get('missing.key', 'fallback'));
    }

    public function testRepositoriesCacheIndependently(): void
    {
        $first = new Config($this->directory);
        self::assertSame(42, $first->get('module.nested.value'));
        file_put_contents($this->directory . '/module.php', '<?php return ["nested" => ["value" => 7]];');
        self::assertSame(42, $first->get('module.nested.value'));
        self::assertSame(7, (new Config($this->directory))->get('module.nested.value'));
        $scope = new \stdClass();
        Facade::swap($scope, $first);
        self::assertSame(42, Facade::for($scope)->get('module.nested.value'));
    }

    public function testRejectsPathTraversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Config($this->directory))->getConfigFile('../secret');
    }

    public function testRejectsNonArrayFiles(): void
    {
        file_put_contents($this->directory . '/bad.php', '<?php return "bad";');
        $this->expectException(\UnexpectedValueException::class);
        (new Config($this->directory))->get('bad');
    }

    public function testFacadeRequiresExplicitRepository(): void
    {
        $this->expectException(\LogicException::class);
        Facade::for(new \stdClass());
    }
}
