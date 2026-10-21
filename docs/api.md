# Public API reference

[Documentation index](README.md)

Signatures list all declared public methods and protected extension points. Parameters
use PHP types and defaults; untyped methods inherit the host PrestaShop API. Return
types describe successful results; exceptions propagate unless a component guide
documents rollback. Do not call protected methods from application services.

## `Dartmoon\Assets\AssetManager`

[Purpose, prerequisites, examples and behavior](libraries/assets.md).

```php
public function __construct(private string $moduleDirectory, private string $moduleUri, private string $buildDirectory = 'views/assets');
```

```php
public static function forModule(\Module $module, string $buildDirectory = 'views/assets'): static;
```

```php
public function url(string $entry): ?string;
```

Return the public URL for a source entry, or null before assets are built.

```php
public function render(string|array $entries): string;
```

Render styles before scripts, including CSS belonging to static imports.

Declared errors: `InvalidArgumentException`, `UnexpectedValueException`, `OutOfBoundsException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Config\Config`

[Purpose, prerequisites, examples and behavior](libraries/config.md).

```php
public function __construct(private string $configFilePath);
```

```php
public function get(string $path, mixed $default = false): mixed;
```

```php
public function getConfig(string $path, mixed $default = false): mixed;
```

```php
public function getConfigValue(array $config, string $key, mixed $default = false): mixed;
```

```php
public function getConfigFile(string $fileName): array;
```

Declared errors: `InvalidArgumentException`, `UnexpectedValueException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Config\Facades\Config`

[Purpose, prerequisites, examples and behavior](libraries/config.md).

```php
protected static function serviceClass(): string;
```

```php
protected static function create(object $scope): object;
```

Declared errors: `LogicException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Console\AbstractCommand`

[Purpose, prerequisites, examples and behavior](libraries/console.md).

```php
protected function configure(): void;
```

Errors: collaborator/host errors propagate; validation is described in the component guide.

## `Dartmoon\Controllers\Admin\AbstractAdminController`

[Purpose, prerequisites, examples and behavior](libraries/helpers.md).

```php
abstract protected function getConfigFormDefinition();
```

```php
protected function initFormToolbar();
```

```php
public function __construct();
```

```php
public function renderForm();
```

```php
public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false);
```

```php
protected function copyFromPost(&$object, $table);
```

Copy only declared form fields; preserve native password hashing.

```php
public function ajaxProcessSearchProductByName();
```

```php
public function ajaxProcessGetProductById();
```

```php
protected function createListLink(array $options): string;
```

Declared errors: `PrestaShopException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Downloaders\AbstractDownloader`

[Purpose, prerequisites, examples and behavior](libraries/downloaders.md).

```php
abstract public function download(string $srcFile, string $dstFile, array $credentials = []): string;
```

Errors: collaborator/host errors propagate; validation is described in the component guide.

## `Dartmoon\Downloaders\FTPDownloader`

[Purpose, prerequisites, examples and behavior](libraries/downloaders.md).

```php
public function download(string $srcFile, string $dstFile, array $credentials = []): string;
```

Declared errors: `LogicException`, `InvalidArgumentException`, `RuntimeException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Filters\Facades\Filter`

[Purpose, prerequisites, examples and behavior](libraries/filters.md).

```php
protected static function serviceClass(): string;
```

Errors: collaborator/host errors propagate; validation is described in the component guide.

## `Dartmoon\Filters\FilterService`

[Purpose, prerequisites, examples and behavior](libraries/filters.md).

```php
public function __construct(iterable $filters = []);
```

```php
public function registerFilter(AbstractFilter $filter): self;
```

```php
public function filter(mixed $value, string|array $filters): mixed;
```

Array notation supports literal pipes/colons: ['append' => ['a|b:c']].

Declared errors: `InvalidArgumentException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Filters\Filters\AbstractFilter`

[Purpose, prerequisites, examples and behavior](libraries/filters.md).

```php
public function getName(): string;
```

```php
abstract public function filter(mixed $value, array $params): mixed;
```

Errors: collaborator/host errors propagate; validation is described in the component guide.

## `Dartmoon\Filters\Filters\Append`

[Purpose, prerequisites, examples and behavior](libraries/filters.md).

```php
public function filter(mixed $value, array $params): string;
```

Declared errors: `InvalidArgumentException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Filters\Filters\Clean`

[Purpose, prerequisites, examples and behavior](libraries/filters.md).

```php
public function filter(mixed $value, array $params): string;
```

Declared errors: `InvalidArgumentException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Filters\Filters\Slugify`

[Purpose, prerequisites, examples and behavior](libraries/filters.md).

```php
public function filter(mixed $value, array $params): string;
```

Declared errors: `InvalidArgumentException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Filters\Filters\Title`

[Purpose, prerequisites, examples and behavior](libraries/filters.md).

```php
public function filter(mixed $value, array $params): string;
```

Declared errors: `LogicException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Helpers\HelperForm`

[Purpose, prerequisites, examples and behavior](libraries/helpers.md).

```php
public function generate();
```

```php
public static function prepareFields(array $fieldsets, array $values): array;
```

Declared errors: `InvalidArgumentException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Helpers\HelperList`

[Purpose, prerequisites, examples and behavior](libraries/helpers.md).

```php
public function generateList($list, $fields_display);
```

```php
public static function prepareRows(array $rows, array $fields): array;
```

Core invokes a column callback only when the row contains its field key.

Errors: collaborator/host errors propagate; validation is described in the component guide.

## `Dartmoon\Helpers\ProductSearch`

[Purpose, prerequisites, examples and behavior](libraries/helpers.md).

```php
public function search(string $query, int $limit = 20, int $offset = 0): array;
```

Bounded results restricted to the current shop and language.

```php
public function find(int $id): ?array;
```

Declared errors: `InvalidArgumentException`, `RuntimeException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Hooks\AbstractHookGroup`

[Purpose, prerequisites, examples and behavior](libraries/hooks.md).

```php
public function __construct(protected \Module $module, protected \Context $context);
```

```php
public function getHooks();
```

Errors: collaborator/host errors propagate; validation is described in the component guide.

## `Dartmoon\Hooks\Contracts\HookDispatcher`

[Purpose, prerequisites, examples and behavior](libraries/hooks.md).

```php
public function register(HookGroup $group): void;
```

```php
public function getAvailableHooks(): array;
```

```php
public function dispatch(string $hookName, array $params = []): mixed;
```

Errors: collaborator/host errors propagate; validation is described in the component guide.

## `Dartmoon\Hooks\Contracts\HookGroup`

[Purpose, prerequisites, examples and behavior](libraries/hooks.md).

```php
public function getHooks();
```

@return string|string[] Canonical PrestaShop hook names, without the hook prefix.

Errors: collaborator/host errors propagate; validation is described in the component guide.

## `Dartmoon\Hooks\HookDispatcher`

[Purpose, prerequisites, examples and behavior](libraries/hooks.md).

```php
public function register(HookGroup $group): void;
```

```php
public function getAvailableHooks(): array;
```

```php
public function dispatch(string $hookName, array $params = []): mixed;
```

Declared errors: `InvalidArgumentException`, `LogicException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Hooks\Traits\HasHookDispatcher`

[Purpose, prerequisites, examples and behavior](libraries/hooks.md).

```php
protected function initHookDispatcher(): void;
```

```php
protected function createHookDispatcher(): DispatcherContract;
```

```php
protected function createHookGroup(string|HookGroup $group): HookGroup;
```

Override to inject handler dependencies using your container or factories.

```php
public function getHookDispatcher(): DispatcherContract;
```

```php
public function __call(string $methodName, array $arguments): mixed;
```

Declared errors: `BadMethodCallException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Importers\ImageImporter`

[Purpose, prerequisites, examples and behavior](libraries/importers.md).

```php
public function importProductImageFromUrl(\Product $product, string $url, bool $cover = false, bool $generate_thumbnails = true): \Image;
```

```php
public function importProductImageFromFile(\Product $product, string $file, bool $cover = false, bool $generate_thumbnails = true): \Image;
```

Local files are explicit, trusted inputs; never interpret a remote URL as a local path.

```php
public function generateProductImageThumbs(\Image $image, bool $delete_thumbs = true): void;
```

```php
public function deleteThumbs(\Image $image): void;
```

Delete only declared variants for this exact image, never neighboring originals.

Declared errors: `InvalidArgumentException`, `RuntimeException`, `LogicException`, `UnexpectedValueException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Importers\ImportedImage`

[Purpose, prerequisites, examples and behavior](libraries/importers.md).

```php
public function deleteImage($forceDelete = false);
```

Errors: collaborator/host errors propagate; validation is described in the component guide.

## `Dartmoon\Importers\Importer`

[Purpose, prerequisites, examples and behavior](libraries/importers.md).

```php
public function __construct(protected FilterService $filters = new FilterService(), protected MultiLangText $translations = new MultiLangText());
```

```php
public function getOrAddCategory(string|array $name, string|array $link_rewrite, bool $active = false, ?\Category $parent = null): \Category;
```

```php
public function getCategory(string|array $link_rewrite, ?\Category $parent = null): ?\Category;
```

```php
public function addCategory(string|array $name, string|array $link_rewrite, bool $active = false, ?\Category $parent = null): \Category;
```

```php
public function addOrUpdateProduct(string|array $name, string|array|null $description = null, string|array|null $description_short = null, string $reference = '', string|array|null $link_rewrite = null, array $categories = [], array $features = [], bool $active = false, array $extras = [], ?\Product $product = null): \Product;
```

```php
public function productExists(string $reference): bool;
```

```php
public function getProductByReference(string $reference): ?\Product;
```

```php
public function getProductByName(string $name, ?int $id_lang = null): ?\Product;
```

```php
public function getOrAddProductCombination(\Product $product, array $attributes, string $reference): \Combination;
```

```php
public function getProductCombinationByReference(\Product $product, string $reference): ?\Combination;
```

```php
public function getProductCombination(\Product $product, array $attributes): ?\Combination;
```

```php
public function addOrUpdateProductCombination(\Product $product, string $reference, array $attributes, ?\Combination $combination = null, array $extras = [], bool $force_attribute_association = false): \Combination;
```

```php
public function setProductCombinationAttributes(\Combination $combination, array $attributes): bool;
```

Preserve image/cart associations; native Combination::setAttributes deletes image links.

```php
public function getOrAddValueOfFeature(\Feature $feature, string|array $feature_value): \FeatureValue;
```

```php
public function getValueOfFeature(\Feature $feature, string|array $feature_value): ?\FeatureValue;
```

```php
public function addValueToFeature(\Feature $feature, string|array $feature_value): \FeatureValue;
```

```php
public function getOrAddAttributeGroup(string|array $name, string $type, ?int $id_lang = null): \AttributeGroup;
```

```php
public function getAttributeGroup(string|array $name, string $type, ?int $id_lang = null): ?\AttributeGroup;
```

```php
public function addAttributeGroup(string|array $name, string $type, ?int $id_lang = null): \AttributeGroup;
```

```php
public function getOrAddAttribute(\AttributeGroup $attribute_group, string|array $value, ?int $id_lang = null): \ProductAttribute;
```

```php
public function getAttributeFromAttributeGroup(\AttributeGroup $attribute_group, string|array $value, ?int $id_lang = null): ?\ProductAttribute;
```

```php
public function addAttributeToAttributeGroup(\AttributeGroup $attribute_group, string|array $value): \ProductAttribute;
```

```php
public function getManufacturerByName(string $name): ?\Manufacturer;
```

```php
public function generateDescriptionShort(string $description): string;
```

```php
public function getMultiLangText(string|array $text): array;
```

Declared errors: `InvalidArgumentException`, `RuntimeException`, `UnexpectedValueException`, `LogicException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Log\Log`

[Purpose, prerequisites, examples and behavior](libraries/logging.md).

```php
public function __construct(private string $directory, private int $level = self::DEBUG);
```

```php
public function log(string $message, int $level = self::DEBUG, bool $echo = false): void;
```

```php
public function emergency(string $message): void;
```

```php
public function alert(string $message): void;
```

```php
public function critical(string $message): void;
```

```php
public function error(string $message): void;
```

```php
public function warning(string $message): void;
```

```php
public function notice(string $message): void;
```

```php
public function info(string $message): void;
```

```php
public function debug(string $message): void;
```

Declared errors: `InvalidArgumentException`, `RuntimeException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Renderers\HtmlAttributes`

[Purpose, prerequisites, examples and behavior](libraries/renderers.md).

```php
public static function render(array $attributes): string;
```

Declared errors: `InvalidArgumentException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Renderers\TemplateRenderer`

[Purpose, prerequisites, examples and behavior](libraries/renderers.md).

```php
public function __construct(private \Module $module);
```

```php
public function render(string $template, array $data = []): string;
```

Declared errors: `InvalidArgumentException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Support\Facade`

[Purpose, prerequisites, examples and behavior](libraries/../extensions.md).

```php
abstract protected static function serviceClass(): string;
```

```php
protected static function create(object $scope): object;
```

```php
public static function for(object $scope): object;
```

Resolve once per module object; release the scope when the module is released.

```php
public static function swap(object $scope, object $service): void;
```

Bind a subclass/implementation for this facade and module only.

```php
public static function forget(object $scope): void;
```

Declared errors: `InvalidArgumentException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\TabManager\Facades\TabManager`

[Purpose, prerequisites, examples and behavior](libraries/tab-manager.md).

```php
protected static function serviceClass(): string;
```

Errors: collaborator/host errors propagate; validation is described in the component guide.

## `Dartmoon\TabManager\TabManager`

[Purpose, prerequisites, examples and behavior](libraries/tab-manager.md).

```php
public function __construct(private MultiLangText $translations = new MultiLangText());
```

```php
public function install(array $tabs, ?\Module $module = null): bool;
```

```php
public function uninstall(array $tabs): bool;
```

```php
public function uninstallForModule(\Module $module): bool;
```

Declared errors: `InvalidArgumentException`, `RuntimeException`. Host/filesystem/template errors may also propagate.

## `Dartmoon\Utils\Facades\MultiLangText`

[Purpose, prerequisites, examples and behavior](libraries/utils.md).

```php
protected static function serviceClass(): string;
```

Errors: collaborator/host errors propagate; validation is described in the component guide.

## `Dartmoon\Utils\MultiLangText`

[Purpose, prerequisites, examples and behavior](libraries/utils.md).

```php
public function generate(string|array $text, ?array $languages = null): array;
```

Include disabled languages so ObjectModel translations remain complete.

Errors: collaborator/host errors propagate; validation is described in the component guide.

