# Utils: multilingual text

[Documentation index](../README.md)

The Utils component currently provides `Dartmoon\Utils\MultiLangText`. It
converts one label or a set of translations into an array keyed by language ID,
as required by multilingual PrestaShop ObjectModel properties.

## Repeat one label across shop languages

Inside a bootstrapped shop:

```php
use Dartmoon\Utils\MultiLangText;

$text = new MultiLangText();
$names = $text->generate('Settings');
// For a shop with language IDs 1 and 2:
// [1 => 'Settings', 2 => 'Settings']
```

By default, `Language::getLanguages(false)` supplies all shop languages,
including disabled ones. This keeps translations complete when a language is
enabled later. The helper does not persist or validate the target ObjectModel.

## Supply translations

```php
$names = $text->generate([
    'en-US' => 'Settings',
    'it' => 'Impostazioni',
]);
```

For each language, the first matching key is used in this order:

1. `locale`, for example `en-US`.
2. `language_code`, for example `en-us`.
3. `iso_code`, for example `en`.
4. `id_lang`, for example integer key `1`.

Keys match the supplied language metadata exactly, including case. Missing
translations use the first provided value; there is no separate default-language
lookup. Put a suitable fallback first. An empty translation array produces an
empty string for every language. Empty strings explicitly supplied for a
matching key are preserved.

## Use an explicit language list

The second argument is useful for deterministic tests or limiting the output:

```php
$languages = [
    ['id_lang' => 1, 'locale' => 'en-US', 'language_code' => 'en-us', 'iso_code' => 'en'],
    ['id_lang' => 2, 'locale' => 'it-IT', 'language_code' => 'it-it', 'iso_code' => 'it'],
];

$names = $text->generate(['en' => 'Settings', 'it' => 'Impostazioni'], $languages);
// [1 => 'Settings', 2 => 'Impostazioni']
$none = $text->generate('Settings', []); // []
```

Supplying this list avoids calling the host `Language` API. Each entry must
contain `id_lang`; include locale/code fields when you want those lookup forms.
Array translation values are converted to strings, so provide text values.

## Assign and save

For an already constructed multilingual ObjectModel:

```php
$model->name = $text->generate(['en' => 'Settings', 'it' => 'Impostazioni']);
if (!$model->save()) {
    throw new RuntimeException('Could not save translated names.');
}
```

TabManager uses this helper automatically for each definition's `name` field.
The compatibility facade `Dartmoon\Utils\Facades\MultiLangText::generate()`
forwards to a new helper instance. Use Composer autoloading and the correctly
spelled `MultiLangText` class; do not require the old `MutilLangText.php` filename.
