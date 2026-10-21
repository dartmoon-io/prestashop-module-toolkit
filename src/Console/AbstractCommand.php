<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Console;

use Symfony\Component\Console\Command\Command;

/** Register as a console.command service and inject dependencies in the constructor. */
abstract class AbstractCommand extends Command
{
    protected $name = 'command:example';
    protected $description = 'Command description';

    protected function configure(): void
    {
        $this->setName($this->name)->setDescription($this->description);
    }
}
