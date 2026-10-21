<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Controllers\Admin;

use Dartmoon\Helpers\HelperForm;
use Dartmoon\Helpers\HelperList;
use Dartmoon\Helpers\ProductSearch;
use Dartmoon\Renderers\HtmlAttributes;

/** Optional base for native ObjectModel controllers; modern pages can use Symfony. */
abstract class AbstractAdminController extends \ModuleAdminController
{
    protected $ajaxUrl;
    protected $self;

    abstract protected function getConfigFormDefinition();

    protected function initFormToolbar()
    {
    }

    public function __construct()
    {
        parent::__construct();
        $this->self = preg_replace('/Controller(?:Override)?$/', '', (new \ReflectionClass($this))->getShortName());
        $this->ajaxUrl = $this->context->link->getAdminLink($this->self);
    }

    public function renderForm()
    {
        $this->initFormToolbar();
        $this->object = $this->loadObject(true);
        if (!$this->object) {
            return '';
        }
        $this->fields_form = $this->getConfigFormDefinition();
        $this->multiple_fieldsets = true;
        $this->fields_form = HelperForm::prepareFields($this->fields_form, $this->getFieldsValue($this->object));

        return parent::renderForm();
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        $result = parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
        if (is_array($this->_list)) {
            $this->_list = HelperList::prepareRows($this->_list, $this->fields_list);
        }

        return $result;
    }

    /** Copy only declared form fields; preserve native password hashing. */
    protected function copyFromPost(&$object, $table)
    {
        foreach ($this->getConfigFormDefinition() as $fieldset) {
            foreach ($fieldset['form']['input'] ?? [] as $input) {
                $name = preg_replace('/\[\]$/', '', $input['name'] ?? '');
                if (!isset($object::$definition['fields'][$name]) || $name === 'id_' . $table
                    || ($input['disabled'] ?? false) || (isset($input['condition']) && !$input['condition'])) {
                    continue;
                }
                if ($input['lang'] ?? false) {
                    $values = is_array($object->{$name}) ? $object->{$name} : [];
                    foreach (\Language::getIDs(false) as $id) {
                        if (\Tools::getIsset($name . '_' . $id)) {
                            $values[$id] = \Tools::getValue($name . '_' . $id);
                        }
                    }
                    $object->{$name} = $values;
                } elseif (\Tools::getIsset($name)) {
                    $value = \Tools::getValue($name);
                    if ($name === 'passwd') {
                        if ($object->id && $value === '') {
                            continue;
                        }
                        if ($value !== '') {
                            $value = $this->get('hashing')->hash($value, _COOKIE_KEY_);
                        }
                    }
                    $object->{$name} = $value;
                }
            }
        }
    }

    public function ajaxProcessSearchProductByName()
    {
        $this->checkProductSearchAccess();
        $this->ajaxRender(json_encode(['items' => (new ProductSearch())->search((string) \Tools::getValue('q', ''))], JSON_THROW_ON_ERROR));
    }

    public function ajaxProcessGetProductById()
    {
        $this->checkProductSearchAccess();
        $this->ajaxRender(json_encode(['item' => (new ProductSearch())->find((int) \Tools::getValue('id'))], JSON_THROW_ON_ERROR));
    }

    protected function createListLink(array $options): string
    {
        $attributes = ['href' => $options['href'] ?? '#', 'title' => $options['action'] ?? ''];
        foreach (['target', 'class'] as $key) {
            if (isset($options[$key])) {
                $attributes[$key] = $options[$key];
            }
        }
        if (($attributes['target'] ?? null) === '_blank') {
            $attributes['rel'] = 'noopener noreferrer';
        }
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<a ' . HtmlAttributes::render($attributes) . '><i class="' . $escape($options['icon'] ?? '') . '"></i> '
            . $escape($options['action'] ?? '') . '</a>';
    }

    private function checkProductSearchAccess(): void
    {
        if (!$this->access('view')) {
            throw new \PrestaShopException('Access denied.');
        }
        header('Content-Type: application/json; charset=utf-8');
    }
}
