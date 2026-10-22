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

        self::assertSame('auth.checkPassword', $method->name, 'the method name must survive the artifact mapping unchanged');
        self::assertSame('mtproto', $method->api, 'the api must be the artifact api (mtproto), not a default');
        self::assertSame('0xd18b4d16', $method->id, 'the constructor id must be carried through verbatim from the artifact');
        self::assertSame('auth.Authorization', $method->returnType, 'the return type must be the artifact return (auth.Authorization)');
        self::assertSame('password', $method->params[0]['name'], 'the parameter NAME must be the artifact param name');
        self::assertSame('InputCheckPasswordSRP', $method->params[0]['type'], 'the parameter TYPE must be the artifact param type');
        self::assertNull($method->params[0]['flag_word'], 'a param with no flags field in the artifact must have a NULL flag_word, never a default 0');
        self::assertNull($method->params[0]['bit'], 'a param with no flags field in the artifact must have a NULL bit, never a default 0');
        self::assertNull($method->params[0]['required'], 'a param with no required flag in the artifact must have a NULL required, never a default');
        self::assertSame('SRP proof', $method->params[0]['description'], 'the parameter DESCRIPTION must survive the artifact mapping');
        self::assertSame([], $method->required, 'an mtproto method carries no bot-http required list, so it must be an empty array not null'); // mtproto: no bot-http required list
        self::assertSame([], $method->returns, 'an mtproto method carries no bot-http returns list, so it must be an empty array not null');
        self::assertSame(['PASSWORD_HASH_INVALID'], $method->errors, 'the artifact errors list must survive the mapping as the declared catalog of RPC errors');
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

        self::assertSame('flags', $method->params[0]['flag_word'], 'a param declared with a flags WORD must expose that word, not null');
        self::assertSame(0, $method->params[0]['bit'], 'a flags-word param must expose its BIT index (0), not null');
        self::assertNull($method->params[1]['flag_word'], 'a param declared with a bare bit (no flags word) must have a NULL flag_word, never the word flags');
        self::assertNull($method->params[1]['bit'], 'a param declared with a bare bit must still expose that bit index');
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

        self::assertSame('bot-http', $method->api, 'the api must be bot-http for a bot-api artifact');
        self::assertSame('', $method->id, 'a bot-http method carries NO crc constructor id, so it must be the empty string'); // bot-http carries no crc id
        self::assertSame('User|Me', $method->returnType, 'the return type must be the artifact returns list JOINED by a pipe'); // returns joined
        self::assertTrue($method->params[0]['required'], 'a param listed as required must be flagged required');
        self::assertNull($method->params[0]['flag_word'], 'bot-http params have no TL flags word, so it must be null');
        self::assertNull($method->params[0]['bit'], 'bot-http params have no TL flags bit, so it must be null');
        self::assertSame(['chat_id'], $method->required, 'the method required list must survive the artifact mapping');
        self::assertSame(['User', 'Me'], $method->returns, 'the method returns list must survive as the declared list, not the joined string');
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
