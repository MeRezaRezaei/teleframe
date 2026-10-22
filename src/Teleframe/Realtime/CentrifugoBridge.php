<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Realtime;

/**
 * Publishes payloads to Centrifugo channels. Standalone (no Laravel
 * dependency) — the concrete implementation wraps the Centrifugo HTTP API.
 */
interface CentrifugoBridge
{
    /**
     * Publish a JSON-serializable payload to a Centrifugo channel.
     *
     * @param array<string, mixed> $payload
     */
    public function publish(string $channel, array $payload): void;
}
