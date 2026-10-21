# File logging

[Documentation index](../README.md)

`Dartmoon\Log\Log` writes a daily, module-local file without global
configuration constants. Use PrestaShop's existing logger integration when you
need central back-office logging; this component is for simple job log files.

```php
use Dartmoon\Log\Log;

$logger = new Log('/srv/private/mymodule/logs', Log::DEBUG);
$logger->debug('Import started');
$logger->error('A product could not be saved');
$logger->log('Import finished', Log::INFO, true); // Explicit console output
```

Files are named `log-YYYY-MM-DD.log`. Each timestamped entry contains its level,
uses an exclusive append lock and escapes embedded line breaks. Logging is
quiet unless the third `log()` argument is true.

| Configured level | Written entries |
| --- | --- |
| `0` | None |
| `Log::EMERGENCY` (`3`) | Emergencies only |
| `Log::ALERT` (`4`) | Alerts and more severe entries |
| `Log::CRITICAL` (`5`) | Critical and more severe entries |
| `Log::ERROR` (`1`) | Errors and more severe entries |
| `Log::WARNING` (`6`) | Warnings and more severe entries |
| `Log::NOTICE` (`7`) | Notices and more severe entries |
| `Log::INFO` (`8`) | Informational and more severe entries |
| `Log::DEBUG` (`2`) | All entries (default) |

Each level has a matching method: `emergency()`, `alert()`, `critical()`,
`error()`, `warning()`, `notice()`, `info()` and `debug()`. The existing numeric
values `ERROR = 1` and `DEBUG = 2` are preserved; thresholds follow the severity
order in the table, not numeric order. Prefer the named constants.

Each instance owns its directory and level. A missing directory is created;
filesystem failures raise `RuntimeException`. Invalid levels raise
`InvalidArgumentException`. Choose a private directory and manage retention in
the deployment. This small logger does not implement PSR-3 or rotate old files.

See the [public API reference](../api.md) for parameters, defaults, return types
and declared errors, and [extension points](../extensions.md) for replacement strategies.
