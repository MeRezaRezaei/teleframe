<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

/**
 * Chunk fetcher — "at session register we are going to ask teelgram to send
 * all updates as a chunck" (owner verbatim 2026-09-14).
 *
 * The wire implementation wraps MTProto Client::call() for
 * updates.getState / updates.getDifference; tests substitute a fixture fake
 * so the sync loop is verifiable without live credentials.
 */
interface ChunkFetcher
{
    /**
     * Fetch the current sequence state.
     *
     * @return array{pts: int, date: int, qts: int, seq: int} decoded updates.State
     */
    public function getState(): array;

    /**
     * Fetch the big update — the difference between the remote state and
     * what the app already knows.
     *
     * @param  array{pts: int, date: int, qts: int, seq: int}  $state  remote updates.State
     * @param  array{pts: int, date: int, qts: int, seq: int}|null  $priorState  persisted watermark, if any
     * @return array<string, mixed> decoded updates.Difference
     */
    public function getDifference(array $state, ?array $priorState = null): array;
}
