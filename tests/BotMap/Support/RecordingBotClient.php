<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\BotMap\Support;

use MeRezaRezaei\Teleframe\Bot\Services\BotClient;

/**
 * Offline BotClient double for the BotMap suite: records every call() the
 * transport makes and answers from a per-method script, so no test ever
 * touches the real Telegram API.
 */
class RecordingBotClient extends BotClient
{
    /** @var list<array{0: string, 1: array<string, mixed>}> */
    public array $calls = [];

    /** @var array<string, array<string, mixed>> */
    public array $script;

    /**
     * @param array<string, array<string, mixed>> $script method → response envelope
     */
    public function __construct(string $botToken = 'test:token', array $script = [])
    {
        parent::__construct($botToken);
        $this->script = $script;
    }

    public function call(string $method, array $params = []): array
    {
        $this->calls[] = [$method, $params];

        return $this->script[$method] ?? ['ok' => true, 'result' => ['method' => $method, 'params' => $params]];
    }

    public function lastCall(): ?array
    {
        return $this->calls === [] ? null : $this->calls[array_key_last($this->calls)];
    }
}