<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

ini_set('zend.exception_ignore_args', '1');

// Run against a disposable installed shop; only randomly named test entities are removed.
use Dartmoon\Helpers\HelperForm;
use Dartmoon\Helpers\HelperList;
use Dartmoon\Helpers\ProductSearch;
use Dartmoon\Importers\ImageImporter;
use Dartmoon\Importers\Importer;
use Dartmoon\Renderers\TemplateRenderer;

$shop = realpath($argv[1] ?? '');
if (!$shop || !is_file($shop . '/config/config.inc.php')) {
    throw new InvalidArgumentException('Pass the path to a disposable PrestaShop 9.2 shop.');
}
$adminDirectories = glob($shop . '/admin*/themes/default/template', GLOB_ONLYDIR);
if (count($adminDirectories) !== 1) {
    throw new RuntimeException('Cannot identify the native admin templates.');
}
define('_PS_ADMIN_DIR_', dirname($adminDirectories[0], 3));
require $shop . '/config/config.inc.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';
// Check native inheritance signatures before creating any fixtures.
foreach ([HelperForm::class, HelperList::class, Dartmoon\Controllers\Admin\AbstractAdminController::class, Dartmoon\Importers\ImportedImage::class] as $class) {
    if (!class_exists($class)) {
        throw new RuntimeException('Cannot load ' . $class);
    }
}

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$token = 'dmtoolkit' . bin2hex(random_bytes(5));
$models = [];
$temporaryFiles = [];
$moduleDirectory = $shop . '/modules/' . $token;
$moduleCreated = false;
$remember = static function (ObjectModel $model) use (&$models): ObjectModel {
    $models[] = $model;

    return $model;
};
$context = Context::getContext();
$templateDirectories = $context->smarty->getTemplateDir();
$originalEmployee = $context->employee;
try {
    $importer = new Importer();
    $category = $remember($importer->getOrAddCategory($token, $token));
    $check((int) $category->id_parent === (int) $context->shop->id_category, 'The importer ignored the shop home category.');
    $check((int) $importer->getOrAddCategory($token, $token)->id === (int) $category->id, 'Category lookup was not idempotent.');
    $product = $remember(new Product());
    $importer->addOrUpdateProduct(
        name: $token, description: 'Full description', description_short: '',
        reference: $token, categories: [$category], extras: ['price' => 12.5], product: $product
    );
    $loaded = $importer->getProductByReference($token);
    $check($loaded !== null && (int) $loaded->id === (int) $product->id, 'Product reference lookup failed.');
    $check((int) $loaded->id_category_default === (int) $category->id, 'Default category was not persisted.');
    $check((int) $importer->getProductByName($token)->id === (int) $product->id, 'Product name lookup failed.');
    $feature = $remember(new Feature());
    $feature->name = $importer->getMultiLangText($token);
    $check($feature->save(), 'Cannot create feature fixture.');
    $value = $remember($importer->getOrAddValueOfFeature($feature, 'Cotton'));
    $importer->addOrUpdateProduct(
        name: $token, reference: $token, description_short: 'Short only',
        features: [['feature' => $feature, 'value' => $value]], product: $product
    );
    $loaded = $importer->getProductByReference($token);
    $languageId = (int) Configuration::get('PS_LANG_DEFAULT');
    $check($loaded->description[$languageId] === 'Full description', 'Null descriptions overwrote existing text.');
    $check($loaded->description_short[$languageId] === 'Short only', 'Short-only description update failed.');
    $check((int) $loaded->id_category_default === (int) $category->id, 'An update without categories changed the default.');
    $check(count($loaded->getFeatures()) === 1, 'Product feature was not associated.');
    $size = $remember($importer->getOrAddAttributeGroup($token . 'size', 'select'));
    $material = $remember($importer->getOrAddAttributeGroup($token . 'material', 'select'));
    $small = $remember($importer->getOrAddAttribute($size, 'Small'));
    $medium = $remember($importer->getOrAddAttribute($size, 'Medium'));
    $cotton = $remember($importer->getOrAddAttribute($material, 'Cotton'));
    $check($small instanceof ProductAttribute, 'PHP Attribute was used instead of ProductAttribute.');
    $check((int) $importer->getOrAddAttribute($size, 'small')->id === (int) $small->id, 'Attribute lookup failed.');
    $combination = $remember(new Combination());
    $importer->addOrUpdateProductCombination($product, $token . '-s', [$small, $cotton], $combination);
    $check($importer->getProductCombination($product, [$small]) === null, 'A subset matched a larger combination.');
    $check((int) $importer->getProductCombination($product, [$cotton, $small])->id === (int) $combination->id, 'Exact combination lookup failed.');
    $source = tempnam(sys_get_temp_dir(), 'dartmoon-image-');
    $temporaryFiles[] = $source;
    $raster = imagecreatetruecolor(4, 4);
    imagepng($raster, $source);
    $images = new ImageImporter();
    $image = $remember($images->importProductImageFromFile($product, $source, true));
    $base = _PS_PRODUCT_IMG_DIR_ . $image->getExistingImgPath();
    $check(is_file($base . '.jpg'), 'Native product image was not written.');
    $types = ImageType::getImagesTypes('products');
    foreach ($types as $type) {
        $check(is_file($base . '-' . $type['name'] . '.jpg'), 'Missing native thumbnail.');
    }
    $check(Db::getInstance()->insert('product_attribute_image', ['id_product_attribute' => (int) $combination->id, 'id_image' => (int) $image->id]), 'Cannot create image association fixture.');
    $importer->addOrUpdateProductCombination($product, $token . '-s', [$medium, $cotton], $combination, ['price' => 2]);
    $check((int) $importer->getProductCombination($product, [$small, $cotton])->id === (int) $combination->id, 'An ordinary update replaced combination attributes.');
    $importer->setProductCombinationAttributes($combination, [$medium, $cotton]);
    $check((int) $importer->getProductCombination($product, [$medium, $cotton])->id === (int) $combination->id, 'Explicit attribute replacement failed.');
    $check((bool) Db::getInstance()->getValue('SELECT id_image FROM `' . _DB_PREFIX_ . 'product_attribute_image` WHERE id_product_attribute = ' . (int) $combination->id), 'Attribute replacement removed image associations.');
    $neighbor = dirname($base) . '/999999999-home_default.jpg';
    $check(!file_exists($neighbor), 'Sentinel filename already exists.');
    file_put_contents($neighbor, 'must survive');
    $temporaryFiles[] = $neighbor;
    $images->deleteThumbs($image);
    $check(is_file($base . '.jpg') && file_get_contents($neighbor) === 'must survive', 'Thumbnail cleanup removed another image or the original.');
    $images->generateProductImageThumbs($image);
    $before = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'image` WHERE id_product = ' . (int) $product->id);
    $failing = new class extends ImageImporter {
        public function generateProductImageThumbs(Image $image, bool $delete_thumbs = true): void
        {
            throw new RuntimeException('Expected thumbnail failure.');
        }
    };
    try {
        $failing->importProductImageFromFile($product, $source);
        throw new RuntimeException('Expected an import failure.');
    } catch (RuntimeException $exception) {
        $check($exception->getMessage() === 'Expected thumbnail failure.', 'Import failure rollback failed.');
    }
    $after = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'image` WHERE id_product = ' . (int) $product->id);
    $check($before === $after, 'A failed image import left an orphan row.');
    $corrupt = tempnam(sys_get_temp_dir(), 'dartmoon-image-');
    $temporaryFiles[] = $corrupt;
    file_put_contents($corrupt, substr(file_get_contents($source), 0, 33));
    $check(@getimagesize($corrupt) !== false, 'Corrupt fixture must retain a readable image header.');
    try {
        $images->importProductImageFromFile($product, $corrupt);
        throw new RuntimeException('The corrupt image should not be written.');
    } catch (Throwable $exception) {
        $check(!$exception instanceof InvalidArgumentException && $exception->getMessage() !== 'The corrupt image should not be written.', 'The failed original write was not exercised.');
    }
    $check($before === (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'image` WHERE id_product = ' . (int) $product->id), 'A failed original image write left an orphan row.');
    $search = new ProductSearch();
    $found = $search->search($token);
    $check(count($found) === 1 && $found[0]['id'] === (int) $product->id, 'Scoped product search failed.');
    $check($search->search($token . '%') === [], 'Product search treated a literal wildcard as a pattern.');
    $check($search->find((int) $product->id)['id'] === (int) $product->id, 'Product search ID lookup failed.');

    $context->smarty->setTemplateDir($adminDirectories);
    $context->employee = new Employee((int) Db::getInstance()->getValue('SELECT id_employee FROM `' . _DB_PREFIX_ . 'employee` WHERE active = 1 ORDER BY id_employee'));
    $form = new HelperForm();
    $form->first_call = false;
    $form->table = 'dartmoon_probe';
    $form->currentIndex = '#';
    $form->default_form_language = $languageId;
    $form->languages = Language::getLanguages(false);
    $form->fields_value = ['amount' => 0, 'position' => '1 2 3'];
    $html = $form->generateForm([['form' => ['legend' => ['title' => 'Toolkit'], 'input' => [
        ['type' => 'number', 'name' => 'amount', 'label' => 'Amount', 'attributes' => ['min' => 0]],
        ['type' => 'vector3', 'name' => 'position', 'label' => 'Position'],
    ]]]]);
    $check(str_contains($html, 'type="number"') && str_contains($html, 'data-dartmoon-vector'), 'Native form did not render extended fields.');
    $list = new HelperList();
    $list->table = 'dartmoon_probe';
    $list->identifier = 'id_item';
    $list->currentIndex = '#';
    $list->simple_header = true;
    $list->no_link = true;
    $callback = new class {
        public function computed($value, $row): string
        {
            return 'computed-' . $row['id_item'];
        }
    };
    $html = $list->generateList([['id_item' => 1]], ['computed' => ['title' => 'Computed', 'callback' => 'computed', 'callback_object' => $callback]]);
    $check(str_contains($html, 'computed-1'), 'Native list did not invoke a computed-column callback.');
    $check(is_subclass_of(Dartmoon\Controllers\Admin\AbstractAdminController::class, ModuleAdminController::class), 'Legacy controller base is incompatible.');
    $legacy = new class extends Dartmoon\Controllers\Admin\AbstractAdminController {
        public function __construct()
        {
        }

        protected function getConfigFormDefinition()
        {
            return [['form' => ['input' => [['type' => 'number', 'name' => 'price']]]]];
        }

        public function copyFields($object): void
        {
            $this->copyFromPost($object, 'product');
        }
    };
    $post = $_POST;
    try {
        $_POST = ['price' => '0', 'active' => '1'];
        $active = $product->active;
        $legacy->copyFields($product);
        $check($product->price === '0' && $product->active === $active, 'Form copying accepted an undeclared field or dropped zero.');
    } finally {
        $_POST = $post;
    }
    $formProbe = new class extends Dartmoon\Controllers\Admin\AbstractAdminController {
        public function __construct()
        {
        }

        protected function getConfigFormDefinition()
        {
            return [['form' => ['input' => [['type' => 'number', 'name' => 'price']]]]];
        }

        protected function loadObject($opt = false)
        {
            $object = new Product();
            $object->price = '7.25';

            return $object;
        }

        public function getFieldsValue($object)
        {
            $values = parent::getFieldsValue($object);
            if (($values['price'] ?? null) !== '7.25') {
                throw new RuntimeException('Legacy form values were read before its fields were defined.');
            }
            throw new LogicException('Expected form probe completed.');
        }
    };
    try {
        $formProbe->renderForm();
        throw new RuntimeException('Form probe did not run.');
    } catch (LogicException $exception) {
        $check($exception->getMessage() === 'Expected form probe completed.', 'Legacy form initialization failed.');
    }

    $context->smarty->setTemplateDir($templateDirectories);
    $check(!file_exists($moduleDirectory), 'Temporary module path already exists.');
    mkdir($moduleDirectory . '/views/templates/front', 0755, true);
    $moduleCreated = true;
    file_put_contents($moduleDirectory . '/' . $token . '.php', '<?php class ' . ucfirst($token) . ' extends Module { public function __construct() { $this->name = ' . var_export($token, true) . '; parent::__construct(); } public function getPathUri() { return __PS_BASE_URI__ . "modules/" . $this->name . "/"; } }');
    file_put_contents($moduleDirectory . '/views/templates/front/probe.tpl', '{$message|escape:"html":"UTF-8"}|{$module_path_uri|escape:"html":"UTF-8"}');
    $module = Module::getInstanceByName($token);
    $renderer = new TemplateRenderer($module);
    $html = $renderer->render('front/probe.tpl', ['message' => '<unsafe>']);
    $check(str_contains($html, '&lt;unsafe&gt;') && str_contains($html, $module->getPathUri()), 'Module renderer did not resolve its template and URI.');
    $check($context->smarty->getTemplateVars('message') === null, 'Renderer values leaked into the global Smarty context.');
    echo "PrestaShop toolkit integration passed ({$checks} checks).\n";
} finally {
    $context->smarty->setTemplateDir($templateDirectories);
    $context->employee = $originalEmployee;
    foreach ($temporaryFiles as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    foreach (array_reverse($models) as $model) {
        if ($model->id && !$model->delete()) {
            throw new RuntimeException('Cannot clean up test ' . get_class($model) . ' ID ' . $model->id);
        }
    }
    if ($moduleCreated) {
        unlink($moduleDirectory . '/views/templates/front/probe.tpl');
        unlink($moduleDirectory . '/' . $token . '.php');
        rmdir($moduleDirectory . '/views/templates/front');
        rmdir($moduleDirectory . '/views/templates');
        rmdir($moduleDirectory . '/views');
        rmdir($moduleDirectory);
    }
}
