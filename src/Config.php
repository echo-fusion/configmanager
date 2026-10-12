<?php

declare(strict_types=1);

namespace EchoFusion\ConfigManager;

use EchoFusion\ConfigManager\Exceptions\ConfigException;
use EchoFusion\Contracts\ConfigManager\ConfigInterface;
use function array_is_list;
use function array_key_exists;
use function explode;
use function is_array;
use function is_file;
use function sprintf;

/**
 * Immutable configuration built from one or more arrays.
 *
 * Later arrays override earlier ones. Associative arrays are merged
 * recursively; lists (and scalars) are replaced as a whole.
 */
final class Config implements ConfigInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $items;

    /**
     * @param array<string, mixed> ...$configs
     */
    public function __construct(array ...$configs)
    {
        $items = [];
        foreach ($configs as $config) {
            $items = self::merge($items, $config);
        }
        $this->items = $items;
    }

    /**
     * Build the configuration from PHP files that each return an array.
     *
     * @throws ConfigException if a file is missing or does not return an array
     */
    public static function fromFiles(string ...$paths): self
    {
        $configs = [];
        foreach ($paths as $path) {
            if (!is_file($path)) {
                throw new ConfigException(sprintf('Config file "%s" does not exist.', $path));
            }

            $config = (static fn (string $file): mixed => require $file)($path);
            if (!is_array($config)) {
                throw new ConfigException(sprintf('Config file "%s" must return an array.', $path));
            }

            $configs[] = $config;
        }

        return new self(...$configs);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $found = $this->lookup($key);

        return $found === null ? $default : $found[0];
    }

    public function has(string $key): bool
    {
        return $this->lookup($key) !== null;
    }

    public function all(): array
    {
        return $this->items;
    }

    /**
     * Return a new instance with the given configuration merged on top.
     *
     * @param array<string, mixed> $config
     */
    public function with(array $config): self
    {
        return new self($this->items, $config);
    }

    /**
     * @return array{0: mixed}|null the value wrapped in an array, or null when the key is missing
     */
    private function lookup(string $key): ?array
    {
        if (array_key_exists($key, $this->items)) {
            return [$this->items[$key]];
        }

        $value = $this->items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return [$value];
    }

    /**
     * @param array<array-key, mixed> $base
     * @param array<array-key, mixed> $override
     *
     * @return array<array-key, mixed>
     */
    private static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (
                is_array($value)
                && isset($base[$key])
                && is_array($base[$key])
                && !array_is_list($value)
                && !array_is_list($base[$key])
            ) {
                $base[$key] = self::merge($base[$key], $value);

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}
