<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Helpers;

class ProductSearch
{
    /** Bounded results restricted to the current shop and language. */
    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        if ($limit < 1 || $limit > 100 || $offset < 0) {
            throw new \InvalidArgumentException('Use a limit from 1 to 100 and a nonnegative offset.');
        }
        if (trim($query) === '') {
            return [];
        }
        $context = \Context::getContext();
        $query = strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']);
        $sql = 'SELECT DISTINCT p.id_product AS id, p.name AS text FROM `' . _DB_PREFIX_ . 'product_lang` p'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'product_shop` s ON s.id_product = p.id_product AND s.id_shop = p.id_shop'
            . ' WHERE p.id_shop = ' . (int) $context->shop->id . ' AND p.id_lang = ' . (int) $context->language->id
            . " AND p.name LIKE '%" . pSQL($query) . "%' ESCAPE '!' ORDER BY p.name, p.id_product LIMIT " . $offset . ', ' . $limit;
        $rows = \Db::getInstance()->executeS($sql);
        if ($rows === false) {
            throw new \RuntimeException('Unable to search the product catalog.');
        }

        return array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'text' => $row['text']], $rows);
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $context = \Context::getContext();
        $product = new \Product($id, false, (int) $context->language->id, (int) $context->shop->id);
        if (!\Validate::isLoadedObject($product) || !$product->isAssociatedToShop((int) $context->shop->id)) {
            return null;
        }

        return ['id' => (int) $product->id, 'text' => (string) $product->name];
    }
}
