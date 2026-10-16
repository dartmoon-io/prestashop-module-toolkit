# PrestaShop Module Toolkit

`dartmoon/prestashop-module-toolkit` is the shared runtime package for the
[module skeleton](https://github.com/dartmoon-io/prestashop-module). It provides Assets, Config, Console, Hooks,
TabManager and Utils into one Composer dependency, while retaining their
`Dartmoon\…` namespaces. The toolkit contains no module build tools.

## Documentation

Start with the [documentation index](docs/README.md) and
[getting started guide](docs/getting-started.md). Each library has a usage guide:

- [Assets](docs/libraries/assets.md)
- [Config](docs/libraries/config.md)
- [Console](docs/libraries/console.md)
- [Hooks](docs/libraries/hooks.md)
- [TabManager](docs/libraries/tab-manager.md)
- [Utils and multilingual text](docs/libraries/utils.md)

## Requirements

PHP 8.1+ and PrestaShop 9.2+. Symfony Console is supplied by PrestaShop (6.4 in
9.2); it is a development dependency here, not a second Symfony installation
inside every module. Future PrestaShop versions require the integration suite
to be rerun; this package does not claim untested future compatibility.

```sh
composer require dartmoon/prestashop-module-toolkit:^1.0
```

Before publication, use a Composer path repository with `symlink: false` and
`options.versions.dartmoon/prestashop-module-toolkit: 1.0.0`; the skeleton's
`php bin/module setup --toolkit-path=/path/to/prestashop-module-toolkit` does this.
The `replace` entries prevent installing the five older packages alongside it.

## Components

### Assets

```php
return \Dartmoon\Assets\AssetManager::forModule($module)->render([
    '_dev/scss/front.scss',
    '_dev/js/front.js',
]);
```

Resolves Vite's `views/assets/manifest.json`, including hashed filenames and CSS
from static imports. Returns escaped stylesheet links and JavaScript module tags
for display hooks. `url($entry)` resolves an individual public URL. Missing
builds are optional; invalid manifests and missing entries report errors.

### Config

```php
$config = new \Dartmoon\Config\Config(__DIR__ . '/config');
$value = $config->get('module.api.timeout', 10);
```

PHP config files return arrays. Dot paths support arbitrary depth and preserve
explicit `null` and `false`. Files are cached per repository instance and file
names cannot traverse directories. Inject a repository for each module; paths
are no longer inferred from vendor directory depth. The compatibility facade
requires `Config::setRepository($repository)` before use. Avoid a shared facade
in modules that are loaded without isolation.

### Console

Extend `Dartmoon\Console\AbstractCommand`, implement `execute(): int`, and
register the command as a Symfony service (the skeleton has an example).
Constructor injection replaces the removed `ContainerAwareCommand` and
`getContainer()`. `$name` and `$description` remain supported. A custom
constructor must call `parent::__construct()`.

### Hooks

Extend `Dartmoon\Hooks\AbstractHookGroup`, set `$hooks` to canonical PrestaShop
names and implement matching public methods accepting an array. Put group
classes in the module's `$hooks` list and use `HasHookDispatcher`.

Registration validates handlers; lookup is case insensitive. Multiple groups
can handle display hooks and their strings are concatenated in registration
order. A single handler's return value is preserved, including arrays or
booleans; multiple handlers returning structured values throw rather than
silently discarding data. Unknown hooks return `null` for PrestaShop's hook
probing; non-hook method typos throw `BadMethodCallException`. Use canonical
hook names rather than legacy aliases (for example,
`actionFrontControllerSetMedia` for asset registration).

### Tabs

```php
$tabs = new \Dartmoon\TabManager\TabManager();
$tabs->install([
    ['class_name' => 'AdminMyModule', 'name' => ['en' => 'My module', 'it' => 'Il mio modulo']],
    ['class_name' => 'AdminMyModuleSettings', 'name' => 'Settings',
     'parent_class_name' => 'AdminMyModule', 'route_name' => 'my_module_settings'],
], $module);
```

Uses the public `Tab` ObjectModel API, including ACL creation, without depending
on a Symfony container or internal Doctrine repositories. Supports `icon`,
`visible` (defaults to true), `wording` and `wording_domain`. Declare parents
before children; a missing parent or a tab owned by another module is an error.
Repeated installation preserves existing tabs owned by this module. Newly
created tabs are rolled back on failure. `uninstallForModule($module)` removes
children first and reports failures; `uninstall($definitions)` is idempotent.
Choose this manager or native `Module::$tabs` for each tab, not both.

### Multilingual text

`Dartmoon\Utils\MultiLangText::generate()` accepts a string or translation array.
Resolution uses locale, language code, ISO code, then numeric language ID;
missing translations use the first provided value. An empty array produces
empty strings. Disabled languages are included. The filename is now correctly
`MultiLangText.php`, fixing PSR-4 loading on case-sensitive filesystems.

## Development

```sh
composer install
composer test
```

Unit tests cover configuration isolation and traversal, hook dispatch and
return values, console execution, translations, tab ownership, retries,
rollback and uninstall failures. The skeleton additionally tests production
scoping and two modules loaded in the same process.
