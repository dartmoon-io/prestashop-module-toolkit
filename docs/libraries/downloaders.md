# Downloaders

[Documentation index](../README.md)

`Dartmoon\Downloaders\FTPDownloader` downloads a file through PHP's ext-ftp.
It writes a temporary file alongside the destination and renames it only after
the transfer succeeds. Failed downloads preserve an existing destination and
close the connection. Exceptions do not include credentials.

```php
use Dartmoon\Downloaders\FTPDownloader;

$file = (new FTPDownloader())->download('/exports/catalog.csv', '/srv/private/catalog.csv', [
    'host' => $host,
    'user' => $username,
    'password' => $password,
    'port' => 21,
    'timeout' => 30,
    'passive' => true,
    'ssl' => false,
]);
```

Only `host`, `user` and `password` are required. Keep credentials in merchant
configuration or deployment secrets. `ssl: true` selects PHP's explicit FTPS
transport when supported; it is not SFTP. Follow the
[PHP FTP SSL documentation](https://www.php.net/manual/en/function.ftp-ssl-connect.php)
for its certificate-verification limitations.

The destination directory is created if needed. Downloaded files keep the
temporary file's owner-only permissions. The path is returned on success;
invalid options raise `InvalidArgumentException`, unavailable extensions raise
`LogicException`, and connection/transfer/filesystem failures raise
`RuntimeException`.

Implement `AbstractDownloader::download(string $srcFile, string $dstFile,
array $credentials = []): string` for another transport. Store feeds outside
publicly served directories when they contain private data.

See the [public API reference](../api.md) for parameters, defaults, return types
and declared errors, and [extension points](../extensions.md) for replacement strategies.
