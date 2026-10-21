<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Importers;

use Dartmoon\Filters\FilterService;
use Dartmoon\Utils\MultiLangText;

/** Catalog import operations use the current PrestaShop shop context. */
class Importer
{
    public function __construct(
        protected FilterService $filters = new FilterService(),
        protected MultiLangText $translations = new MultiLangText(),
    ) {
    }

    public function getOrAddCategory(string|array $name, string|array $link_rewrite, bool $active = false, ?\Category $parent = null): \Category
    {
        return $this->getCategory($link_rewrite, $parent) ?? $this->addCategory($name, $link_rewrite, $active, $parent);
    }

    public function getCategory(string|array $link_rewrite, ?\Category $parent = null): ?\Category
    {
        $parentId = $parent === null ? $this->homeId() : $this->id($parent);
        $slug = $this->translated($link_rewrite);
        $id = $this->singleId('SELECT DISTINCT c.id_category FROM `' . _DB_PREFIX_ . 'category` c'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'category_shop` s ON s.id_category = c.id_category AND s.id_shop = ' . $this->shopId()
            . ' INNER JOIN `' . _DB_PREFIX_ . 'category_lang` l ON l.id_category = c.id_category AND l.id_shop = s.id_shop'
            . ' WHERE c.id_parent = ' . $parentId . ' AND l.id_lang = ' . $this->languageId()
            . " AND l.link_rewrite = '" . pSQL($slug) . "'", 'id_category');

        return $id === null ? null : new \Category($id, null, $this->shopId());
    }

    public function addCategory(string|array $name, string|array $link_rewrite, bool $active = false, ?\Category $parent = null): \Category
    {
        $category = new \Category();
        $category->name = $this->getMultiLangText($name);
        $category->link_rewrite = $this->getMultiLangText($link_rewrite);
        $category->active = $active;
        $category->id_parent = $parent === null ? $this->homeId() : $this->id($parent);
        $category->id_shop_default = $this->shopId();
        $this->save($category);

        return $category;
    }

    public function addOrUpdateProduct(
        string|array $name,
        string|array|null $description = null,
        string|array|null $description_short = null,
        string $reference = '',
        string|array|null $link_rewrite = null,
        array $categories = [],
        array $features = [],
        bool $active = false,
        array $extras = [],
        ?\Product $product = null,
    ): \Product {
        $product ??= new \Product();
        $new = !(bool) $product->id;
        $categoryIds = $this->ids($categories, \Category::class);
        foreach ($categoryIds as $id) {
            $category = new \Category($id, null, $this->shopId());
            $this->id($category);
            if (!$category->isAssociatedToShop($this->shopId())) {
                throw new \InvalidArgumentException('A product category is not associated with the current shop.');
            }
        }
        foreach ($features as $pair) {
            if (!is_array($pair) || !($pair['feature'] ?? null) instanceof \Feature || !($pair['value'] ?? null) instanceof \FeatureValue) {
                throw new \InvalidArgumentException('Features require feature/value ObjectModel pairs.');
            }
            $featureId = $this->id($pair['feature']);
            $this->id($pair['value']);
            if ((int) $pair['value']->id_feature !== $featureId) {
                throw new \InvalidArgumentException('The feature value belongs to another feature.');
            }
        }
        $this->extras($product, $extras);
        $product->name = $this->getMultiLangText($name);
        $product->reference = $reference;
        if ($link_rewrite === null) {
            $link_rewrite = array_map(fn (string $text): string => $this->filters->filter($text, 'slugify'), $product->name);
        }
        $product->link_rewrite = $this->getMultiLangText($link_rewrite);
        if ($description !== null) {
            $product->description = $this->getMultiLangText($description);
        }
        if ($description_short !== null) {
            $product->description_short = $this->getMultiLangText($description_short);
        }
        $product->active = $active;
        if ($new) {
            $product->id_shop_default = $this->shopId();
            $categoryIds = $categoryIds === [] ? [$this->homeId()] : $categoryIds;
        } elseif (!$product->isAssociatedToShop($this->shopId())) {
            throw new \InvalidArgumentException('The product is not associated with the current shop.');
        }
        if ($categoryIds !== []) {
            $product->id_category_default = $categoryIds[0];
        }
        $this->save($product);
        if ($categoryIds !== [] && !$product->updateCategories($categoryIds)) {
            throw new \RuntimeException('Unable to update product categories.');
        }
        foreach ($features as $pair) {
            if (!\Product::addFeatureProductImport((int) $product->id, (int) $pair['feature']->id, (int) $pair['value']->id)) {
                throw new \RuntimeException('Unable to associate a product feature.');
            }
        }
        \Product::resetStaticCache();

        return $product;
    }

    public function productExists(string $reference): bool
    {
        return $this->getProductByReference($reference) !== null;
    }

    public function getProductByReference(string $reference): ?\Product
    {
        if ($reference === '') {
            throw new \InvalidArgumentException('A product reference must not be empty for lookups.');
        }
        $id = $this->singleId('SELECT DISTINCT p.id_product FROM `' . _DB_PREFIX_ . 'product` p'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'product_shop` s ON s.id_product = p.id_product AND s.id_shop = ' . $this->shopId()
            . " WHERE p.reference = '" . pSQL($reference) . "'", 'id_product');

        return $id === null ? null : new \Product($id, false, null, $this->shopId());
    }

    public function getProductByName(string $name, ?int $id_lang = null): ?\Product
    {
        $id = $this->singleId('SELECT DISTINCT p.id_product FROM `' . _DB_PREFIX_ . 'product_lang` p'
            . ' WHERE p.id_shop = ' . $this->shopId() . ' AND p.id_lang = ' . $this->languageId($id_lang)
            . " AND p.name = '" . pSQL($name) . "'", 'id_product');

        return $id === null ? null : new \Product($id, false, null, $this->shopId());
    }

    public function getOrAddProductCombination(\Product $product, array $attributes, string $reference): \Combination
    {
        return $this->getProductCombination($product, $attributes)
            ?? $this->addOrUpdateProductCombination($product, $reference, $attributes);
    }

    public function getProductCombinationByReference(\Product $product, string $reference): ?\Combination
    {
        $id = $this->singleId('SELECT DISTINCT c.id_product_attribute FROM `' . _DB_PREFIX_ . 'product_attribute` c'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` s ON s.id_product_attribute = c.id_product_attribute'
            . ' AND s.id_shop = ' . $this->shopId() . ' WHERE c.id_product = ' . $this->id($product)
            . " AND c.reference = '" . pSQL($reference) . "'", 'id_product_attribute');

        return $id === null ? null : new \Combination($id, null, $this->shopId());
    }

    public function getProductCombination(\Product $product, array $attributes): ?\Combination
    {
        $wanted = $this->ids($attributes, \ProductAttribute::class);
        sort($wanted);
        if ($wanted === []) {
            throw new \InvalidArgumentException('A combination must have attributes.');
        }
        $rows = \Db::getInstance()->executeS('SELECT c.id_product_attribute, pac.id_attribute'
            . ' FROM `' . _DB_PREFIX_ . 'product_attribute` c'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` s ON s.id_product_attribute = c.id_product_attribute AND s.id_shop = ' . $this->shopId()
            . ' INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pac.id_product_attribute = c.id_product_attribute'
            . ' WHERE c.id_product = ' . $this->id($product));
        if ($rows === false) {
            throw new \RuntimeException('Unable to load product combinations.');
        }
        $sets = [];
        foreach ($rows as $row) {
            $sets[(int) $row['id_product_attribute']][] = (int) $row['id_attribute'];
        }
        $matches = [];
        foreach ($sets as $id => $ids) {
            sort($ids);
            if ($wanted === $ids) {
                $matches[] = $id;
            }
        }
        if (count($matches) > 1) {
            throw new \UnexpectedValueException('Several combinations have the same attributes; select one explicitly.');
        }

        return $matches === [] ? null : new \Combination($matches[0], null, $this->shopId());
    }

    public function addOrUpdateProductCombination(
        \Product $product,
        string $reference,
        array $attributes,
        ?\Combination $combination = null,
        array $extras = [],
        bool $force_attribute_association = false,
    ): \Combination {
        $productId = $this->id($product);
        if (!$product->isAssociatedToShop($this->shopId())) {
            throw new \InvalidArgumentException('The product is not associated with the current shop.');
        }
        $attributeIds = $this->attributeIds($attributes);
        $combination ??= new \Combination();
        $new = !(bool) $combination->id;
        if (!$new && (int) $combination->id_product !== $productId) {
            throw new \InvalidArgumentException('The combination belongs to another product.');
        }
        if (!$new && !$combination->isAssociatedToShop($this->shopId())) {
            throw new \InvalidArgumentException('The combination is not associated with the current shop.');
        }
        $this->extras($combination, $extras);
        $combination->id_product = $productId;
        $combination->reference = $reference;
        $this->save($combination);
        if ($new || $force_attribute_association) {
            $this->setProductCombinationAttributes($combination, $attributeIds);
        }
        if ($product->product_type !== 'combinations') {
            $product->product_type = 'combinations';
            $this->save($product);
        }
        \Product::updateDefaultAttribute($productId);
        \Product::resetStaticCache();

        return $combination;
    }

    /** Preserve image/cart associations; native Combination::setAttributes deletes image links. */
    public function setProductCombinationAttributes(\Combination $combination, array $attributes): bool
    {
        $id = $this->id($combination);
        if (!$combination->isAssociatedToShop($this->shopId())) {
            throw new \InvalidArgumentException('The combination is not associated with the current shop.');
        }
        $ids = $this->attributeIds($attributes);
        $db = \Db::getInstance();
        $previous = $db->executeS('SELECT id_attribute FROM `' . _DB_PREFIX_ . 'product_attribute_combination` WHERE id_product_attribute = ' . $id);
        if ($previous === false || !$db->delete('product_attribute_combination', 'id_product_attribute = ' . $id)) {
            throw new \RuntimeException('Unable to replace combination attributes.');
        }
        $rows = array_map(static fn (int $attribute): array => ['id_product_attribute' => $id, 'id_attribute' => $attribute], $ids);
        try {
            if (!$db->insert('product_attribute_combination', $rows)) {
                throw new \RuntimeException('Unable to associate combination attributes.');
            }
        } catch (\Throwable $exception) {
            $db->delete('product_attribute_combination', 'id_product_attribute = ' . $id);
            $oldRows = array_map(static fn (array $row): array => ['id_product_attribute' => $id, 'id_attribute' => (int) $row['id_attribute']], $previous);
            if ($oldRows !== [] && !$db->insert('product_attribute_combination', $oldRows)) {
                throw new \RuntimeException('Unable to restore combination attributes for ID ' . $id, 0, $exception);
            }
            throw $exception;
        }
        \Hook::exec('actionAttributeCombinationSave', ['id_product_attribute' => $id, 'id_attributes' => $ids]);

        return true;
    }

    public function getOrAddValueOfFeature(\Feature $feature, string|array $feature_value): \FeatureValue
    {
        return $this->getValueOfFeature($feature, $feature_value) ?? $this->addValueToFeature($feature, $feature_value);
    }

    public function getValueOfFeature(\Feature $feature, string|array $feature_value): ?\FeatureValue
    {
        foreach (\FeatureValue::getFeatureValuesWithLang($this->languageId(), $this->id($feature)) as $row) {
            if ($row['value'] === $this->translated($feature_value)) {
                return new \FeatureValue((int) $row['id_feature_value']);
            }
        }

        return null;
    }

    public function addValueToFeature(\Feature $feature, string|array $feature_value): \FeatureValue
    {
        $value = new \FeatureValue();
        $value->id_feature = $this->id($feature);
        $value->value = $this->getMultiLangText($feature_value);
        $this->save($value);

        return $value;
    }

    public function getOrAddAttributeGroup(string|array $name, string $type, ?int $id_lang = null): \AttributeGroup
    {
        return $this->getAttributeGroup($name, $type, $id_lang) ?? $this->addAttributeGroup($name, $type, $id_lang);
    }

    public function getAttributeGroup(string|array $name, string $type, ?int $id_lang = null): ?\AttributeGroup
    {
        $id = $this->singleId('SELECT DISTINCT g.id_attribute_group FROM `' . _DB_PREFIX_ . 'attribute_group` g'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'attribute_group_shop` s ON s.id_attribute_group = g.id_attribute_group AND s.id_shop = ' . $this->shopId()
            . ' INNER JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` l ON l.id_attribute_group = g.id_attribute_group'
            . ' WHERE l.id_lang = ' . $this->languageId($id_lang) . " AND g.group_type = '" . pSQL($type)
            . "' AND l.name = '" . pSQL($this->translated($name, $id_lang)) . "'", 'id_attribute_group');

        return $id === null ? null : new \AttributeGroup($id);
    }

    public function addAttributeGroup(string|array $name, string $type, ?int $id_lang = null): \AttributeGroup
    {
        if (!in_array($type, ['select', 'radio', 'color'], true)) {
            throw new \InvalidArgumentException('Unknown attribute group type.');
        }
        $group = new \AttributeGroup();
        $group->name = $group->public_name = $this->getMultiLangText($name);
        $group->group_type = $type;
        $group->is_color_group = $type === 'color';
        $this->save($group);

        return $group;
    }

    public function getOrAddAttribute(\AttributeGroup $attribute_group, string|array $value, ?int $id_lang = null): \ProductAttribute
    {
        return $this->getAttributeFromAttributeGroup($attribute_group, $value, $id_lang) ?? $this->addAttributeToAttributeGroup($attribute_group, $value);
    }

    public function getAttributeFromAttributeGroup(\AttributeGroup $attribute_group, string|array $value, ?int $id_lang = null): ?\ProductAttribute
    {
        $name = $this->translated($value, $id_lang);
        foreach (\AttributeGroup::getAttributes($this->languageId($id_lang), $this->id($attribute_group)) as $row) {
            if (mb_strtolower(trim($row['name']), 'UTF-8') === mb_strtolower(trim($name), 'UTF-8')) {
                return new \ProductAttribute((int) $row['id_attribute']);
            }
        }

        return null;
    }

    public function addAttributeToAttributeGroup(\AttributeGroup $attribute_group, string|array $value): \ProductAttribute
    {
        $attribute = new \ProductAttribute();
        $attribute->id_attribute_group = $this->id($attribute_group);
        $attribute->name = $this->getMultiLangText($value);
        $this->save($attribute);

        return $attribute;
    }

    public function getManufacturerByName(string $name): ?\Manufacturer
    {
        $id = $this->singleId('SELECT DISTINCT m.id_manufacturer FROM `' . _DB_PREFIX_ . 'manufacturer` m'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'manufacturer_shop` s ON s.id_manufacturer = m.id_manufacturer AND s.id_shop = ' . $this->shopId()
            . " WHERE m.name = '" . pSQL($name) . "'", 'id_manufacturer');

        return $id === null ? null : new \Manufacturer($id);
    }

    public function generateDescriptionShort(string $description): string
    {
        $text = trim(strip_tags($description));
        $limit = (int) \Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT');
        $limit = $limit > 0 ? $limit : 800;
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        return $limit <= 3 ? mb_substr($text, 0, $limit, 'UTF-8') : mb_substr($text, 0, $limit - 3, 'UTF-8') . '...';
    }

    public function getMultiLangText(string|array $text): array
    {
        return $this->translations->generate($text);
    }

    private function translated(string|array $text, ?int $languageId = null): string
    {
        $translations = $this->getMultiLangText($text);

        return $translations[$this->languageId($languageId)] ?? (string) reset($translations);
    }

    private function languageId(?int $id = null): int
    {
        $id ??= (int) \Configuration::get('PS_LANG_DEFAULT');
        if ($id <= 0) {
            throw new \LogicException('Select a valid PrestaShop language.');
        }

        return $id;
    }

    private function shopId(): int
    {
        $id = (int) (\Context::getContext()->shop->id ?? 0);
        if ($id <= 0 || \Shop::getContext() !== \Shop::CONTEXT_SHOP) {
            throw new \LogicException('Select a single PrestaShop shop context before importing.');
        }

        return $id;
    }

    private function homeId(): int
    {
        $id = (int) (\Context::getContext()->shop->id_category ?? \Configuration::get('PS_HOME_CATEGORY'));
        if ($id <= 0) {
            throw new \LogicException('The current shop has no home category.');
        }

        return $id;
    }

    private function id(\ObjectModel $model): int
    {
        if (!\Validate::isLoadedObject($model)) {
            throw new \InvalidArgumentException('Expected a saved PrestaShop ObjectModel.');
        }

        return (int) $model->id;
    }

    private function ids(array $values, string $expected): array
    {
        $ids = [];
        foreach ($values as $value) {
            if ($value instanceof \ObjectModel && !$value instanceof $expected) {
                throw new \InvalidArgumentException('Unexpected ObjectModel type; expected ' . $expected);
            }
            $id = $value instanceof \ObjectModel ? $this->id($value) : filter_var($value, FILTER_VALIDATE_INT);
            if ($id === false || $id <= 0) {
                throw new \InvalidArgumentException('Object identifiers must be positive integers.');
            }
            $ids[] = (int) $id;
        }

        return array_values(array_unique($ids));
    }

    private function attributeIds(array $values): array
    {
        $ids = $this->ids($values, \ProductAttribute::class);
        if ($ids === []) {
            throw new \InvalidArgumentException('A combination must have attributes.');
        }
        $groups = [];
        foreach ($ids as $id) {
            $attribute = new \ProductAttribute($id);
            $this->id($attribute);
            if (!$attribute->isAssociatedToShop($this->shopId())) {
                throw new \InvalidArgumentException('The attribute is not associated with the current shop.');
            }
            if (isset($groups[$attribute->id_attribute_group])) {
                throw new \InvalidArgumentException('Choose only one attribute per group.');
            }
            $groups[$attribute->id_attribute_group] = true;
        }

        return $ids;
    }

    private function extras(\ObjectModel $model, array $values): void
    {
        foreach ($values as $name => $value) {
            if (!isset($model::$definition['fields'][$name]) || in_array($name, ['id_product', 'id_shop_default', 'id_category_default'], true)) {
                throw new \InvalidArgumentException('Unsupported extra field: ' . $name);
            }
        }
        foreach ($values as $name => $value) {
            $model->{$name} = $value;
        }
    }

    private function save(\ObjectModel $model): void
    {
        $this->shopId();
        if (!$model->save()) {
            throw new \RuntimeException('Unable to save ' . get_class($model) . '.');
        }
    }

    private function singleId(string $query, string $field): ?int
    {
        $rows = \Db::getInstance()->executeS($query . ' LIMIT 2');
        if ($rows === false) {
            throw new \RuntimeException('Catalog lookup failed.');
        }
        if (count($rows) > 1) {
            throw new \UnexpectedValueException('Ambiguous catalog lookup; select an object explicitly.');
        }

        return $rows === [] ? null : (int) $rows[0][$field];
    }
}
