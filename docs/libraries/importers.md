# Catalog and image importers

[Documentation index](../README.md)

These operations require a bootstrapped PrestaShop 9.2 shop and its database.
Run them from a module command or controller with a single shop selected.
They do not disable/delete products that are absent from a supplier feed.

## Products and categories

```php
use Dartmoon\Importers\Importer;

$importer = new Importer();
$category = $importer->getOrAddCategory('Accessories', 'accessories');
$existing = $importer->getProductByReference('SKU-123');
$product = $importer->addOrUpdateProduct(
    name: ['en' => 'Example product', 'it' => 'Prodotto di esempio'],
    description: 'Full description',
    description_short: 'Short description',
    reference: 'SKU-123',
    link_rewrite: 'example-product',
    categories: [$category],
    active: false,
    extras: ['price' => 12.50],
    product: $existing,
);
```

The helper never guesses the product to update. Supply the existing Product or
perform an explicit lookup first. Reference/name lookups are scoped to the
current shop; duplicate matches raise `UnexpectedValueException`. An empty
reference is not valid for a lookup.

- The current shop's home category is used, without assuming its ID is `2`.
- The first supplied category becomes the default before saving. An update
  without categories preserves existing assignments.
- `description: null` and `description_short: null` preserve existing text;
  an empty string clears it. Short-only updates work independently.
- Missing `link_rewrite` is generated from each translated name.
- Products default to inactive. Set `active` explicitly when updating a live item.
- Extras must be defined ObjectModel fields. Primary/ownership/default-category
  identifiers cannot be overwritten through extras.
- Scalar and translated values use the Utils library, including disabled languages.
- Failed saves/lookups raise exceptions. An entire import is not one transaction;
  handle per-item errors and plan retries in your application.

The other category operations are `getCategory()` and `addCategory()`. They
accept an optional parent Category. `getProductByName($name, $id_lang = null)`,
`productExists($reference)` and `getManufacturerByName($name)` provide lookups.
`generateDescriptionShort()` creates a Unicode-safe plain-text excerpt using
`PS_PRODUCT_SHORT_DESC_LIMIT`. `getMultiLangText()` exposes the translation helper.

## Features and combinations

```php
$size = $importer->getOrAddAttributeGroup('Size', 'select');
$small = $importer->getOrAddAttribute($size, 'Small');
$combination = $importer->getOrAddProductCombination($product, [$small], 'SKU-123-S');

$importer->addOrUpdateProductCombination(
    $product, 'SKU-123-S', [$small], $combination, ['price' => 2.50]
);
```

PrestaShop's `ProductAttribute` is used instead of PHP's `Attribute`. A
combination accepts attribute objects or IDs, with one attribute per group.
Matching uses the complete attribute set, not a subset. Ordinary updates
preserve attribute associations. Explicitly replace them using
`setProductCombinationAttributes()` or `force_attribute_association: true`.
Image/cart links and the combination ID are preserved. Select the current shop
before editing; combination attributes themselves are shared across shops.
Stock quantities should be managed through PrestaShop's stock API.

`getProductCombinationByReference()` performs a scoped reference lookup;
`getProductCombination()` matches attributes. `getAttributeGroup()`/
`addAttributeGroup()` and `getAttributeFromAttributeGroup()`/
`addAttributeToAttributeGroup()` are available for explicit operations.

Use a saved Feature with `getOrAddValueOfFeature()`, `getValueOfFeature()` and
`addValueToFeature()`. Pass product features as
`[['feature' => $feature, 'value' => $featureValue]]`. Values must belong to the
specified feature. Existing unrelated features are preserved.

## Product images

```php
use Dartmoon\Importers\ImageImporter;

$images = new ImageImporter();
$image = $images->importProductImageFromUrl($product, 'https://example.com/product.jpg');
// Explicitly trusted local files use a separate method:
$image = $images->importProductImageFromFile($product, '/srv/import/image.png');
```

Images are validated before saving. Remote sources must be HTTP(S) and use
PrestaShop's `Tools::copyFromUntrustedSource()` download path. No Intervention
Image package is required. Native `ImageManager` controls JPEG conversion,
quality, configured formats, dimensions and image fitment.

Both import methods accept `cover: false` and `generate_thumbnails: true`.
If requesting a cover while one already exists, the helper raises an error;
change cover selection explicitly. `generateProductImageThumbs()` regenerates
configured image types/formats. `deleteThumbs()` deletes only known thumbnail
variants of that exact image, preserving the original and neighboring files.

Failures throw exceptions and roll back a newly created image. A rollback
failure reports its image ID. The internal `ImportedImage` subclass allows
cleanup when the first original-file write failed. Use PrestaShop image quality
configuration for image quality and format settings. ext-gd is required by the native image operations.

See the [public API reference](../api.md) for parameters, defaults, return types
and declared errors, and [extension points](../extensions.md) for replacement strategies.

## Inject policy collaborators

`new Importer($filters, $translations)` accepts a module-configured `FilterService`
and `MultiLangText`. Product slug generation uses that filter instance; translated
fields use the supplied translator. Defaults are per importer. Use `::class`
service definitions in `config/services.php` so production scoping rewrites
injection types and definitions together.
