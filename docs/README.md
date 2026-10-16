# Toolkit documentation

The toolkit provides six libraries through one Composer package,
`dartmoon/prestashop-module-toolkit`. Their namespaces are `Dartmoon\Assets`, `Dartmoon\Config`,
`Dartmoon\Console`, `Dartmoon\Hooks`, `Dartmoon\TabManager` and `Dartmoon\Utils`.

| Guide | Use it to |
| --- | --- |
| [Getting started](getting-started.md) | Install the package and understand the PrestaShop runtime |
| [Assets](libraries/assets.md) | Resolve Vite manifest entries and render CSS/JavaScript from hooks |
| [Config](libraries/config.md) | Read module configuration files and inject a repository |
| [Console](libraries/console.md) | Create Symfony console commands with injected dependencies |
| [Hooks](libraries/hooks.md) | Organize hook handlers and register them with a module |
| [TabManager](libraries/tab-manager.md) | Install, translate and remove back-office menu tabs |
| [Utils](libraries/utils.md) | Generate multilingual ObjectModel values |

Examples use a module named `mymodule` with the application namespace
`Acme\MyModule`. Substitute your generated module's name and namespace.
Commands run from the module root unless a guide explicitly says otherwise.

For creating, building and installing a complete module, use the
[prestashop-module documentation](https://github.com/dartmoon-io/prestashop-module/tree/main/docs).
These guides cover the shared runtime components; build tools belong to the
boilerplate repository.
