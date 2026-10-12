# ConfigManager

[![CI](https://github.com/echo-fusion/configmanager/actions/workflows/ci.yml/badge.svg)](https://github.com/echo-fusion/configmanager/actions/workflows/ci.yml) [![Packagist](https://img.shields.io/packagist/v/echo-fusion/configmanager.svg)](https://packagist.org/packages/echo-fusion/configmanager) [![PHP](https://img.shields.io/badge/php-8.1%20%7C%208.2%20%7C%208.3-777bb4.svg)](https://www.php.net) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**ConfigManager** is a small, immutable configuration container for PHP applications. It merges any number of config arrays (or PHP config files) and gives you dot-notation access to the result.

It implements `EchoFusion\Contracts\ConfigManager\ConfigInterface` from [echo-fusion/contracts](https://github.com/echo-fusion/contracts), so other packages can depend on the interface only.

> Part of [EchoFusion](https://github.com/echo-fusion), a modular PHP framework built from small packages.

## Installation

```bash
composer require echo-fusion/configmanager
```

## Requirements

* PHP 8.1, 8.2 or 8.3

## Usage

### Merging arrays

Later arrays override earlier ones. Associative arrays are merged recursively; lists are replaced as a whole.

```php
use EchoFusion\ConfigManager\Config;

$config = new Config(
    ['database' => ['host' => 'localhost', 'port' => 3306], 'plugins' => ['a', 'b']],
    ['database' => ['host' => 'db'], 'plugins' => ['c']],
);

$config->get('database.host');          // 'db'
$config->get('database.port');          // 3306
$config->get('plugins');                // ['c']
$config->get('database.user', 'root');  // 'root' (default for a missing key)
$config->has('database.port');          // true
$config->all();                         // the full merged array
```

`has()` is `true` for keys whose value is `null`; `get()` only returns the default when the key does not exist.

### Loading PHP files

Each file must return an array. A missing file, or a file that returns something else, throws `EchoFusion\ConfigManager\Exceptions\ConfigException`.

```php
$config = Config::fromFiles(
    __DIR__ . '/config/global.config.php',
    __DIR__ . '/config/environments/production.config.php',
);
```

### Immutability

The object never changes after construction. `with()` returns a new instance:

```php
$debug = $config->with(['app' => ['debug' => true]]);
```

### Depending on the interface

```php
use EchoFusion\Contracts\ConfigManager\ConfigInterface;

final class Mailer
{
    public function __construct(private ConfigInterface $config) {}
}
```

## Testing

```bash
composer test
```

## Credits

- [Amir Shadanfar](https://github.com/amir-shadanfar)

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
