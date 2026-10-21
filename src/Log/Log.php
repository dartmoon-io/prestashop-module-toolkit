<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Log;

/** A module-local daily log, with no module constants or implicit stdout output. */
class Log
{
    public const ERROR = 1;
    public const DEBUG = 2;
    public const EMERGENCY = 3;
    public const ALERT = 4;
    public const CRITICAL = 5;
    public const WARNING = 6;
    public const NOTICE = 7;
    public const INFO = 8;

    private const LEVELS = [
        self::EMERGENCY => 'EMERGENCY',
        self::ALERT => 'ALERT',
        self::CRITICAL => 'CRITICAL',
        self::ERROR => 'ERROR',
        self::WARNING => 'WARNING',
        self::NOTICE => 'NOTICE',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG',
    ];

    public function __construct(private string $directory, private int $level = self::DEBUG)
    {
        if ($directory === '' || ($level !== 0 && !isset(self::LEVELS[$level]))) {
            throw new \InvalidArgumentException('Supply a log directory and a level from 0 to 8.');
        }
    }

    public function log(string $message, int $level = self::DEBUG, bool $echo = false): void
    {
        if (!isset(self::LEVELS[$level])) {
            throw new \InvalidArgumentException('Use a Log level constant.');
        }
        $levels = array_keys(self::LEVELS);
        if ($this->level === 0 || array_search($level, $levels, true) > array_search($this->level, $levels, true)) {
            return;
        }
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Cannot create the log directory.');
        }
        $path = rtrim($this->directory, '/\\') . '/log-' . date('Y-m-d') . '.log';
        $line = date('Y-m-d H:i:s') . ' [' . self::LEVELS[$level] . '] '
            . str_replace(["\r", "\n"], ['\\r', '\\n'], $message) . PHP_EOL;
        if (@file_put_contents($path, $line, FILE_APPEND | LOCK_EX) !== strlen($line)) {
            throw new \RuntimeException('Cannot write the module log file.');
        }
        if ($echo) {
            echo $message . PHP_EOL;
        }
    }

    public function emergency(string $message): void
    {
        $this->log($message, self::EMERGENCY);
    }

    public function alert(string $message): void
    {
        $this->log($message, self::ALERT);
    }

    public function critical(string $message): void
    {
        $this->log($message, self::CRITICAL);
    }

    public function error(string $message): void
    {
        $this->log($message, self::ERROR);
    }

    public function debug(string $message): void
    {
        $this->log($message, self::DEBUG);
    }

    public function warning(string $message): void
    {
        $this->log($message, self::WARNING);
    }

    public function notice(string $message): void
    {
        $this->log($message, self::NOTICE);
    }

    public function info(string $message): void
    {
        $this->log($message, self::INFO);
    }
}
