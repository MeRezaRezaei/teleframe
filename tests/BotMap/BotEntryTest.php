<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\BotMap;

use MeRezaRezaei\Teleframe\BotMap\BotEntry;
use MeRezaRezaei\Teleframe\Tests\BotMap\Support\RecordingBotClient;
use PHPUnit\Framework\TestCase;

final class BotEntryTest extends TestCase
{
    public function test_command_fluently_invokes_transport_with_params(): void
    {
        $client = new RecordingBotClient('123:ABC', [
            'sendMessage' => ['ok' => true, 'result' => ['message_id' => 9]],
        ]);
        $entry = new BotEntry('support', $client);

        $response = $entry->command('sendMessage', ['chat_id' => 1, 'text' => 'Hi']);

        self::assertTrue($response['ok']);
        self::assertSame(9, $response['result']['message_id']);
        self::assertSame(['sendMessage', ['chat_id' => 1, 'text' => 'Hi']], $client->lastCall());
    }

    public function test_call_is_a_low_level_passthrough(): void
    {
        $client = new RecordingBotClient();
        $entry = new BotEntry('support', $client);

        $response = $entry->call('getMe');

        self::assertTrue($response['ok']);
        self::assertSame('getMe', $client->lastCall()[0]);
        self::assertSame([], $client->lastCall()[1]);
    }

    public function test_manifest_merges_manual_and_probe_and_caches(): void
    {
        $client = new RecordingBotClient('t', [
            'getMyCommands' => ['ok' => true, 'result' => [
                ['command' => 'help', 'description' => 'h'],
                ['command' => 'pay', 'description' => 'p'],
            ]],
        ]);
        $entry = new BotEntry('shop', $client, ['pay', 'start']);

        self::assertSame(['pay', 'start', 'help'], $entry->manifest());
        self::assertSame(['pay', 'start', 'help'], $entry->manifest());
        $getMyCommandsCalls = array_values(array_filter($client->calls, static fn (array $call): bool => $call[0] === 'getMyCommands'));
        self::assertCount(1, $getMyCommandsCalls);
    }

    public function test_commands_probes_get_my_commands_and_is_cached(): void
    {
        $client = new RecordingBotClient('t', [
            'getMyCommands' => ['ok' => true, 'result' => [
                ['command' => 'start', 'description' => 's'],
                ['command' => 'help', 'description' => 'h'],
            ]],
        ]);
        $entry = new BotEntry('b', $client);

        self::assertSame(['start', 'help'], $entry->commands());
        self::assertSame(['start', 'help'], $entry->commands());
        self::assertCount(1, $client->calls);
    }

    public function test_commands_extracts_only_named_entries(): void
    {
        $client = new RecordingBotClient('t', [
            'getMyCommands' => ['ok' => true, 'result' => [
                ['command' => 'start', 'description' => 's'],
                ['description' => 'nameless'],
                'not-an-array',
                ['command' => '', 'description' => 'blank name'],
            ]],
        ]);
        $entry = new BotEntry('b', $client);

        self::assertSame(['start'], $entry->commands());
    }

    public function test_manifest_with_seed_does_not_reprobe(): void
    {
        $client = new RecordingBotClient('t');
        $entry = new BotEntry('b', $client, ['x'], ['x', 'seeded']);

        self::assertSame(['x', 'seeded'], $entry->manifest());
        self::assertCount(0, $client->calls);
    }

    public function test_manifest_empty_when_nothing_discoverable(): void
    {
        $client = new RecordingBotClient('t', [
            'getMyCommands' => ['ok' => true, 'result' => []],
        ]);
        $entry = new BotEntry('b', $client);

        self::assertSame([], $entry->manifest());
    }

    public function test_name_capabilities_and_route_prefix_accessors(): void
    {
        $entry = new BotEntry('named', new RecordingBotClient(), ['a'], null, 'rp');

        self::assertSame('named', $entry->name());
        self::assertSame(['a'], $entry->capabilities());
        self::assertSame('rp', $entry->routePrefix());
        self::assertSame('test:token', $entry->client()->botToken);
    }
}