# Extension points and scopes

[Documentation index](README.md) · [Signatures](api.md)

Components remain PSR-4 mapped as `Dartmoon\<Component>\` in `src/<Component>/`.
Each directory owns its implementation, contracts where substitution is useful,
and optional `Facades/`. `Support/Facade` implements common scoped resolution.
No toolkit application kernel or container is required.

Prefer constructor injection in services registered with the host Symfony
container. The boilerplate's `config/services.php` registers a module-owned
`app/Filters/Greeting` and injects `FilterService` into its console command.
Replace that definition or pass a different filter instance to change behavior.

| Component | Customization |
| --- | --- |
| Assets | Inject `AssetManager` or subclass it; `forModule()` uses late static binding. Choose entries in your hook group. |
| Config | Pass a directory to `Config`; inject a subclass for alternate retrieval policy. Each instance owns its file cache. |
| Console | Extend `AbstractCommand`, inject services in the constructor and call `parent::__construct()`. Register using native command discovery. |
| Controllers | Extend `AbstractAdminController` for native ObjectModel forms, overriding form definitions and toolbar hooks; use host Symfony controllers for new pages. |
| Downloaders | Implement `AbstractDownloader::download()`; inject the downloader into your feed service. Credentials are explicit arguments. |
| Filters | Extend `AbstractFilter`; register by name on one `FilterService` or pass instances to its constructor. A matching name replaces a default. |
| Helpers | Extend native helper subclasses; `prepareRows()` and `HtmlAttributes` can be composed without replacing the renderer. |
| Hooks | Implement `HookGroup` and `HookDispatcher` contracts; override module trait factories to inject dependencies. |
| Importers | Inject `FilterService` and `MultiLangText` into `Importer`. Subclass `ImageImporter` to customize thumbnail generation, preserving its rollback contract. Keep feed mapping in module services. |
| Logging | Inject a module-local `Dartmoon\Log\Log`; subclass `log()` for alternate delivery. Never use a public log directory. |
| Renderers | Inject or subclass `TemplateRenderer`; templates must escape data. `HtmlAttributes` is a stateless escaping utility. |
| TabManager | Inject `MultiLangText`. Override the module's `createTabManager()` lifecycle factory to supply a subclass before the host container is available. |
| Utils | Inject or subclass `MultiLangText`; pass explicit language metadata when the host is unavailable. |

## Facade API

All four facades inherit the same public methods:

```php
$filters = Dartmoon\Filters\Facades\Filter::for($module); // FilterService
Dartmoon\Filters\Facades\Filter::swap($module, $customFilterService); // void
Dartmoon\Filters\Facades\Filter::forget($module); // void
```

`for(object $scope): object` resolves one service for that facade and object.
`swap(object $scope, object $service): void` requires the corresponding service
class or a subclass, otherwise throws `InvalidArgumentException`.
`forget(object $scope): void` removes that binding; the next resolution creates
a default. Config uses the Module's `getLocalPath()/config`, or throws
`LogicException` for a non-Module scope without a binding. Other defaults need
no constructor arguments. WeakMap keys release bindings when scopes are released;
avoid binding services that retain the scope unnecessarily in long-running jobs.

Use the same Module object for a module's calls. Different objects deliberately
have different bindings. Constructor-injected instances need no registry.
These rules also apply when two modules use the toolkit unscoped during development.
Production PHP-Scoper prefixes the toolkit and its registry per application;
module-owned implementations and `::class` references remain compatible.
