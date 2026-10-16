# TabManager

[Documentation index](../README.md)

`Dartmoon\TabManager\TabManager` creates back-office menu tabs using the host
`Tab` ObjectModel API. It needs a bootstrapped shop, database and module instance.
It does not require an internal Doctrine repository or Symfony container.

## Define tabs in the boilerplate

Edit the existing `tabs` entry in `config/module.php`:

```php
<?php

// Merge this entry into the existing module metadata array.
return [
    'tabs' => [
        [
            'class_name' => 'AdminMyModule',
            'name' => ['en' => 'My module', 'it' => 'Il mio modulo'],
            'icon' => 'settings',
        ],
        [
            'class_name' => 'AdminMyModuleSettings',
            'name' => 'Settings',
            'parent_class_name' => 'AdminMyModule',
            'route_name' => 'my_module_settings',
            'visible' => true,
        ],
    ],
];
```

Keep the other metadata keys, including name, version and author. The starter
module already passes this array and its own module instance to `install()` and
uses `uninstallForModule()` during cleanup. Define parents before their children.
The referenced controller or Symfony route must be implemented separately;
creating a tab does not generate its page.

| Field | Meaning and default |
| --- | --- |
| `class_name` | Required identifier, up to 64 characters, starting with a letter; remaining characters may include digits/underscores |
| `name` | A string or translation array; defaults to class_name |
| `parent_class_name` | Existing or previously created parent; empty means root ID 0 |
| `route_name` | Symfony route name; defaults to an empty string |
| `icon` | Back-office icon identifier; defaults to an empty string |
| `visible` | Sets the tab's active flag; defaults to true |
| `wording` | Optional translation wording; defaults to null |
| `wording_domain` | Optional wording translation domain; defaults to null |

Choose identifiers unique to the module. Use TabManager or PrestaShop's native
`Module::$tabs` for a particular tab; registering through both duplicates work.

## Use the manager directly

Given the current `$module` instance and an array of definitions:

```php
use Dartmoon\TabManager\TabManager;

$manager = new TabManager();
if (!$manager->install($definitions, $module)) {
    throw new RuntimeException('Could not install module tabs.');
}
```

Pass the module so ownership is recorded and uninstall can find its tabs. The
optional null module argument is available for compatibility, but creates tabs
without module ownership.

Repeated installation skips existing tabs owned by that module; it does not
update their names, routes or hierarchy. A conflicting owner, invalid class name
or missing parent raises `InvalidArgumentException`. A failed save returns
false after rollback. Newly created tabs are also rolled back if installation
throws. Rollback attempts all created tabs and raises `RuntimeException` if
cleanup fails. Existing tabs are preserved.

## Remove tabs and evolve definitions

```php
$success = $manager->uninstallForModule($module);
// Or remove an explicitly selected set of class_name definitions:
$success = $manager->uninstall($definitions);
```

`uninstallForModule()` removes owned children before parents and reports false
if a deletion returns false. `uninstall()` processes definitions in reverse
order, so use the same parent-first ordering as installation. Missing tabs
count as already removed. Explicit uninstall works by class name; pass only
definitions that your module owns.

For an existing installation, add new tabs or modify existing tabs in a module
upgrade script. `install()` alone does not reconcile changed definitions.
Normal `Tab::save()`/`delete()` behavior supplies the shop's ACL and cache
handling. Menu visibility does not replace controller permission checks.

For legacy static calls, `Dartmoon\TabManager\Facades\TabManager` forwards calls
to a new manager instance. The instance API makes dependencies clearer.
