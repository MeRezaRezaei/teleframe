<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use Throwable;

/**
 * Offline Bot Client terminal for the notification-core tests: records every
 * call (method + params), never touches the network, and can be armed to
 * throw (transport-failure path). sendDocument calls snapshot the document
 * bytes BEFORE the NotifyService worker unlinks the temp attachment, so the
 * tests can assert the rendered HTML actually reached the terminal.
 */
final class FakeBotClient extends BotClient
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    public array $calls = [];

    /** @var list<string|null> contents of each sendDocument payload (null on read failure) */
    public array $documents = [];

    /** @var Throwable|null when set, call() throws this instead of recording */
    public ?Throwable $failure = null;

    /** Every call() that reached the terminal, including one that failed. */
    public int $attempts = 0;

    public function __construct()
    {
        parent::__construct('test-token', null, 1, 0);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function call(string $method, array $params = []): array
    {
        $this->attempts++;

        if ($this->failure !== null) {
            throw $this->failure;
        }

        $this->calls[] = ['method' => $method, 'params' => $params];

        if ($method === 'sendDocument' && isset($params['document']) && is_string($params['document'])) {
            $content = @file_get_contents($params['document']);
            $this->documents[] = $content === false ? null : $content;
        }

        return ['ok' => true, 'result' => ['message_id' => 1]];
    }
}
