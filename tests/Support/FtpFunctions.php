<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Downloaders;

// Namespaced test functions never contact a server and are not shipped in releases.
class FtpTestState
{
    public static ?string $failure = null;
    public static array $events = [];
}

function ftp_connect($host, $port, $timeout)
{
    FtpTestState::$events[] = ['connect', $host, $port, $timeout];

    return FtpTestState::$failure === 'connect' ? false : new \stdClass();
}
function ftp_ssl_connect($host, $port, $timeout)
{
    FtpTestState::$events[] = ['ssl'];

    return ftp_connect($host, $port, $timeout);
}
function ftp_login($connection, $user, $password)
{
    FtpTestState::$events[] = ['login'];

    return FtpTestState::$failure !== 'login';
}
function ftp_pasv($connection, $passive)
{
    FtpTestState::$events[] = ['passive', $passive];

    return FtpTestState::$failure !== 'passive';
}
function ftp_get($connection, $destination, $source, $mode)
{
    FtpTestState::$events[] = ['get', $source, $mode];
    file_put_contents($destination, 'downloaded bytes');
    if (FtpTestState::$failure === 'throw') {
        throw new \RuntimeException('Transfer interrupted.');
    }

    return FtpTestState::$failure !== 'get';
}
function ftp_close($connection)
{
    FtpTestState::$events[] = ['close'];

    return true;
}
