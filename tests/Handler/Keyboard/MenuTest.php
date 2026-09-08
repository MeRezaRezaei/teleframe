<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler\Keyboard;

use MeRezaRezaei\Teleframe\Handler\Keyboard\CallbackData;
use MeRezaRezaei\Teleframe\Handler\Keyboard\Menu;
use PHPUnit\Framework\TestCase;

/**
 * Q16 menu value object: immutable rows, deterministic content-hash id across
 * deploys, the version-byte rotation lever, and render() emitting the exact
 * Bot API InlineKeyboardMarkup array with chat/message-bound tokens.
 */
final class MenuTest extends TestCase
{
    private const SECRET = 'test-hmac-secret';

    public function test_id_is_deterministic_and_content_scoped(): void
    {
        $a = new Menu([[Menu::button('Ok', 'menu:ok', 'yes')]]);
        $b = new Menu([[Menu::button('Ok', 'menu:ok', 'yes')]]);
        $c = new Menu([[Menu::button('Ok', 'menu:ok', 'no')]]);

        self::assertSame($a->id(), $b->id());
        self::assertNotSame($a->id(), $c->id());
    }

    public function test_version_changes_key_id_but_not_content_id(): void
    {
        $v1 = new Menu([[Menu::button('Ok', 'menu:ok')]], version: 1);
        $v2 = new Menu([[Menu::button('Ok', 'menu:ok')]], version: 2);

        self::assertSame($v1->id(), $v2->id());
        self::assertNotSame($v1->keyId(), $v2->keyId());
        self::assertSame(1, $v1->version());
        self::assertSame(2, $v2->version());
        self::assertSame(9, strlen($v1->keyId()));
    }

    public function test_buttons_flatten_row_major(): void
    {
        $menu = new Menu([
            [Menu::button('A', 'menu:a'), Menu::button('B', 'menu:b')],
            [Menu::button('C', 'menu:c', 'arg')],
        ]);

        self::assertSame(3, $menu->count());
        self::assertSame('menu:b', $menu->buttonAt(1)['route']);
        self::assertSame('arg', $menu->buttonAt(2)['arg']);
        self::assertNull($menu->buttonAt(3));
    }

    public function test_render_emits_bot_api_inline_keyboard_shape(): void
    {
        $menu = new Menu([
            [Menu::button('Settings', 'menu:settings', 'dark'), Menu::button('Help', 'menu:help')],
            [Menu::button('Page 2', 'menu:page', '2')],
        ]);

        $payload = $menu->render(self::SECRET, 421, 7);

        self::assertArrayHasKey('inline_keyboard', $payload);
        self::assertCount(2, $payload['inline_keyboard']);
        self::assertCount(2, $payload['inline_keyboard'][0]);
        self::assertCount(1, $payload['inline_keyboard'][1]);

        $first = $payload['inline_keyboard'][0][0];
        self::assertSame('Settings', $first['text']);
        self::assertIsString($first['callback_data']);

        $decoded = CallbackData::decode($first['callback_data'], 7, 421, self::SECRET);
        self::assertSame(['route' => $menu->keyId() . ':0', 'arg' => 'dark'], $decoded);

        $third = $payload['inline_keyboard'][1][0];
        self::assertSame('2', CallbackData::decode($third['callback_data'], 7, 421, self::SECRET)['arg']);
    }

    public function test_render_binds_chat_and_optional_message(): void
    {
        $menu = new Menu([[Menu::button('Ok', 'menu:ok')]]);
        $token = $menu->render(self::SECRET, 421, 7)['inline_keyboard'][0][0]['callback_data'];

        self::assertNotNull(CallbackData::decode($token, 7, 421, self::SECRET));
        self::assertNull(CallbackData::decode($token, 8, 421, self::SECRET));

        $preSend = $menu->render(self::SECRET, 421)['inline_keyboard'][0][0]['callback_data'];
        self::assertNotNull(CallbackData::decode($preSend, null, 421, self::SECRET));
    }

    public function test_render_without_arg_signs_empty_arg(): void
    {
        $menu = new Menu([[Menu::button('Ok', 'menu:ok')]]);
        $token = $menu->render(self::SECRET, 421, 7)['inline_keyboard'][0][0]['callback_data'];

        self::assertSame('', CallbackData::decode($token, 7, 421, self::SECRET)['arg']);
    }

    public function test_empty_row_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Menu([[]]);
    }

    public function test_missing_route_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        /** @var list<array{text: string, route: string}> $rows */
        $rows = [[['text' => 'Ok', 'route' => '']]];
        new Menu($rows);
    }

    public function test_non_byte_version_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Menu([[Menu::button('Ok', 'menu:ok')]], version: 256);
    }
}