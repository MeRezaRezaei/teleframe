<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\BotMap;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use RuntimeException;
use Throwable;

/**
 * Named bot registry (Phase 5f). Register third-party bots under stable names
 * once, then invoke any Telegram Bot API method on them by name:
 *
 *     $map = new BotMap(vault: $vault, vaultPassphrase: env('...'));
 *     $map->register('support', ['token' => '123:ABC', 'capabilities' => ['send_message']]);
 *     $map->for('support')->command('sendMessage', ['chat_id' => 1, 'text' => 'Hi']);
 *
 * Mirrors the MethodRegistry named-registry pattern: resolve-by-name with a
 * loud "unknown bot" exception. Token storage follows the Backup vault seam
 * (VaultInterface) — a config token is kept in process memory, while a token
 * registered with vault_key is encrypted into the vault at register and
 * decrypted again only on BotMap::for() resolution. Plain-PHP constructible:
 * the vault, passphrase, and BotClient factory are constructor wires, never a
 * hard-coded Laravel container call.
 *
 * Q19 (a): capability discovery is automated where Telegram lets us — a
 * best-effort getMyCommands probe runs at register() — and documented where it
 * isn't — the manual capabilities manifest array. A failing probe is silent
 * (offline registrations must not throw); the manual manifest still ships.
 */
final class BotMap
{
    /** @var array<string, array{
     *     token: string|null,
     *     vault_key: string|null,
     *     capabilities: array<int, string>,
     *     route_prefix: string|null,
     *     probed: array<int, string>,
     *     manifest: array<int, string>
     * }>
     */
    private array $bots = [];

    /** @var \Closure(string, array<string, mixed>): mixed|null */
    private ?\Closure $clientFactory;

    public function __construct(
        private ?VaultInterface $vault = null,
        private ?string $vaultPassphrase = null,
        ?callable $clientFactory = null,
    ) {
        $this->clientFactory = $clientFactory !== null ? \Closure::fromCallable($clientFactory) : null;
    }

    /**
     * Register a named bot.
     *
     * @param array<string, mixed> $config  {token: string, vault_key?: string,
     *                                      capabilities?: list<string>, route_prefix?: string}
     * @throws InvalidArgumentException when 'token' is missing/empty or the name is already taken
     * @throws RuntimeException when vault_key is used but no vault seam is wired
     */
    public function register(string $name, array $config): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('Bot name must not be empty.');
        }
        if (isset($this->bots[$name])) {
            throw new InvalidArgumentException("Bot [{$name}] is already registered.");
        }

        $token = isset($config['token']) && is_string($config['token']) ? $config['token'] : null;
        if ($token === null || $token === '') {
            throw new InvalidArgumentException("Registering bot [{$name}] requires a 'token'.");
        }

        $vaultKey = isset($config['vault_key']) && is_string($config['vault_key']) && $config['vault_key'] !== ''
            ? $config['vault_key']
            : null;
        $capabilities = $this->stringList($config['capabilities'] ?? []);
        $routePrefix = isset($config['route_prefix']) && is_string($config['route_prefix'])
            ? $config['route_prefix']
            : null;

        if ($vaultKey !== null) {
            $this->assertVaultReady();
            (new TokenVault($this->vault, (string) $this->vaultPassphrase))->store($vaultKey, $token);
        }

        $entry = [
            'token' => $vaultKey === null ? $token : null,
            'vault_key' => $vaultKey,
            'capabilities' => $capabilities,
            'route_prefix' => $routePrefix,
            'probed' => [],
            'manifest' => array_values(array_unique($capabilities)),
        ];
        $this->bots[$name] = $entry;

        // Q19 (a): automate the discoverable — best-effort probe at register.
        try {
            $probed = $this->probeCommands($token);
            $this->bots[$name]['probed'] = array_values(array_unique($probed));
            $this->bots[$name]['manifest'] = array_values(array_unique(array_merge($capabilities, $probed)));
        } catch (Throwable) {
            // Probe is best-effort by design (Q19); the manual manifest carries the rest.
        }
    }

    /**
     * Resolve a named bot to an invokable BotEntry, decrypting its token from
     * the vault when the bot was registered with vault_key.
     *
     * @throws InvalidArgumentException when the name is not registered
     * @throws RuntimeException when the vault seam is missing or the token cannot be decrypted
     */
    public function for(string $name): BotEntry
    {
        $entry = $this->bots[$name] ?? null;
        if ($entry === null) {
            throw new InvalidArgumentException("Unknown bot [{$name}].");
        }

        $token = $entry['token'];
        if ($token === null) {
            $this->assertVaultReady();
            $token = (new TokenVault($this->vault, (string) $this->vaultPassphrase))->retrieve((string) $entry['vault_key']);
        }

        return new BotEntry(
            name: $name,
            client: $this->createClient($token, $entry),
            capabilities: $entry['capabilities'],
            manifest: $entry['manifest'],
            routePrefix: $entry['route_prefix'],
            cacheCommands: function (array $commands) use ($name): void {
                if (isset($this->bots[$name])) {
                    $this->bots[$name]['probed'] = array_values(array_unique($commands));
                    $this->bots[$name]['manifest'] = array_values(array_unique(array_merge(
                        $this->bots[$name]['capabilities'],
                        $commands,
                    )));
                }
            },
        );
    }

    /**
     * All registered bots as introspection data (name → metadata). Tokens are
     * never included — vaulted bots expose vault_key only, and in-memory bots
     * expose no credential at all.
     *
     * @return array<string, array{
     *     name: string,
     *     vault_key: string|null,
     *     capabilities: array<int, string>,
     *     probed: array<int, string>,
     *     manifest: array<int, string>,
     *     route_prefix: string|null
     * }>
     */
    public function all(): array
    {
        $all = [];
        foreach ($this->bots as $name => $entry) {
            $all[$name] = [
                'name' => $name,
                'vault_key' => $entry['vault_key'],
                'capabilities' => $entry['capabilities'],
                'probed' => $entry['probed'],
                'manifest' => $entry['manifest'],
                'route_prefix' => $entry['route_prefix'],
            ];
        }

        return $all;
    }

    /**
     * @throws RuntimeException when a vault-backed operation is requested without a seam
     */
    private function assertVaultReady(): void
    {
        if ($this->vault === null || $this->vaultPassphrase === null || $this->vaultPassphrase === '') {
            throw new RuntimeException(
                'BotMap vault_key used but no VaultInterface + vault passphrase are wired '
                . '(pass them to the BotMap constructor).',
            );
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createClient(string $token, array $config): BotClient
    {
        if ($this->clientFactory !== null) {
            $client = ($this->clientFactory)($token, $config);
            if (! $client instanceof BotClient) {
                throw new RuntimeException('BotMap clientFactory must return a BotClient.');
            }

            return $client;
        }

        return new BotClient($token);
    }

    /**
     * @return array<int, string>
     */
    private function probeCommands(string $token): array
    {
        $client = $this->createClient($token, ['vault_key' => null, 'capabilities' => [], 'route_prefix' => null]);

        $commands = [];
        $response = $client->call('getMyCommands', []);
        $result = $response['result'] ?? [];
        if (is_array($result)) {
            foreach ($result as $raw) {
                if (is_array($raw) && isset($raw['command']) && is_string($raw['command']) && $raw['command'] !== '') {
                    $commands[] = $raw['command'];
                }
            }
        }

        return array_values(array_unique($commands));
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $out[] = $item;
            }
        }

        return array_values(array_unique($out));
    }
}