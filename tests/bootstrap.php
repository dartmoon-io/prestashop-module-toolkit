<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

require dirname(__DIR__) . '/vendor/autoload.php';
define('_DB_PREFIX_', 'test_');

// Small test doubles for the public PrestaShop APIs used by the components.
class Module
{
    public $name = 'testmodule';
    public $context;

    public function __construct()
    {
        $this->context = new Context();
    }
}
class Context
{
    private static ?self $instance = null;
    public $shop;
    public $language;
    public $smarty;

    public static function getContext(): self
    {
        return self::$instance ??= new self();
    }
}
// Unit doubles only. Integration checks load the actual PrestaShop helpers.
class HelperForm
{
}
class HelperList
{
}
class Shop
{
    public const CONTEXT_SHOP = 1;
    public static int $context = self::CONTEXT_SHOP;

    public static function getContext(): int
    {
        return self::$context;
    }
}
class Configuration
{
    public static array $values = [];

    public static function get($key)
    {
        return self::$values[$key] ?? false;
    }
}
class Db
{
    public static array|false $rows = [];
    public static array $queries = [];

    public static function getInstance(): self
    {
        return new self();
    }

    public function executeS($query)
    {
        self::$queries[] = $query;

        return self::$rows;
    }
}
function pSQL($value)
{
    return addslashes($value);
}
class Language
{
    public static function getLanguages($active = true): array
    {
        return [
            ['id_lang' => 1, 'locale' => 'en-US', 'language_code' => 'en-us', 'iso_code' => 'en'],
            ['id_lang' => 2, 'locale' => 'it-IT', 'language_code' => 'it-it', 'iso_code' => 'it'],
        ];
    }
}
class Tab
{
    public static array $rows = [];
    public static array $deleted = [];
    public static ?string $failSave = null;
    public static array $failDelete = [];
    public $id;
    public $class_name;
    public $id_parent;
    public $module;
    public $route_name;
    public $icon;
    public $active;
    public $name;
    public $wording;
    public $wording_domain;

    public function __construct($id = null)
    {
        if ($id !== null && isset(self::$rows[$id])) {
            foreach (get_object_vars(self::$rows[$id]) as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    public static function getIdFromClassName($class): int
    {
        foreach (self::$rows as $row) {
            if ($row->class_name === $class) {
                return $row->id;
            }
        }

        return 0;
    }

    public static function getCollectionFromModule($name): array
    {
        return array_filter(self::$rows, static fn ($row) => $row->module === $name);
    }

    public function save(): bool
    {
        $this->id = count(self::$rows) + 1;
        self::$rows[$this->id] = clone $this;

        return $this->class_name !== self::$failSave;
    }

    public function delete(): bool
    {
        self::$deleted[] = $this->class_name;
        if (in_array($this->class_name, self::$failDelete, true)) {
            return false;
        }
        unset(self::$rows[$this->id]);

        return true;
    }
}
