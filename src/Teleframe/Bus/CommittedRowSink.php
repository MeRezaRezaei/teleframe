<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

use MeRezaRezaei\Teleframe\Core\Contracts\UpdateSinkInterface;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;

/**
 * A bus sink that publishes an update only AFTER the row representing it is
 * committed — the S1 inversion (D-039/D-040).
 *
 * ## WHY THIS IS A CONTRACT AND NOT A DETAIL OF RedisStreamSink
 *
 * The invariant is a property of the FLOW, not of one class: "no update is
 * published to `tg:stream:updates` until the row that represents it is
 * committed". Only the caller of the sink knows when the update is being
 * produced, and only the caller can supply the writer that commits it. So the
 * producer asks for the two-phase shape explicitly and a sink that only
 * implements UpdateSinkInterface still works — it simply publishes without a
 * committed key, which is why every implementation of this contract is the
 * one used on the daemon path.
 *
 * `UpdatePollerService::processUpdate()` is the caller: it mints the producer
 * frame (D-041), resolves the committer, and hands both here.
 */
interface CommittedRowSink extends UpdateSinkInterface
{
    /**
     * Commit $update's row through $committer, then publish the committed key.
     *
     * @param  array<string, mixed>  $update  the raw update as it left the wire
     * @param  string|null  $source  tenancy source, as handle() receives it
     * @param  UpdateIngestor|null  $committer  the truth writer, or null when
     *                                          none is reachable — a framework-free
     *                                          host, or a container that cannot build
     *                                          one. A null committer degrades to an
     *                                          identity-only entry carrying the
     *                                          -1/-1 sentinels (D-042), NEVER back to
     *                                          the payload shape.
     * @param  string|null  $frame  the producer frame minted for THIS update, or null
     *                              for a caller that mints none. It is the identity
     *                              a seq-less row's key is derived from, and it is
     *                              what makes a re-delivery an UPSERT.
     */
    public function handleCommitted(
        array $update,
        ?string $source,
        ?UpdateIngestor $committer,
        ?string $frame,
    ): bool;
}
