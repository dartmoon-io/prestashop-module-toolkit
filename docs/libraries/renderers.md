# Rendering utilities

[Documentation index](../README.md)

## Module templates

`Dartmoon\Renderers\TemplateRenderer` resolves Smarty templates through the
module resource and assigns data to that template instance.

```php
use Dartmoon\Renderers\TemplateRenderer;

$renderer = new TemplateRenderer($module);
return $renderer->render('front/card.tpl', ['title' => 'My product']);
```

The example resolves `views/templates/front/card.tpl` inside the module.
`module_path` and `module_path_uri` are available by default from the module's
public path APIs, including shop subdirectories. Data does not pollute the
shared Smarty context. Use a relative `.tpl` path with letters, digits,
underscores, hyphens and directories; traversal and absolute paths are rejected.

Templates remain responsible for escaping their values:

```smarty
<h3>{$title|escape:'html':'UTF-8'}</h3>
```

## HTML attributes

```php
use Dartmoon\Renderers\HtmlAttributes;

$attributes = HtmlAttributes::render([
    'class' => 'form-control',
    'data-label' => 'A "quoted" value',
    'disabled' => true,
    'hidden' => false,
]);
```

Values are escaped for HTML attributes. `true` emits a presence attribute;
`false` and `null` are omitted. Use string `"false"` for a literal data/ARIA
attribute value. Arrays and invalid/event-handler attribute names are rejected.
Attribute escaping does not validate URL schemes or sanitize arbitrary HTML;
construct links from trusted application URLs.

Campaign-specific rendering remains in the application. These utilities remove
hardcoded module paths and unescaped attribute concatenation without bringing
campaign models or viewer configuration into the toolkit.

See the [public API reference](../api.md) for parameters, defaults, return types
and declared errors, and [extension points](../extensions.md) for replacement strategies.
