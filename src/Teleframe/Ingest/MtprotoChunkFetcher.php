<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use MeRezaRezaei\Teleframe\Core\MTProto\Client;

/**
 * MTProto chunk fetcher — the live wire half of the session-register loop.
 *
 * Owner verbatim 2026-09-14: "at session register we are going to ask
 * teelgram to send all updates as a chunck".
 *
 * Wraps Client::call() for the two MTProto methods that produce the big
 * update:
 * - updates.getState → the remote pts/date/qts/seq baseline
 * - updates.getDifference → the chunk (new_messages + users + chats)
 *
 * Live-gated: the Client must be authenticated and connected.  The
 * ChunkFetcher interface lets MirrorChunkSync stay credential-free;
 * this class is the production adapter.
 */
final class MtprotoChunkFetcher implements ChunkFetcher
{
    public function __construct(
        private readonly Client $client,
    ) {}

    public function getState(): array
    {
        /** @var array{_?: string, pts?: int, date?: int, qts?: int, seq?: int, unread_count?: int} $raw */
        $raw = $this->client->call('updates.getState', []);

        return [
            'pts' => (int) ($raw['pts'] ?? 0),
            'date' => (int) ($raw['date'] ?? 0),
            'qts' => (int) ($raw['qts'] ?? 0),
            'seq' => (int) ($raw['seq'] ?? 0),
        ];
    }

    public function getDifference(array $state, ?array $priorState = null): array
    {
        $params = [
            'pts' => $priorState['pts'] ?? $state['pts'],
            'date' => $priorState['date'] ?? $state['date'],
            'qts' => $priorState['qts'] ?? $state['qts'],
        ];

        return $this->client->call('updates.getDifference', $params);
    }
}
