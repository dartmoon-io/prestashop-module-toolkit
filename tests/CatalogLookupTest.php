<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\Importers\Importer;
use PHPUnit\Framework\TestCase;

final class CatalogLookupTest extends TestCase
{
    protected function setUp(): void
    {
        \Context::getContext()->shop = (object) ['id' => 7, 'id_category' => 42];
        \Db::$rows = [];
        \Db::$queries = [];
        \Configuration::$values = ['PS_LANG_DEFAULT' => 2];
        \Shop::$context = \Shop::CONTEXT_SHOP;
    }

    public function testImportRequiresASingleShopContext(): void
    {
        \Shop::$context = 4;
        try {
            $this->expectException(\LogicException::class);
            (new Importer())->getProductByReference('reference');
        } finally {
            \Shop::$context = \Shop::CONTEXT_SHOP;
        }
    }

    public function testCategoryLookupUsesCurrentShopLanguageAndHome(): void
    {
        self::assertNull((new Importer())->getCategory("men's-shoes"));
        $query = \Db::$queries[0];
        self::assertStringContainsString('s.id_shop = 7', $query);
        self::assertStringContainsString('c.id_parent = 42', $query);
        self::assertStringContainsString('l.id_lang = 2', $query);
        self::assertStringContainsString("men\\'s-shoes", $query);
    }

    public function testDuplicateReferencesAreReportedInsteadOfPickingAnArbitraryProduct(): void
    {
        \Db::$rows = [['id_product' => 1], ['id_product' => 2]];
        $this->expectException(\UnexpectedValueException::class);
        (new Importer())->getProductByReference('duplicate');
    }

    public function testDatabaseFailuresAreNotTreatedAsMissingProducts(): void
    {
        \Db::$rows = false;
        $this->expectException(\RuntimeException::class);
        (new Importer())->getProductByReference('reference');
    }

    public function testDescriptionsTruncateUnicodeWithoutBreakingMarkup(): void
    {
        \Configuration::$values['PS_PRODUCT_SHORT_DESC_LIMIT'] = 6;
        self::assertSame('èàù...', (new Importer())->generateDescriptionShort('<p>èàùòìabcdef</p>'));
        self::assertSame([1 => 'Text', 2 => 'Text'], (new Importer())->getMultiLangText('Text'));
    }
}
