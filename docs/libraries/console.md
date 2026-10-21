# Console

[Documentation index](../README.md)

`Dartmoon\Console\AbstractCommand` extends Symfony's `Command` and supports
`$name` and `$description` properties. Dependencies come from constructor
injection. Run these commands through the shop's `bin/console` after installing
the module; the toolkit relies on the shop's Symfony Console runtime.

## Create a command

Create `app/Commands/ShowSettingsCommand.php` in a generated module:

```php
<?php
namespace Acme\MyModule\Commands;

use Dartmoon\Config\Config;
use Dartmoon\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ShowSettingsCommand extends AbstractCommand
{
    protected $name = 'mymodule:settings';
    protected $description = 'Show the configured API timeout.';

    public function __construct(private Config $config)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timeout = (int) $this->config->get('api.http.timeout', 30);
        $output->writeln('Timeout: ' . $timeout);

        return self::SUCCESS;
    }
}
```

Use a command prefix unique to the module. A custom constructor must call the
parent constructor so Symfony configures the command. Keep the inherited
`$name` and `$description` properties untyped when overriding them.

## Register dependencies

The boilerplate already loads `app/Commands/` with autoconfiguration. Add an
explicit argument for the Config repository, using this `config/services.php`
for the example above:

```php
<?php

use Acme\MyModule\Commands\ShowSettingsCommand;
use Dartmoon\Config\Config;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()->defaults()->autowire()->autoconfigure();
    $services->set('mymodule.config', Config::class)->args([__DIR__]);
    $services->load('Acme\\MyModule\\Commands\\', '../app/Commands/')
        ->exclude('../app/Commands/**/index.php');
    $services->set(ShowSettingsCommand::class)
        ->args([service('mymodule.config')]);
};
```

With autoconfiguration enabled, Symfony discovers the command. If registering
it without autoconfiguration, add the `console.command` tag explicitly. Keep
directory guard files excluded from service resource discovery.

## Arguments, options and results

Override `configure(): void` to define input arguments/options. Start that
method with `parent::configure()` to retain the property-based name and
description, then use Symfony's `addArgument()` and `addOption()` methods.
Read inputs from `InputInterface` inside `execute()`.

Return `self::SUCCESS` for success, `self::FAILURE` for an operation failure,
or `self::INVALID` for invalid input. Avoid printing secrets or depending on
an HTTP request. Replace legacy `getContainer()` calls with injected services.

## Build and execute

From the module root, build and install the resulting artifact in the shop.
From the shop root:

```sh
php -d memory_limit=1G bin/console mymodule:settings --env=prod --no-debug
```

The starter module also provides `mymodule:hello`. If a command is missing,
check that the module is installed, the service namespace matches its Composer
autoload mapping, and the shop container has been rebuilt after service changes.

For a unit test, construct the command with its dependencies and execute it
using Symfony's `CommandTester`. The toolkit repository's development
dependencies include Symfony Console for this purpose.

See the [public API reference](../api.md) for parameters, defaults, return types
and declared errors, and [extension points](../extensions.md) for replacement strategies.
