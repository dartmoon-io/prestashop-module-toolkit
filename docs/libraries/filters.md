# Filters

[Documentation index](../README.md)

`Dartmoon\Filters\FilterService` applies transformations in order. Each instance
has its own registry, so a custom filter in one module does not change another.

```php
use Dartmoon\Filters\FilterService;

$filters = new FilterService();
$title = $filters->filter(' <red> chair ', 'clean|title|append:!'); // Red Chair!
$slug = $filters->filter('Caffè chair', 'slugify'); // caffe-chair
$suffix = $filters->filter('value', ['append' => ['a|b:c']]);
```

| Filter | Behavior |
| --- | --- |
| `append:suffix` | Append one parameter, preserving colons in the suffix |
| `clean` | Trim and remove `< > ; = # { }`; preserve `0` |
| `slugify` | Generate an ASCII slug; optional iconv transliteration; `n-a` fallback |
| `title` | UTF-8 title case using ext-mbstring |

Use named parameter arrays for literal pipes or multiple custom parameters.
An empty pipeline returns the original value. Unknown filters, missing append
parameters and invalid UTF-8 in clean/slugify raise `InvalidArgumentException`.
`clean` is a catalog-text transformation, not an HTML sanitizer or SQL escaping
function. Keep normal template escaping and ObjectModel validation.

## Register a filter

```php
use Dartmoon\Filters\Filters\AbstractFilter;

class Uppercase extends AbstractFilter
{
    protected $name = 'uppercase';

    public function filter(mixed $value, array $params): string
    {
        return mb_strtoupper((string) $value, 'UTF-8');
    }
}

$filters->registerFilter(new Uppercase());
$result = $filters->filter('hello', 'uppercase');
```

The constructor also accepts an iterable of custom filters after registering
the defaults. Registering the same name replaces that filter on this service.
Inject the service into import jobs. The module example registers its own
`Greeting` filter with `FilterService` in `config/services.php` and injects that
service into `ExampleCommand`. For scoped access use
`Filter::swap($module, $filters)` then `Filter::for($module)->filter(...)`.
See [extension points](../extensions.md).

See the [public API reference](../api.md) for parameters, defaults, return types
and declared errors, and [extension points](../extensions.md) for replacement strategies.
