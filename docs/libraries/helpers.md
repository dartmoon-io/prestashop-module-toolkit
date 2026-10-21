# Legacy admin helpers

[Documentation index](../README.md)

The helpers extend PrestaShop's current legacy classes. Core code still renders
forms/lists and performs CRUD operations. New Symfony pages can keep using
`PrestaShopAdminController`, as in the boilerplate's controller example.

## Forms

```php
use Dartmoon\Helpers\HelperForm;

$helper = new HelperForm();
// Set the normal PrestaShop helper options: module, currentIndex, token,
// languages, default_form_language, fields_value, and table as appropriate.
$helper->fields_value = ['amount' => 0, 'position' => '0 0 0'];
return $helper->generateForm([['form' => ['input' => [
    ['type' => 'number', 'name' => 'amount', 'label' => 'Amount',
     'attributes' => ['min' => 0, 'step' => '0.01']],
    ['type' => 'vector3', 'name' => 'position', 'label' => 'Position'],
    ['type' => 'separator', 'name' => 'section', 'content' => 'Extra settings'],
]]]]);
```

Native fields pass through. Custom number/vector3 fields and escaped headings
(`separator`, `tab-description`) become native HTML fields. `condition: false`
removes an input. Supply custom attributes as arrays; field identity/type/value
cannot be replaced through that array. Do not supply raw attribute strings.

Vector3 uses three numeric controls and one submitted `x y z` value. Add the
small dependency-free browser helper to your module's `_dev/js/back.js`:

```js
import {initForms} from '../../vendor/dartmoon/prestashop-module-toolkit/resources/js/forms.js';

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => initForms());
} else {
  initForms();
}
```

Run Vite after installing Composer dependencies. The helper synchronizes the
hidden value as coordinates change and supports dynamically inserted fields.
Validate submitted values on the server using your ObjectModel/form rules.

## Lists

Use `Dartmoon\Helpers\HelperList` like native `HelperList`. Computed columns
with callbacks receive a `null` value when their field is absent from a row,
so the native renderer invokes the callback. Real values, including zero, are
preserved. `HelperList::prepareRows($rows, $fields)` applies this normalization
when using a core-created helper.

## ObjectModel controller base

```php
use Dartmoon\Controllers\Admin\AbstractAdminController;

class AdminMymoduleItemsController extends AbstractAdminController
{
    protected function getConfigFormDefinition()
    {
        return [['form' => ['input' => [
            ['type' => 'text', 'name' => 'title', 'label' => 'Title', 'lang' => true],
        ]]]];
    }
}
```

Declare the usual native controller configuration (`module`, `table`,
`className`, identifier and permissions) and a matching ObjectModel. The base
prepares field definitions and delegates form rendering and CRUD to PrestaShop.
Override `initFormToolbar()` when needed. Submitted fields are limited to the
declared, enabled form inputs and ObjectModel fields. Multilingual values and
native password hashing are supported.

`createListLink()` renders an escaped icon/action link from `href`, `action`,
`icon`, optional `class` and `target`. Implement custom actions with explicit
permissions and POST/token checks; the toolkit does not automatically dispatch
arbitrary request names to methods.

## Product lookup

`ProductSearch::search($query, $limit = 20, $offset = 0)` returns `id`/`text`
rows in the current shop/language. Limits are 1–100 and wildcard characters are
treated literally. `find($id)` returns one such row or `null`.

The controller base exposes native AJAX actions `SearchProductByName` and
`GetProductById`, guarded by the controller's view access. Generate the AJAX URL
through `getAdminLink()` with the native token and `ajax=1`; read JSON `items`
or `item`. Provide your own search UI. Preview, dropzone and product-select widgets belong in application code.

See the [PrestaShop helper documentation](https://devdocs.prestashop-project.org/9/development/components/helpers/)
for standard helper/controller configuration.

See the [public API reference](../api.md) for parameters, defaults, return types
and declared errors, and [extension points](../extensions.md) for replacement strategies.
