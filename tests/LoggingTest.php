<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\Log\Log;
use PHPUnit\Framework\TestCase;

final class LoggingTest extends TestCase
{
    public function testDailyLogsAreIsolatedQuietAndRespectLevels(): void
    {
        self::assertSame(1, Log::ERROR);
        self::assertSame(2, Log::DEBUG);
        $directory = sys_get_temp_dir() . '/dartmoon-logs-' . bin2hex(random_bytes(6));
        try {
            $one = new Log($directory . '/one', Log::ERROR);
            $two = new Log($directory . '/two');
            $disabled = new Log($directory . '/disabled', 0);
            ob_start();
            $one->debug('hidden');
            $one->error("failure\nforged line");
            $two->debug('other module');
            $disabled->error('disabled');
            self::assertSame('', ob_get_clean());
            $first = file_get_contents(glob($directory . '/one/*.log')[0]);
            self::assertStringContainsString('[ERROR] failure\\nforged line', $first);
            self::assertSame(1, substr_count($first, PHP_EOL));
            self::assertStringNotContainsString('hidden', $first);
            self::assertStringNotContainsString('other module', $first);
            self::assertDirectoryDoesNotExist($directory . '/disabled');
            ob_start();
            $two->log('visible', Log::DEBUG, true);
            self::assertSame('visible' . PHP_EOL, ob_get_clean());

            $levels = [
                Log::EMERGENCY => 'EMERGENCY', Log::ALERT => 'ALERT',
                Log::CRITICAL => 'CRITICAL', Log::ERROR => 'ERROR',
                Log::WARNING => 'WARNING', Log::NOTICE => 'NOTICE',
                Log::INFO => 'INFO', Log::DEBUG => 'DEBUG',
            ];
            foreach (array_merge([0], array_keys($levels)) as $rank => $threshold) {
                $path = $directory . '/' . $threshold;
                $logger = new Log($path, $threshold);
                ob_start();
                foreach ($levels as $level => $label) {
                    $logger->{strtolower($label)}('message');
                    $logger->log('generic', $level);
                }
                self::assertSame('', ob_get_clean());
                if ($threshold === 0) {
                    self::assertDirectoryDoesNotExist($path);
                    continue;
                }
                $content = file_get_contents(glob($path . '/*.log')[0]);
                self::assertSame($rank * 2, substr_count($content, PHP_EOL));
                foreach (array_values($levels) as $priority => $label) {
                    if ($priority < $rank) {
                        self::assertStringContainsString('[' . $label . '] message', $content);
                        self::assertStringContainsString('[' . $label . '] generic', $content);
                    } else {
                        self::assertStringNotContainsString('[' . $label . ']', $content);
                    }
                }
            }
        } finally {
            foreach (glob($directory . '/*/*.log') as $file) {
                unlink($file);
            }
            foreach (glob($directory . '/*', GLOB_ONLYDIR) as $dir) {
                rmdir($dir);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function testInvalidLevelsAreRejected(): void
    {
        foreach ([-1, 9] as $level) {
            try {
                new Log(__DIR__, $level);
                self::fail('An invalid configured level was accepted.');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('level', $exception->getMessage());
            }
        }
        $logger = new Log(__DIR__);
        foreach ([-1, 0, 9] as $level) {
            try {
                $logger->log('invalid', $level);
                self::fail('An invalid message level was accepted.');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('level', $exception->getMessage());
            }
        }
    }
}
