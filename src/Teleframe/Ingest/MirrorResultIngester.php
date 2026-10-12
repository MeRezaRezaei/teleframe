<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Database\ConnectionInterface;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;

/**
 * Rpc-result ingest route — the SECOND ingest surface the mirror has.
 *
 * The mirror had exactly one route: `MirrorIngesterFactory`'s callable, fed by
 * the Redis bus (`IngestConsumer`), so it only ever saw UPDATES. A constructor
 * that Telegram delivers ONLY as an RPC result — `userFull` (TL 263), reached
 * solely through `users.userFull` (RPC 1508) / `users.getFullUser` (RPC 2394) —
 * was therefore a door in a wall: correct shipped DDL, a correct model, a
 * correct write path, and no data, ever. Same for `payments.savedStarGifts`
 * (TL 2026), whose `SavedStarGift` children feed `tf_saved_star_gifts`.
 *
 * This class is that missing route, and it is deliberately NOT a second
 * implementation of the write. It holds the SAME three collaborators the
 * update route holds — `MirrorNestedSourceWalker`, `MirrorFactDecomposer`,
 * `MirrorFactWriter` — and calls the SAME `collect()` / `decompose()` /
 * `write()` methods with the same arguments in the same order. There is no
 * second decomposer, no second walker, no second insert. A behaviour change to
 * the write path lands in both routes at once, because there is only one write
 * path; two implementations of the same write are how two paths drift apart.
 *
 * What differs from the update route, and why:
 *
 * - The walker is invoked on the whole decoded RPC result rather than on one
 *   bus entry. That is the only difference that matters: `MirrorNestedSource-
 *   Walker::collect()` already descends wrapper nodes (a `UserFull` only ever
 *   arrives inside `users.UserFull`), so a real RPC result reaches exactly the
 *   declarations the catalog already carries.
 * - There is NO top-level-constructor routing and NO `UpdateStored` emission.
 *   An RPC result is not an update: it carries no peer routing decision and
 *   owes no app event, so the update route's `MirrorUpdateIngester` half
 *   (classify self-originated → route → emit) is deliberately not reused here.
 *   Reusing it would fabricate routing for values that were never routed.
 * - The nested-fact semantics are identical to the update route's: a nested
 *   fact is stored, row existence = fact existence, and FK/NOT NULL failures
 *   surface as the same clue strings (never swallowed).
 *
 * HONEST SCOPE (2026-09-29): this class is proven but not yet WIRED. The
 * catalog declaration is what makes the fact reachable by the walker, and this
 * class is what makes a result reachable at all — but nothing in `src/` calls
 * `MirrorIngesterFactory::buildResultRoute()` yet, so no result reaches this
 * class in production yet either. Which methods are mirrored on result is a
 * product decision, not an implementation gap; see that method's docblock for
 * the three candidate call sites and what each one would cost. The defect being
 * closed is therefore TWO-PART and the second part is still open.
 *
 * Provenance: the defect this closes is the missing RPC-result route; the
 * walk/decompose/write collaborators are the ones the update route already
 * used (`MirrorIngesterFactory::build`, lines 57-59 and 80-86).
 */
final class MirrorResultIngester
{
    public function __construct(
        private readonly MirrorNestedSourceWalker $walker,
        private readonly MirrorFactDecomposer $decomposer,
        private readonly MirrorFactWriter $writer,
    ) {}

    /**
     * Decompose and write the mirror facts an RPC result carries NESTED.
     *
     * A result that yields no nested fact writes nothing — absence of a fact is
     * absence of a row, never a placeholder.
     *
     * @param  int  $accountId  the account the result was fetched FOR — the mirror's scoping key
     * @param  array<string, mixed>  $result  the decoded RPC result, exactly as the transport returned it
     * @return array{inserted: int, fkClues: list<string>, unmappedClues: list<string>, valueClues: list<string>}
     */
    public function ingest(ConnectionInterface $db, int $accountId, array $result): array
    {
        $inserted = 0;
        $clues = [];
        $unmapped = [];
        // write() returns a FOURTH key — valueClues, the "this VALUE does not
        // fit the column the DDL gives it" bucket, distinct from fkClues and
        // unmappedClues. This layer REBUILDS a narrower array instead of
        // forwarding the writer's, so the key must be re-added here or every
        // value fault is dropped on the floor.
        $values = [];

        foreach ($this->walker->collect($result) as $fact) {
            $decomposed = $this->decomposer->decompose($fact['table'], $accountId, $fact['payload']);
            $clues = [...$clues, ...$decomposed['clues']];

            if ($decomposed['rows'] === []) {
                continue;
            }

            $written = $this->writer->write($db, $decomposed['rows']);
            $inserted += $written['inserted'];
            $clues = [...$clues, ...$written['fkClues']];
            $unmapped = [...$unmapped, ...$written['unmappedClues']];
            $values = [...$values, ...$written['valueClues']];
        }

        // valueClues forwarded last: same axis as its writer-side key, kept out
        // of fkClues/unmappedClues rather than folded into them.
        return [
            'inserted' => $inserted,
            'fkClues' => $clues,
            'unmappedClues' => $unmapped,
            'valueClues' => $values,
        ];
    }
}