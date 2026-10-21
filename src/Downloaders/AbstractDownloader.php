<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Downloaders;

abstract class AbstractDownloader
{
    abstract public function download(string $srcFile, string $dstFile, array $credentials = []): string;
}
