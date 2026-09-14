<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Database\ConnectionInterface;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorChunkDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;

/**
 * Mirror chunk sync — the session-register ingest gate (truth model, cycle 6).
 *
 * Owner verbatim 2026-09-14: "at session register we are going to ask
 * teelgram to send all updates as a chunck and if we can ingest the big
 * update the full shcema is correct this is the way i can make sure my app
 * is working".
 *
 * The loop, exactly as the verbatim describes it:
 * 1. ask Telegram for its state (updates.getState);
 * 2. ask for the big update chunk (updates.getDifference);
 * 3. decompose the chunk into mirror rows (users, chats, messages);
 * 4. insert them — an FK failure is a clue to the wrong ingest path
 *    (MirrorFactWriter, cycle 4);
 * 5. report the result so the caller can persist the new watermark
 *    (PtsWatermark) and decide what to act on next.
 *
 * Zero FK clues across a well-formed chunk = the full schema is correct for
 * that data. The wire half (ChunkFetcher) is live-gated; this loop is pure
 * orchestration and testable with a fixture fetcher.
 */
final class MirrorChunkSync
{
    public function __construct(
        private readonly MirrorChunkDecomposer $decomposer,
        private readonly MirrorFactWriter $writer,
    ) {}

    /**
     * @param  array{pts: int, date: int, qts: int, seq: int}|null  $priorState  persisted watermark
     * @return array{
     *     state: array{pts: int, date: int, qts: int, seq: int},
     *     rows: int,
     *     inserted: int,
     *     clues: list<string>,
     *     fkClues: list<string>,
     * }
     */
    public function sync(ConnectionInterface $db, int $accountId, ChunkFetcher $fetcher, ?array $priorState = null): array
    {
        $state = $fetcher->getState();
        $chunk = $fetcher->getDifference($state, $priorState);

        $factRows = $this->decomposer->decomposeChunk($accountId, $chunk);
        $write = $this->writer->write($db, $factRows['rows']);

        return [
            'state' => $state,
            'rows' => count($factRows['rows']),
            'inserted' => $write['inserted'],
            'clues' => $factRows['clues'],
            'fkClues' => $write['fkClues'],
        ];
    }
}
