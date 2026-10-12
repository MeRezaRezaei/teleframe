<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use DateInterval;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use PHPUnit\Framework\TestCase;

final class InMemoryCacheTest extends TestCase
{
    private InMemoryCache $cache;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCache;
    }

    public function test_set_and_get_round_trip(): void
    {
        self::assertTrue($this->cache->set('foo', 'bar'));
        self::assertSame('bar', $this->cache->get('foo'));
    }

    public function test_values_survive_round_trip_for_all_scalar_types(): void
    {
        $values = [
            'string' => 'text',
            'int' => 42,
            'float' => 42.5,
            'bool_false' => false,
            'null' => null,
            'array' => ['a' => 1],
        ];

        foreach ($values as $key => $value) {
            $this->cache->set($key, $value);
            self::assertSame($value, $this->cache->get($key));
        }
    }

    public function test_object_value_survives_round_trip(): void
    {
        $object = new DateInterval('PT1S');

        $this->cache->set('object', $object);

        self::assertSame($object, $this->cache->get('object'));
    }

    public function test_missing_key_returns_default(): void
    {
        self::assertSame('fallback', $this->cache->get('missing', 'fallback'));
    }

    public function test_missing_key_returns_null_when_no_default_given(): void
    {
        self::assertNull($this->cache->get('missing'));
    }

    public function test_set_overwrites_existing_value(): void
    {
        $this->cache->set('key', 'first');
        $this->cache->set('key', 'second');

        self::assertSame('second', $this->cache->get('key'));
    }

    public function test_has_reflects_present_key(): void
    {
        $this->cache->set('key', 'value');

        self::assertTrue($this->cache->has('key'));
        self::assertFalse($this->cache->has('missing'));
    }

    public function test_has_is_true_for_null_value(): void
    {
        $this->cache->set('key', null);

        self::assertTrue($this->cache->has('key'));
        self::assertNull($this->cache->get('key'));
    }

    public function test_has_is_true_for_false_value(): void
    {
        $this->cache->set('key', false);

        self::assertTrue($this->cache->has('key'));
    }

    public function test_delete_removes_key(): void
    {
        $this->cache->set('key', 'value');

        self::assertTrue($this->cache->delete('key'));
        self::assertFalse($this->cache->has('key'));
        self::assertNull($this->cache->get('key'));
    }

    public function test_delete_on_missing_key_still_returns_true(): void
    {
        self::assertTrue($this->cache->delete('missing'));
    }

    public function test_clear_empties_all_values(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);

        self::assertTrue($this->cache->clear());
        self::assertFalse($this->cache->has('a'));
        self::assertFalse($this->cache->has('b'));
    }

    public function test_get_multiple_returns_values_for_requested_keys(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);

        self::assertSame(
            ['a' => 1, 'b' => 2, 'missing' => null],
            $this->cache->getMultiple(['a', 'b', 'missing']),
        );
    }

    public function test_get_multiple_uses_default_for_missing_keys(): void
    {
        $this->cache->set('a', 1);

        self::assertSame(
            ['a' => 1, 'missing' => 'fallback'],
            $this->cache->getMultiple(['a', 'missing'], 'fallback'),
        );
    }

    public function test_set_multiple_stores_all_values(): void
    {
        self::assertTrue($this->cache->setMultiple(['a' => 1, 'b' => 2]));

        self::assertSame(1, $this->cache->get('a'));
        self::assertSame(2, $this->cache->get('b'));
    }

    public function test_set_multiple_overwrites_existing_values(): void
    {
        $this->cache->set('a', 'old');

        $this->cache->setMultiple(['a' => 'new', 'b' => 2]);

        self::assertSame('new', $this->cache->get('a'));
        self::assertSame(2, $this->cache->get('b'));
    }

    public function test_delete_multiple_removes_requested_keys_only(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);
        $this->cache->set('c', 3);

        self::assertTrue($this->cache->deleteMultiple(['a', 'c']));
        self::assertFalse($this->cache->has('a'));
        self::assertTrue($this->cache->has('b'));
        self::assertFalse($this->cache->has('c'));
    }

    public function test_set_with_int_ttl_honours_a_window_still_live(): void
    {
        self::assertTrue($this->cache->set('key', 'value', 60));
        self::assertSame('value', $this->cache->get('key'));
        self::assertTrue($this->cache->has('key'));
    }

    public function test_set_with_date_interval_ttl_honours_a_window_still_live(): void
    {
        $ttl = new DateInterval('PT60S');

        self::assertTrue($this->cache->set('key', 'value', $ttl));
        self::assertSame('value', $this->cache->get('key'));
    }

    public function test_set_multiple_with_ttl_honours_a_window_still_live(): void
    {
        self::assertTrue($this->cache->setMultiple(['a' => 1, 'b' => 2], 60));
        self::assertSame(1, $this->cache->get('a'));
        self::assertSame(2, $this->cache->get('b'));
    }
}
