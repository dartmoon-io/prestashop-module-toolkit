<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Downloaders;

class FTPDownloader extends AbstractDownloader
{
    public function download(string $srcFile, string $dstFile, array $credentials = []): string
    {
        if (!function_exists('ftp_connect')) {
            throw new \LogicException('FTP downloads require ext-ftp.');
        }
        foreach (['host', 'user', 'password'] as $key) {
            if (!isset($credentials[$key]) || !is_string($credentials[$key])) {
                throw new \InvalidArgumentException('Missing FTP credential: ' . $key);
            }
        }
        $port = filter_var($credentials['port'] ?? 21, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        $timeout = filter_var($credentials['timeout'] ?? 30, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($port === false || $timeout === false || $credentials['host'] === '' || $srcFile === '' || $dstFile === '') {
            throw new \InvalidArgumentException('Invalid FTP host, port, timeout or file path.');
        }
        $ssl = $credentials['ssl'] ?? false;
        $passive = $credentials['passive'] ?? true;
        if (!is_bool($ssl) || !is_bool($passive)) {
            throw new \InvalidArgumentException('FTP ssl and passive options must be booleans.');
        }
        if ($ssl && !function_exists('ftp_ssl_connect')) {
            throw new \LogicException('This PHP installation does not support FTPS.');
        }
        $directory = dirname($dstFile);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Cannot create the download directory.');
        }
        $connection = $ssl
            ? @ftp_ssl_connect($credentials['host'], $port, $timeout)
            : @ftp_connect($credentials['host'], $port, $timeout);
        if ($connection === false) {
            throw new \RuntimeException('Cannot connect to the FTP server.');
        }
        $temporary = null;
        try {
            if (!@ftp_login($connection, $credentials['user'], $credentials['password'])) {
                throw new \RuntimeException('FTP authentication failed.');
            }
            if (!@ftp_pasv($connection, $passive)) {
                throw new \RuntimeException('Cannot configure FTP passive mode.');
            }
            $temporary = @tempnam($directory, '.ftp-');
            if ($temporary === false || dirname($temporary) !== realpath($directory)) {
                throw new \RuntimeException('Cannot create a temporary file in the download directory.');
            }
            if (!@ftp_get($connection, $temporary, $srcFile, FTP_BINARY)) {
                throw new \RuntimeException('Cannot download the FTP source file.');
            }
            if (!@rename($temporary, $dstFile)) {
                throw new \RuntimeException('Cannot publish the downloaded file.');
            }

            return $dstFile;
        } finally {
            if (is_string($temporary) && is_file($temporary)) {
                @unlink($temporary);
            }
            ftp_close($connection);
        }
    }
}
