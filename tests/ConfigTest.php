<?php

declare(strict_types=1);

namespace EchoFusion\ConfigManager\Tests;

use EchoFusion\ConfigManager\Config;
use EchoFusion\ConfigManager\Exceptions\ConfigException;
use EchoFusion\Contracts\ConfigManager\ConfigInterface;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testImplementsContract(): void
    {
        $this->assertInstanceOf(ConfigInterface::class, new Config());
    }

    public function testEmptyConfig(): void
    {
        $config = new Config();

        $this->assertSame([], $config->all());
        $this->assertFalse($config->has('anything'));
    }

    public function testLaterConfigOverridesEarlier(): void
    {
        $config = new Config(['a' => 1, 'b' => 1], ['b' => 2]);

        $this->assertSame(['a' => 1, 'b' => 2], $config->all());
    }

    public function testAssociativeArraysAreMergedRecursively(): void
    {
        $config = new Config(
            ['database' => ['host' => 'localhost', 'port' => 3306]],
            ['database' => ['host' => 'db']],
        );

        $this->assertSame(['host' => 'db', 'port' => 3306], $config->get('database'));
    }

    public function testListsAreReplacedNotAppended(): void
    {
        $config = new Config(['plugins' => ['a', 'b']], ['plugins' => ['c']]);

        $this->assertSame(['c'], $config->get('plugins'));
    }

    public function testScalarCanBeReplacedByArrayAndViceVersa(): void
    {
        $config = new Config(['a' => 'x', 'b' => ['k' => 1]], ['a' => ['k' => 1], 'b' => 'y']);

        $this->assertSame(['k' => 1], $config->get('a'));
        $this->assertSame('y', $config->get('b'));
    }

    public function testGetWithDotNotation(): void
    {
        $config = new Config(['database' => ['connection' => ['host' => 'db']]]);

        $this->assertSame('db', $config->get('database.connection.host'));
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $config = new Config(['a' => ['b' => 1]]);

        $this->assertNull($config->get('missing'));
        $this->assertSame('fallback', $config->get('a.missing', 'fallback'));
        $this->assertSame('fallback', $config->get('a.b.c', 'fallback'));
    }

    public function testHasIsTrueForNullValues(): void
    {
        $config = new Config(['a' => null, 'b' => ['c' => null]]);

        $this->assertTrue($config->has('a'));
        $this->assertTrue($config->has('b.c'));
        $this->assertNull($config->get('a', 'default'));
        $this->assertFalse($config->has('b.d'));
    }

    public function testLiteralKeyContainingDotWins(): void
    {
        $config = new Config(['a.b' => 'literal', 'a' => ['b' => 'nested']]);

        $this->assertSame('literal', $config->get('a.b'));
    }

    public function testWithReturnsNewInstanceAndLeavesOriginalUntouched(): void
    {
        $original = new Config(['a' => 1]);
        $changed = $original->with(['a' => 2, 'b' => 3]);

        $this->assertNotSame($original, $changed);
        $this->assertSame(['a' => 1], $original->all());
        $this->assertSame(['a' => 2, 'b' => 3], $changed->all());
    }

    public function testFromFilesMergesInOrder(): void
    {
        $config = Config::fromFiles(__DIR__ . '/fixtures/app.php', __DIR__ . '/fixtures/override.php');

        $this->assertSame('Echo', $config->get('app.name'));
        $this->assertTrue($config->get('app.debug'));
        $this->assertSame([3], $config->get('list'));
    }

    public function testFromFilesThrowsForMissingFile(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('does not exist');

        Config::fromFiles(__DIR__ . '/fixtures/missing.php');
    }

    public function testFromFilesThrowsWhenFileDoesNotReturnArray(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('must return an array');

        Config::fromFiles(__DIR__ . '/fixtures/invalid.php');
    }
}
