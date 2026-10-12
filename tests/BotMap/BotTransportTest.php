<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\BotMap;

use MeRezaRezaei\Teleframe\BotMap\BotTransport;
use MeRezaRezaei\Teleframe\Tests\BotMap\Support\RecordingBotClient;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use MeRezaRezaei\Teleframe\BotMap\BotMap;

final class BotTransportTest extends TestCase
{
    public function test_invoke_calls_a_registered_bot_by_name(): void
    {
        $clients = [];
        $map = new BotMap(null, null, $this->factory(['sendMessage' => self::ok(['message_id' => 42])], $clients));
        $map->register('support', ['token' => '123:ABC']);
        $transport = new BotTransport($map);

        $result = $transport->invoke('support', 'sendMessage', ['chat_id' => 9, 'text' => 'from a handler']);

        self::assertSame(42, $result['result']['message_id']);
        self::assertSame(['sendMessage', ['chat_id' => 9, 'text' => 'from a handler']], $clients[1]->lastCall());
    }

    public function test_for_returns_an_invokable_entry(): void
    {
        $clients = [];
        $map = new BotMap(null, null, $this->factory([], $clients));
        $map->register('support', ['token' => '123:ABC']);
        $transport = new BotTransport($map);

        $response = $transport->for('support')->command('getMe');

        self::assertTrue($response['ok']);
        self::assertSame('getMe', $clients[1]->lastCall()[0]);
    }

    public function test_invoke_unknown_bot_throws(): void
    {
        $clients = [];
        $map = new BotMap(null, null, $this->factory([], $clients));
        $transport = new BotTransport($map);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown bot [ghost].');
        $transport->invoke('ghost', 'getMe');
    }

    /**
     * @param array<string, array<string, mixed>> $script
     * @param list<RecordingBotClient> $clients
     */
    private function factory(array $script, array &$clients): callable
    {
        return static function (string $token, array $config) use ($script, &$clients): RecordingBotClient {
            $client = new RecordingBotClient($token, $script);
            $clients[] = $client;

            return $client;
        };
    }

    /** @return array{ok: bool, result: mixed} */
    private static function ok(mixed $result): array
    {
        return ['ok' => true, 'result' => $result];
    }
}