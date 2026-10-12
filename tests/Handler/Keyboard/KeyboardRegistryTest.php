<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler\Keyboard;

use MeRezaRezaei\Teleframe\Handler\Keyboard\KeyboardRegistry;
use MeRezaRezaei\Teleframe\Handler\Keyboard\Menu;
use PHPUnit\Framework\TestCase;

/**
 * Q16 live keyboard table: keyId -> Menu, button route resolution, and the
 * version-byte rotation that unseats an old menu (the "menu expired" path).
 */
final class KeyboardRegistryTest extends TestCase
{
    public function test_register_returns_self_and_counts(): void
    {
        $registry = new KeyboardRegistry();
        $menu = new Menu([[Menu::button('Ok', 'menu:ok')]]);

        self::assertSame($registry, $registry->register($menu));
        self::assertSame(1, $registry->count());
        self::assertTrue($registry->isLive($menu->keyId()));
    }

    public function test_route_of_resolves_a_button_route(): void
    {
        $registry = new KeyboardRegistry();
        $menu = new Menu([
            [Menu::button('Settings', 'menu:settings', 'dark'), Menu::button('Help', 'menu:help')],
        ]);
        $registry->register($menu);

        self::assertSame('menu:settings', $registry->routeOf($menu->keyId(), 0));
        self::assertSame('menu:help', $registry->routeOf($menu->keyId(), 1));
        self::assertNull($registry->routeOf($menu->keyId(), 2));
    }

    public function test_unknown_key_id_is_null(): void
    {
        $registry = new KeyboardRegistry();

        self::assertNull($registry->routeOf('definitely-not-live', 0));
        self::assertFalse($registry->isLive('definitely-not-live'));
    }

    public function test_registering_new_version_rotates_out_the_old_key_id(): void
    {
        $registry = new KeyboardRegistry();
        $rows = [[Menu::button('Ok', 'menu:ok')]];
        $v1 = new Menu($rows, version: 1);
        $v2 = new Menu($rows, version: 2);

        $registry->register($v1);
        self::assertTrue($registry->isLive($v1->keyId()));

        $registry->register($v2);

        self::assertNotSame($v1->keyId(), $v2->keyId());
        self::assertTrue($registry->isLive($v2->keyId()));
        self::assertFalse($registry->isLive($v1->keyId()));
        self::assertNull($registry->routeOf($v1->keyId(), 0));
        self::assertSame('menu:ok', $registry->routeOf($v2->keyId(), 0));
    }

    public function test_re_registering_same_version_is_idempotent(): void
    {
        $registry = new KeyboardRegistry();
        $menu = new Menu([[Menu::button('Ok', 'menu:ok')]]);

        $registry->register($menu);
        $registry->register($menu);

        self::assertSame(1, $registry->count());
        self::assertTrue($registry->isLive($menu->keyId()));
    }

    public function test_all_lists_live_menus(): void
    {
        $registry = new KeyboardRegistry();
        $a = new Menu([[Menu::button('A', 'menu:a')]]);
        $b = new Menu([[Menu::button('B', 'menu:b')]]);

        $registry->register($a)->register($b);

        self::assertSame([$a, $b], $registry->all());
    }
}