<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\BotMap;

use MeRezaRezaei\Teleframe\Bot\Services\BotClient;

/**
 * A resolved, named bot (Phase 5f): one instance per BotMap::for() resolution,
 * wrapping a live BotClient plus the Q19 capability surface.
 *
 * - command() is the fluent invocation entry — a thin passthrough to
 *   BotClient::call() so every Bot API method stays reachable.
 * - manifest() = manual capabilities ∪ getMyCommands probe, cached.
 * - commands() re-probes getMyCommands through the transport and caches the
 *   result (optionally writing it back so the registry's copy stays fresh).
 */
final class BotEntry
{
    /** @var array<int, string>|null */
    private ?array $commandsCache = null;

    /** @var callable(array<int, string>): void|null */
    private $cacheCommands;

    /**
     * @param array<int, string> $capabilities   manual manifest (Q19: the documented part)
     * @param array<int, string>|null $manifest  seed = capabilities ∪ registration-time probe
     * @param callable(array<int, string>): void|null $cacheCommands write-back seam to the registry
     */
    public function __construct(
        private string $name,
        private BotClient $client,
        private array $capabilities = [],
        private ?array $manifest = null,
        private ?string $routePrefix = null,
        ?callable $cacheCommands = null,
    ) {
        $this->cacheCommands = $cacheCommands;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function client(): BotClient
    {
        return $this->client;
    }

    public function routePrefix(): ?string
    {
        return $this->routePrefix;
    }

    /** @return array<int, string> */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * Fluent invocation: call any Bot API method with $params on this bot.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed> the BotClient response (ok/result envelope)
     */
    public function command(string $method, array $params = []): array
    {
        return $this->client->call($method, $params);
    }

    /**
     * Low-level passthrough to the transport (alias of command()).
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function call(string $method, array $params = []): array
    {
        return $this->command($method, $params);
    }

    /**
     * Commands discoverable via the getMyCommands probe, cached per entry
     * (Q19: the automated part).
     *
     * @return array<int, string>
     */
    public function commands(): array
    {
        if ($this->commandsCache !== null) {
            return $this->commandsCache;
        }

        $commands = [];
        $response = $this->client->call('getMyCommands', []);
        $result = $response['result'] ?? [];
        if (is_array($result)) {
            foreach ($result as $raw) {
                if (is_array($raw) && isset($raw['command']) && is_string($raw['command']) && $raw['command'] !== '') {
                    $commands[] = $raw['command'];
                }
            }
        }

        $this->commandsCache = array_values(array_unique($commands));

        if ($this->cacheCommands !== null) {
            ($this->cacheCommands)($this->commandsCache);
        }

        return $this->commandsCache;
    }

    /**
     * Capability manifest = manual manifest ∪ probe result, cached.
     *
     * @return array<int, string>
     */
    public function manifest(): array
    {
        if ($this->manifest === null) {
            $this->manifest = array_values(array_unique(array_merge($this->capabilities, $this->commands())));
        }

        return $this->manifest;
    }
}