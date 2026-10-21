# Testing

[Documentation index](README.md)

Run `composer verify` for official PrestaShop PHP formatting and PHPUnit, and
`node --test tests/forms.test.mjs` for vector field synchronization. Run
`composer quality:fix` to apply formatting.

For an existing local test shop, inspect cleanup first, then run:

```sh
php -d memory_limit=1G tests/integration/prestashop.php /path/to/shop
```

The integration test creates randomly named categories, products, features,
attributes, combinations, images and one temporary module template. It checks
native forms/lists, Smarty isolation, combination image links, failed image
writes, exact thumbnail deletion and form-field whitelisting. Its finally block
removes only its recorded fixtures and restores the context. It does not reset
the database. Back up valuable test data before running integration tests.

FTP unit tests use function doubles and make no external connection. They are
not evidence of real FTP network behavior. The boilerplate separately tests
production packaging, autoload files and two isolated modules in one process.

`composer.json` requires PHP 8.1 for runtime; PHP 8.1 must still be exercised in
CI. A successful local PHP 8.5 run does not establish cross-version compatibility.
