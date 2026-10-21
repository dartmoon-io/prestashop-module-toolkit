<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\Helpers\HelperForm;
use Dartmoon\Helpers\HelperList;
use Dartmoon\Renderers\HtmlAttributes;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testCustomFieldsUseNativeHtmlFieldsWithEscapedValues(): void
    {
        $fields = HelperForm::prepareFields([['form' => ['input' => [
            ['type' => 'number', 'name' => 'amount', 'attributes' => ['min' => 0, 'data-note' => 'a"<b', 'name' => 'forged']],
            ['type' => 'vector3', 'name' => 'position', 'label' => 'Position'],
            ['type' => 'separator', 'name' => 'heading', 'content' => '<script>'],
            ['type' => 'text', 'name' => 'hidden', 'condition' => false],
        ]]]], ['amount' => 0, 'position' => '1 2 3']);
        $inputs = $fields[0]['form']['input'];
        self::assertCount(3, $inputs);
        self::assertSame('html', $inputs[0]['type']);
        self::assertStringContainsString('name="amount"', $inputs[0]['html_content']);
        self::assertStringContainsString('value="0"', $inputs[0]['html_content']);
        self::assertStringContainsString('a&quot;&lt;b', $inputs[0]['html_content']);
        self::assertStringNotContainsString('forged', $inputs[0]['html_content']);
        self::assertStringContainsString('data-dartmoon-vector', $inputs[1]['html_content']);
        self::assertStringContainsString('value="1 2 3"', $inputs[1]['html_content']);
        self::assertStringContainsString('&lt;script&gt;', $inputs[2]['html_content']);
    }

    public function testComputedColumnsHaveKeysWithoutReplacingRealValues(): void
    {
        $rows = HelperList::prepareRows([['id' => 1, 'zero' => 0]], [
            'a!computed' => ['callback' => 'render'], 'zero' => ['callback' => 'render'], 'unrelated' => [],
        ]);
        self::assertArrayHasKey('computed', $rows[0]);
        self::assertNull($rows[0]['computed']);
        self::assertSame(0, $rows[0]['zero']);
        self::assertArrayNotHasKey('unrelated', $rows[0]);
    }

    public function testAttributeEscapingAndBooleanFields(): void
    {
        self::assertSame('disabled value="&quot;&lt;&amp;"', HtmlAttributes::render(['disabled' => true, 'hidden' => false, 'value' => '"<&']));
        $this->expectException(\InvalidArgumentException::class);
        HtmlAttributes::render(['onclick' => 'evil()']);
    }

    public function testMalformedVectorsFailInsteadOfSilentlyChangingValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        HelperForm::prepareFields([['form' => ['input' => [['type' => 'vector3', 'name' => 'position']]]]], ['position' => '1 2']);
    }
}
