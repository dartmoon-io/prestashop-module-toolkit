# Assets

[Documentation index](../README.md)

`Dartmoon\Assets\AssetManager` resolves source entries through Vite's manifest
and renders the stylesheet links and JavaScript module tags returned by hooks.
Hooks choose which entries to load; the manager handles generated filenames.

## Use in a hook

```php
use Dartmoon\Assets\AssetManager;
use Dartmoon\Hooks\AbstractHookGroup;

class FrontOfficeAssetsHook extends AbstractHookGroup
{
    protected $hooks = ['displayHeader'];

    public function displayHeader(array $params): string
    {
        return AssetManager::forModule($this->module)->render([
            '_dev/scss/front.scss',
            '_dev/js/front.js',
        ]);
    }
}
```

The boilerplate also checks that the controller is a `FrontController`. Its
back-office group uses `displayBackOfficeHeader`, selects the back entries,
and loads them only on this module's configuration page.

Return the HTML from the hook. Do not echo it or pass Vite's ES modules to
PrestaShop's classic-script `addJS()` API. The manager produces
`<script type="module">` tags so shared and dynamic JavaScript imports work.

Use the canonical `displayHeader` name. PrestaShop retains `header` as a legacy
alias, but the toolkit dispatcher expects the names declared in the group to
match the hook dispatched by PrestaShop. `displayBackOfficeHeader` is current.
See the official [front-office hook](https://devdocs.prestashop-project.org/9/modules/concepts/hooks/list-of-hooks/displayheader/)
and [back-office hook](https://devdocs.prestashop-project.org/9/modules/concepts/hooks/list-of-hooks/displaybackofficeheader/) references.

## Resolve an individual URL

```php
$assets = AssetManager::forModule($module);
$url = $assets->url('_dev/js/front.js');
// /shop/modules/mymodule/views/assets/front-abc123.js
```

Pass source paths exactly as listed in the manifest, not generated filenames.
`url()` also supports image or font entries present in the manifest. It returns
an unescaped URL; escape it appropriately when using it in your own HTML.
`render()` escapes URLs for HTML attributes automatically.

## Paths and configuration

The defaults match the boilerplate: `views/assets/manifest.json` relative to
the module root. `forModule()` obtains the filesystem root and public URI from
PrestaShop's `Module::getLocalPath()` and `Module::getPathUri()`, preserving shop
subdirectories. Each instance belongs to one module and caches its own manifest.

For another build directory:

```php
$assets = AssetManager::forModule($module, 'public/build');
```

Outside a shop, supply the paths explicitly:

```php
$assets = new AssetManager('/srv/modules/mymodule', '/shop/modules/mymodule/');
echo $assets->render(['_dev/scss/front.scss', '_dev/js/front.js']);
```

Configure Vite to write its manifest as `manifest.json` in the chosen build
directory. Deploy the manifest together with all generated files. The
boilerplate's production builder already includes `views/`.

## Rendering and errors

- `render()` accepts one source entry or an array. It emits styles before scripts.
- CSS associated with static imports is included recursively, in dependency
  order. Cycles and duplicate filenames are handled without duplicate tags.
- Static JavaScript imports are loaded by the browser from the entry module.
  Dynamic imports remain lazy; their CSS is handled by Vite's generated loader.
- Deduplication applies within one `render()` call. Pass related entries together.
- Before the first asset build, a missing manifest produces `null` from `url()`
  and an empty string from `render()`. PHP-only modules need no Vite build.
- A missing entry in a populated manifest raises `OutOfBoundsException`.
  Invalid JSON raises `JsonException`; malformed entries and unsafe output paths
  raise `UnexpectedValueException`. Rebuild stale assets instead of hiding errors.

The manager follows [Vite's backend integration format](https://vite.dev/guide/backend-integration).
Run `npm run build` or `npm run dev` from the generated module. Source placeholders
remain in `_dev/js/` and `_dev/scss/`; the Vite configuration stays in the module.
