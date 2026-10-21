<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\Console\AbstractCommand;
use Dartmoon\Utils\MultiLangText;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class ComponentsTest extends TestCase
{
    public function testTranslationsAndFallbacks(): void
    {
        $text = new MultiLangText();
        self::assertSame([1 => 'Hello', 2 => 'Hello'], $text->generate('Hello'));
        self::assertSame([1 => 'Hello', 2 => 'Ciao'], $text->generate(['en-US' => 'Hello', 'it' => 'Ciao']));
        self::assertSame([1 => '', 2 => 'Ciao'], $text->generate([1 => '', 2 => 'Ciao']));
        self::assertSame([1 => '', 2 => ''], $text->generate([]));
        self::assertSame([1 => 'Default', 2 => 'Default'], $text->generate(['fr' => 'Default']));
    }

    public function testModernConsoleCommandRuns(): void
    {
        $command = new class extends AbstractCommand {
            protected $name = 'test:hello';

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $output->writeln('Hello');

                return self::SUCCESS;
            }
        };
        $tester = new CommandTester($command);
        self::assertSame('test:hello', $command->getName());
        self::assertSame(0, $tester->execute([]));
        self::assertSame("Hello\n", $tester->getDisplay());
    }
}
