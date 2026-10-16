# Hooks

For manifest-based CSS/JavaScript injection from header hooks, use the
[Assets library](assets.md). Keep asset resolution in the manager and choose
entries in your hook group.

[Documentation index](../README.md)

Hook groups organize a module's hook handlers into classes. A dispatcher maps
canonical PrestaShop hook names to those handlers; the module trait forwards
`hook...()` calls to the dispatcher.

## Write a hook group

Create `app/Hooks/HomeMessageHook.php`:

```php
<?php

namespace Acme\MyModule\Hooks;

use Dartmoon\Hooks\AbstractHookGroup;

final class HomeMessageHook extends AbstractHookGroup
{
    protected $hooks = ['displayHome'];

    public function displayHome(array $params): string
    {
        return $this->module->fetch(
            'module:' . $this->module->name . '/views/templates/hook/home.tpl'
        );
    }
}
```

Create `views/templates/hook/home.tpl` with the markup to return. The base group
provides protected `$module` and `$context` properties. Its constructor accepts
the host `Module` and `Context`; if overriding it, call the parent constructor.

Use the hook name without the `hook` prefix both in `$hooks` and as the public
method name. Prefer canonical names; the dispatcher does not resolve legacy
aliases into their canonical equivalents.

## Connect it to the boilerplate

In the root `mymodule.php` file, import `HomeMessageHook` and append
`HomeMessageHook::class` to the existing protected `$hooks` array. Keep the
starter asset groups if needed. The generated module already:

1. Uses `Dartmoon\Hooks\Traits\HasHookDispatcher`.
2. Calls `initHookDispatcher()` after `parent::__construct()`.
3. Calls `registerHook($this->getHookDispatcher()->getAvailableHooks())` during
   installation, and checks the result.

Adding a class to the array initializes dispatching, but PrestaShop must also
register any newly added hook for an already installed module. Use a module
upgrade script to call `registerHook('displayHome')`, or reinstall in a
disposable development shop. Building a new ZIP alone does not update the
shop's hook registration records.

PrestaShop calls `$module->hookDisplayHome($params)`; the trait strips `hook`
and dispatches `displayHome` to the group.

## Dispatch directly

For custom integration or testing with a bootstrapped module and context:

```php
use Acme\MyModule\Hooks\HomeMessageHook;
use Dartmoon\Hooks\HookDispatcher;

$dispatcher = new HookDispatcher();
$dispatcher->register(new HomeMessageHook($module, $context));
$names = $dispatcher->getAvailableHooks(); // ['displayHome']
$html = $dispatcher->dispatch('displayHome', []);
```

`getAvailableHooks()` returns names suitable for `Module::registerHook()`.
Direct dispatcher registration does not write to the PrestaShop database.

## Multiple handlers and errors

| Situation | Result |
| --- | --- |
| One handler | Its return value is preserved, including arrays, booleans or null |
| Several handlers returning strings/null | Strings concatenate in registration order; null contributes no output |
| Several handlers all returning null | Returns null |
| Several handlers including a non-string, non-null result | Throws `LogicException` |
| Unknown hook | Returns null, allowing PrestaShop hook probing |
| Invalid name or missing public handler | Registration throws `InvalidArgumentException` |

Lookup is case insensitive. Registering the same group instance twice does not
duplicate its handler; distinct instances are separate handlers. Exceptions
from a handler propagate to its caller. A non-hook method invoked through the
trait raises `BadMethodCallException`.

Use one handler for hooks that return structured data. For media registration,
follow the starter's `actionFrontControllerSetMedia` group and return no output.
