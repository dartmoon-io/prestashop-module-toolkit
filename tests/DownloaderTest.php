<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\Downloaders\FTPDownloader;
use Dartmoon\Downloaders\FtpTestState;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FtpFunctions.php';

final class DownloaderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        if (!function_exists('ftp_connect')) {
            self::markTestSkipped('ext-ftp is required for the FTP component.');
        }
        $this->directory = sys_get_temp_dir() . '/dartmoon-ftp-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
        FtpTestState::$events = [];
        FtpTestState::$failure = null;
    }

    protected function tearDown(): void
    {
        if (isset($this->directory)) {
            foreach (glob($this->directory . '/{,.}*', GLOB_BRACE) as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->directory);
        }
    }

    private function credentials(): array
    {
        return ['host' => 'unused.example', 'user' => 'test', 'password' => 'private-test-password'];
    }

    public function testSuccessPublishesOnlyTheCompleteFileAndClosesConnection(): void
    {
        $path = $this->directory . '/feed.csv';
        file_put_contents($path, 'previous bytes');
        self::assertSame($path, (new FTPDownloader())->download('/feed.csv', $path, $this->credentials()));
        self::assertSame('downloaded bytes', file_get_contents($path));
        self::assertSame(['connect', 'unused.example', 21, 30], FtpTestState::$events[0]);
        self::assertContains(['passive', true], FtpTestState::$events);
        self::assertSame(['close'], end(FtpTestState::$events));
    }

    /** @dataProvider failures */
    public function testFailuresKeepTheExistingFileAndRemoveTemporaryDownloads(string $stage): void
    {
        FtpTestState::$failure = $stage;
        $path = $this->directory . '/feed.csv';
        file_put_contents($path, 'previous bytes');
        try {
            (new FTPDownloader())->download('/feed.csv', $path, $this->credentials());
            self::fail('The failed download should throw.');
        } catch (\RuntimeException $exception) {
            self::assertStringNotContainsString('private-test-password', $exception->getMessage());
        }
        self::assertSame('previous bytes', file_get_contents($path));
        self::assertSame([], glob($this->directory . '/.ftp-*'));
        self::assertSame($stage === 'connect' ? 0 : 1, count(array_filter(FtpTestState::$events, static fn (array $event): bool => $event[0] === 'close')));
    }

    public static function failures(): array
    {
        return [['connect'], ['login'], ['passive'], ['get'], ['throw']];
    }

    public function testSslAndActiveModeAreExplicit(): void
    {
        if (!function_exists('ftp_ssl_connect')) {
            self::markTestSkipped('FTPS is unavailable.');
        }
        (new FTPDownloader())->download('/feed.csv', $this->directory . '/feed.csv', $this->credentials() + ['ssl' => true, 'passive' => false]);
        self::assertContains(['ssl'], FtpTestState::$events);
        self::assertContains(['passive', false], FtpTestState::$events);
    }
}
