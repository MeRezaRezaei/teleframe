<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Realtime;

use phpcent\Client;

/**
 * Centrifugo publisher backed by the official phpcent client library.
 */
final class HttpCentrifugoBridge implements CentrifugoBridge
{
    private readonly Client $client;

    public function __construct(string $url, string $apikey = '', string $secret = '')
    {
        $this->client = new Client($url, $apikey, $secret);
    }

    public function publish(string $channel, array $payload): void
    {
        $this->client->publish($channel, $payload);
    }
}
