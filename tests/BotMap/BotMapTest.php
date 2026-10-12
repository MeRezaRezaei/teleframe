<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\BotMap;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\BotMap\BotMap;
use MeRezaRezaei\Teleframe\Tests\BotMap\Support\RecordingBotClient;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

final class BotMapTest extends TestCase
{
    public function test_register_for_and_command_hit_the_fake_transport(): void
    {
        $clients = [];
        $map = $this->map(['getMyCommands' => self::ok([]), 'sendMessage' => self::ok(['message_id' => 7])], $clients);

        $map->register('support', ['token' => '123:ABC', 'capabilities' => ['answer_callback']]);

        $result = $map->for('support')->command('sendMessage', ['chat_id' => 1, 'text' => 'Hi']);

        self::assertTrue($result['ok']);
        self::assertSame(7, $result['result']['message_id']);
        self::assertSame(['sendMessage', ['chat_id' => 1, 'text' => 'Hi']], $clients[1]->lastCall());
    }

    public function test_for_unknown_bot_throws(): void
    {
        $clients = [];
        $map = $this->map([], $clients);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown bot [ghost].');
        $map->for('ghost');
    }

    public function test_all_returns_registered_bot_metadata_without_tokens(): void
    {
        $clients = [];
        $map = $this->map([], $clients);
        $map->register('a', ['token' => '1:x', 'capabilities' => ['alpha'], 'route_prefix' => 'pa']);
        $map->register('b', ['token' => '2:y']);

        $all = $map->all();

        self::assertSame(['a', 'b'], array_keys($all));
        self::assertSame('pa', $all['a']['route_prefix']);
        self::assertSame(['alpha'], $all['a']['capabilities']);
        self::assertNull($all['b']['route_prefix']);
        self::assertSame([], $all['b']['capabilities']);
        self::assertArrayNotHasKey('token', $all['a']);
        self::assertArrayNotHasKey('token', $all['b']);
    }

    public function test_register_requires_a_token(): void
    {
        $clients = [];
        $map = $this->map([], $clients);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Registering bot [empty] requires a 'token'.");
        $map->register('empty', []);
    }

    public function test_register_rejects_duplicate_names(): void
    {
        $clients = [];
        $map = $this->map([], $clients);
        $map->register('dup', ['token' => '1:x']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bot [dup] is already registered.');
        $map->register('dup', ['token' => '2:y']);
    }

    public function test_vaulted_token_is_decrypted_on_resolve_and_kept_out_of_memory(): void
    {
        $vault = new InMemoryVault();
        $clients = [];
        $map = new BotMap($vault, 'correct horse', $this->factory([], $clients));
        $map->register('ops', ['token' => 'sec:cret', 'vault_key' => 'ops-prod']);

        self::assertTrue($vault->findMessagesByName('tfbintoken:') !== []);

        $bots = (new ReflectionProperty(BotMap::class, 'bots'))->getValue($map);
        self::assertIsArray($bots);
        self::assertNull($bots['ops']['token']);

        $resolved = $map->for('ops');
        self::assertSame('sec:cret', $resolved->client()->botToken);
        self::assertSame('ops-prod', $map->all()['ops']['vault_key']);
    }

    public function test_second_vaulted_register_overwrites_the_same_key(): void
    {
        $vault = new InMemoryVault();
        $clients = [];
        $map = new BotMap($vault, 'pass', $this->factory([], $clients));
        $map->register('ops', ['token' => 'old:token', 'vault_key' => 'k']);
        $map->register('ops2', ['token' => 'new:token', 'vault_key' => 'k']);

        self::assertSame('new:token', $map->for('ops2')->client()->botToken);
    }

    public function test_vault_key_without_a_vault_seam_throws_at_register(): void
    {
        $clients = [];
        $map = $this->map([], $clients);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no VaultInterface + vault passphrase are wired');
        $map->register('ops', ['token' => 'sec:cret', 'vault_key' => 'k']);
    }

    public function test_register_probe_seeds_the_manifest_union(): void
    {
        $clients = [];
        $map = $this->map(['getMyCommands' => self::ok([
            ['command' => 'start', 'description' => 'start'],
            ['command' => 'help', 'description' => 'help'],
        ])], $clients);
        $map->register('shop', ['token' => 't', 'capabilities' => ['pay']]);

        self::assertSame(['pay', 'start', 'help'], $map->for('shop')->manifest());
        self::assertSame(['start', 'help'], $map->all()['shop']['probed']);
    }

    public function test_register_probe_failure_is_silent_and_manifest_falls_back_to_manual(): void
    {
        $map = new BotMap(null, null, static function (string $token): RecordingBotClient {
            return new class($token) extends RecordingBotClient {
                public function call(string $method, array $params = []): array
                {
                    throw new RuntimeException('network down');
                }
            };
        });

        $map->register('offline', ['token' => 't', 'capabilities' => ['manual_only']]);

        self::assertSame(['manual_only'], $map->all()['offline']['manifest']);
        self::assertSame([], $map->all()['offline']['probed']);
        self::assertSame(['manual_only'], $map->for('offline')->manifest());
    }

    public function test_for_exposes_capabilities_and_route_prefix(): void
    {
        $clients = [];
        $map = $this->map([], $clients);
        $map->register('c', ['token' => 't', 'capabilities' => ['x', 'y'], 'route_prefix' => 'rp']);

        self::assertSame(['x', 'y'], $map->for('c')->capabilities());
        self::assertSame('rp', $map->for('c')->routePrefix());
    }

    /**
     * @param array<string, array<string, mixed>> $script
     * @param list<RecordingBotClient> $clients
     */
    private function map(array $script, array &$clients): BotMap
    {
        return new BotMap(null, null, $this->factory($script, $clients));
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