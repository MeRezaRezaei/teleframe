<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\Schema;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\Schema\TelegramMethod;
use PHPUnit\Framework\TestCase;

/**
 * W-35: direct units for TelegramMethod — the immutable value object behind
 * both artifact catalogs (methods-mtproto.json / methods-botapi.json).
 * Covers the mtproto flag-bit mapping, the bot-http required/returns path,
 * the empty-default fallbacks, and the unknown-api guard.
 */
final class TelegramMethodTest extends TestCase
{
    public function test_from_artifact_maps_mtproto_entry(): void
    {
        $method = TelegramMethod::fromArtifact('auth.checkPassword', [
            'api' => 'mtproto',
            'id' => '0xd18b4d16',
            'return' => 'auth.Authorization',
            'params' => [
                ['name' => 'password', 'type' => 'InputCheckPasswordSRP', 'description' => 'SRP proof'],
            ],
            'docs' => 'https://core.telegram.org/...',
            'description' => 'Check 2FA password',
            'errors' => ['PASSWORD_HASH_INVALID'],
        ]);

        self::assertSame('auth.checkPassword', $method->name);
        self::assertSame('mtproto', $method->api);
        self::assertSame('0xd18b4d16', $method->id);
        self::assertSame('auth.Authorization', $method->returnType);
        self::assertSame('password', $method->params[0]['name']);
        self::assertSame('InputCheckPasswordSRP', $method->params[0]['type']);
        self::assertNull($method->params[0]['flag_word']);
        self::assertNull($method->params[0]['bit']);
        self::assertNull($method->params[0]['required']);
        self::assertSame('SRP proof', $method->params[0]['description']);
        self::assertSame([], $method->required); // mtproto: no bot-http required list
        self::assertSame([], $method->returns);
        self::assertSame(['PASSWORD_HASH_INVALID'], $method->errors);
    }

    public function test_from_artifact_maps_flag_params_with_word_and_bit(): void
    {
        $method = TelegramMethod::fromArtifact('messages.sendMessage', [
            'api' => 'mtproto',
            'id' => '0x28c61a',
            'return' => 'Updates',
            'params' => [
                ['name' => 'no_webpage', 'type' => 'flags.0?true', 'flag_word' => 'flags', 'bit' => 0, 'description' => ''],
                ['name' => 'message', 'type' => 'string', 'description' => 'text'],
            ],
        ]);

        self::assertSame('flags', $method->params[0]['flag_word']);
        self::assertSame(0, $method->params[0]['bit']);
        self::assertNull($method->params[1]['flag_word']);
        self::assertNull($method->params[1]['bit']);
    }

    public function test_from_artifact_maps_bot_http_entry_with_required_and_returns(): void
    {
        $method = TelegramMethod::fromArtifact('getMe', [
            'api' => 'bot-http',
            'returns' => ['User', 'Me'],
            'params' => [
                ['name' => 'chat_id', 'type' => 'integer', 'required' => true, 'description' => 'target'],
            ],
            'docs' => 'https://core.telegram.org/bots/api#getme',
            'description' => 'A simple method...',
            'errors' => [],
            'required' => ['chat_id'],
        ]);

        self::assertSame('bot-http', $method->api);
        self::assertSame('', $method->id); // bot-http carries no crc id
        self::assertSame('User|Me', $method->returnType); // returns joined
        self::assertTrue($method->params[0]['required']);
        self::assertNull($method->params[0]['flag_word']);
        self::assertNull($method->params[0]['bit']);
        self::assertSame(['chat_id'], $method->required);
        self::assertSame(['User', 'Me'], $method->returns);
    }

    public function test_from_artifact_falls_back_to_empty_defaults(): void
    {
        $method = TelegramMethod::fromArtifact('bare', ['api' => 'mtproto']);

        self::assertSame('', $method->id);
        self::assertSame('', $method->returnType);
        self::assertSame([], $method->params);
        self::assertSame([], $method->errors);
        self::assertSame([], $method->required);
        self::assertSame([], $method->returns);
        self::assertSame('', $method->docs);
        self::assertSame('', $method->description);
    }

    public function test_from_artifact_rejects_unknown_api(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Method [broken] has unknown api [http].');

        TelegramMethod::fromArtifact('broken', ['api' => 'http']);
    }
}
