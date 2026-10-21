# Getting started

[Documentation index](README.md)

## Requirements and installation

The runtime requires PHP 8.1+; the verified host is PrestaShop 9.2.0. The Config and dispatcher
utilities can be used without a running shop, but hooks need the shop's `Module`
and `Context`, TabManager needs its database and `Tab` API, and console commands
need the Symfony Console supplied by PrestaShop.

The recommended entry point is
[prestashop-module](https://github.com/dartmoon-io/prestashop-module), with projects
created through the separate global
[prestashop-installer](https://github.com/dartmoon-io/prestashop-installer)
command, `prestashop create-module <module-name>`. Generated projects already
require the toolkit and load `vendor/autoload.php` from the
module entry file. Do not install the five abandoned component packages beside
the toolkit; its Composer `replace` entries consolidate them.

Once the toolkit package is published, an existing module can install it with:

```sh
composer require dartmoon/prestashop-module-toolkit:^1.0
```

Before publication, add a local Composer repository to the module's
`composer.json`, using a path relative to that module:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../prestashop-module-toolkit",
            "options": {
                "symlink": false,
                "versions": {"dartmoon/prestashop-module-toolkit": "1.0.0"}
            }
        }
    ],
    "require": {"dartmoon/prestashop-module-toolkit": "^1.0"}
}
```

Merge these keys into the existing manifest, then run `composer update
dartmoon/prestashop-module-toolkit`. The boilerplate's `setup --toolkit-path=...`
option configures this repository for new modules automatically. Local packages
are mirrored; reinstall or update the dependency after changing toolkit code.

## Use the component APIs

Write ordinary imports in your application:

```php
use Dartmoon\Config\Config;
use Dartmoon\Assets\AssetManager;
use Dartmoon\Hooks\AbstractHookGroup;
use Dartmoon\TabManager\TabManager;
use Dartmoon\Utils\MultiLangText;
```

The boilerplate scopes dependency declarations and application references
together when building a production artifact. Do not manually add
`Dependencies` to these imports or edit generated vendor files. Install built
modules in the shop when verifying that several modules can coexist.

Symfony services and commands use the shop's Symfony APIs. The toolkit does
not bundle a second Symfony runtime; install its development dependencies only
when working on the toolkit itself.

## Supply implementations

Inject services using PrestaShop's native Symfony definitions or explicit
constructors/factories during lifecycle operations. See the
[extension guide](extensions.md) and [API reference](api.md). Facades require
an explicit module scope; there is no implicit current-module state.

## Work on the toolkit

From the toolkit repository root:

```sh
composer install
composer test
```

The tests use small PrestaShop API doubles. Production scoping checks live in the boilerplate; native component integration
checks are in this repository under `tests/integration/`. Revalidate integration
when adopting another PrestaShop release.
