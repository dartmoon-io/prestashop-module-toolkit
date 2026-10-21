<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\Assets\AssetManager;
use PHPUnit\Framework\TestCase;

final class AssetManagerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/dartmoon-assets-' . bin2hex(random_bytes(6));
        mkdir($this->directory . '/views/assets', 0777, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->directory . '/views/assets/manifest.json');
        rmdir($this->directory . '/views/assets');
        rmdir($this->directory . '/views');
        rmdir($this->directory);
    }

    private function manifest(array $manifest): void
    {
        file_put_contents($this->directory . '/views/assets/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
    }

    public function testResolvesModuleUrlsAndEscapesHtml(): void
    {
        $directory = $this->directory;
        $module = new class($directory) extends \Module {
            public function __construct(private string $directory)
            {
            }

            public function getLocalPath(): string
            {
                return $this->directory . '/';
            }

            public function getPathUri(): string
            {
                return '/shop/modules/a&b/';
            }
        };
        $this->manifest(['front.js' => ['file' => 'front-abc.js']]);
        $assets = AssetManager::forModule($module);
        self::assertSame('/shop/modules/a&b/views/assets/front-abc.js', $assets->url('front.js'));
        self::assertSame('<script type="module" src="/shop/modules/a&amp;b/views/assets/front-abc.js"></script>' . "\n", $assets->render('front.js'));
    }

    public function testRendersImportedCssInOrderWithoutDuplicatesOrEagerDynamicImports(): void
    {
        $this->manifest([
            'front.js' => ['file' => 'front-abc.js', 'imports' => ['_shared'], 'css' => ['front.css'], 'dynamicImports' => ['lazy.js']],
            '_shared' => ['file' => 'shared.js', 'imports' => ['front.js'], 'css' => ['shared.css']],
            'front.scss' => ['file' => 'front.css'],
            'back.scss' => ['file' => 'front.css'],
            'lazy.js' => ['file' => 'lazy.js', 'css' => ['lazy.css']],
        ]);
        $assets = new AssetManager($this->directory, '/modules/test/');
        self::assertSame(
            '<link rel="stylesheet" href="/modules/test/views/assets/shared.css">' . "\n"
            . '<link rel="stylesheet" href="/modules/test/views/assets/front.css">' . "\n"
            . '<script type="module" src="/modules/test/views/assets/front-abc.js"></script>' . "\n",
            $assets->render(['front.js', 'front.scss', 'back.scss', 'front.js'])
        );
    }

    public function testMissingBuildIsOptionalAndDoesNotCacheTheAbsence(): void
    {
        $assets = new AssetManager($this->directory, '/modules/test');
        self::assertNull($assets->url('front.js'));
        self::assertSame('', $assets->render('front.js'));
        $this->manifest(['front.js' => ['file' => 'front.js']]);
        self::assertSame('/modules/test/views/assets/front.js', $assets->url('front.js'));
    }

    public function testManagersKeepModuleUrlsAndCustomBuildDirectoriesSeparate(): void
    {
        $this->manifest(['front.js' => ['file' => 'front.js']]);
        $first = new AssetManager($this->directory, '/shop/modules/one');
        $second = new AssetManager($this->directory . '/views', '/shop/modules/two/', 'assets');
        self::assertSame('/shop/modules/one/views/assets/front.js', $first->url('front.js'));
        self::assertSame('/shop/modules/two/assets/front.js', $second->url('front.js'));
    }

    public function testMissingEntryRaisesAnActionableError(): void
    {
        $this->manifest(['front.js' => ['file' => 'front.js']]);
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('missing.js');
        (new AssetManager($this->directory, '/modules/test'))->render('missing.js');
    }

    public function testInvalidJsonIsReported(): void
    {
        file_put_contents($this->directory . '/views/assets/manifest.json', '{broken');
        $this->expectException(\JsonException::class);
        (new AssetManager($this->directory, '/modules/test'))->render('front.js');
    }

    /** @dataProvider invalidManifests */
    public function testMalformedManifestsAreReported(array $manifest): void
    {
        $this->manifest($manifest);
        $this->expectException(\UnexpectedValueException::class);
        (new AssetManager($this->directory, '/modules/test'))->render('front.js');
    }

    public static function invalidManifests(): array
    {
        return [
            'missing file' => [['front.js' => []]],
            'wrong file type' => [['front.js' => ['file' => 123]]],
            'css is not a list' => [['front.js' => ['file' => 'front.js', 'css' => 'style.css']]],
            'imports contain nonstrings' => [['front.js' => ['file' => 'front.js', 'imports' => [null]]]],
        ];
    }

    /** @dataProvider unsafePaths */
    public function testUnsafeManifestPathsAreRejected(string $path): void
    {
        $this->manifest(['front.js' => ['file' => $path]]);
        $this->expectException(\UnexpectedValueException::class);
        (new AssetManager($this->directory, '/modules/test'))->url('front.js');
    }

    public static function unsafePaths(): array
    {
        return array_map(static fn (string $path): array => [$path], [
            '../outside.js', '/outside.js', 'https://example.com/evil.js',
            'nested\\outside.js', '%2e%2e/outside.js', "bad\0name.js",
        ]);
    }
}
