# Config

[Documentation index](../README.md)

`Dartmoon\Config\Config` reads PHP files that return arrays. Use it for module
defaults and application settings stored in source. Use PrestaShop's
`Configuration` API for values that merchants edit and persist in the database.

## Create and read a configuration file

Create `config/api.php` inside your module:

```php
<?php

return [
    'endpoint' => 'https://example.com/api',
    'http' => ['timeout' => 10],
    'optional' => null,
    'enabled' => false,
];
```

From the module entry file, construct a repository with the module's own path:

```php
use Dartmoon\Config\Config;

$config = new Config(__DIR__ . '/config');
$timeout = $config->get('api.http.timeout', 30); // 10
$endpoint = $config->get('api.endpoint');
$all = $config->get('api'); // The entire array
$missing = $config->get('api.missing', 'fallback'); // 'fallback'
$optional = $config->get('api.optional', 'fallback'); // null
$enabled = $config->get('api.enabled', true); // false
```

The first path segment is the filename without `.php`; later segments traverse
nested arrays. The default argument is `false`. A missing file returns an empty
array when requested as a whole, or the supplied default for a nested value.
Explicit `null` and `false` values do not trigger the fallback.

Pass an absolute directory derived from the module location. Avoid paths based
on the current working directory or the depth of a vendor installation.

## Inject the repository into a service

The generated `config/services.php` defines a service with the ID
`mymodule.config`. To inject it into a service, add a definition using the
existing `$services` configurator:

```php
use Acme\MyModule\Services\ApiSettings;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

$services->set(ApiSettings::class)
    ->args([service('mymodule.config')]);
```

Create `app/Services/ApiSettings.php`:

```php
<?php

namespace Acme\MyModule\Services;

use Dartmoon\Config\Config;

final class ApiSettings
{
    public function __construct(private Config $config)
    {
    }

    public function timeout(): int
    {
        return (int) $this->config->get('api.http.timeout', 30);
    }
}
```

Place the imports at the top of `config/services.php` and the service definition
inside its existing closure. Inject that service into commands or controllers
through the same Symfony container. In module methods and hook groups, create
or pass a repository using the module's local path.

## Compatibility facade

If migrating code that uses the static facade, initialize it explicitly:

```php
use Dartmoon\Config\Config as Repository;
use Dartmoon\Config\Facades\Config;

Config::setRepository(new Repository(__DIR__ . '/config'));
$timeout = Config::get('api.http.timeout', 30);
Config::setRepository(null); // Reset when needed, for example in tests.
```

Calls before initialization throw `LogicException`. Prefer instance injection
when multiple unscoped modules share a process: the facade holds static state.

## API and lifecycle

| Method | Behavior |
| --- | --- |
| `get($path, $default = false)` | Read a file or nested dot path |
| `getConfig($path, $default = false)` | Compatibility alias for the same lookup |
| `getConfigFile($fileName)` | Load a complete array by filename without extension |
| `getConfigValue($array, $key, $default = false)` | Read a dot path from an existing array |

Each repository caches loaded files, including missing ones. Create a new
instance to observe file changes in a long-running process. File names accept
letters, digits, underscores and hyphens; directory traversal raises
`InvalidArgumentException`. A file returning a non-array raises
`UnexpectedValueException`. This library does not write configuration files.
