<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;
use RuntimeException;

/**
 * THROWN, never returned, when a stored peer discriminator names no entity the
 * mirror knows how to resolve.
 *
 * ## WHY THIS IS AN EXCEPTION AND NOT A `null`
 *
 * The operator named the failure this exists to make impossible:
 *
 * > "a consuming app can get any data in place, using all relations and data"
 *
 * A `belongsTo` whose foreign key does not exist returns an EMPTY relation, and
 * an empty relation is indistinguishable from "genuinely no rows". A host
 * writing `$dialog->peer->first_name` against a discriminator the mirror cannot
 * interpret gets `null` and concludes the data is missing from TELEGRAM rather
 * than from the mapping. That is confidently wrong, and it is worse than absent.
 *
 * So the three answers are kept apart, deliberately and by construction:
 *
 *  - **kind 0 — no peer.** Telegram did not send one. The migration states this
 *    is a REAL stored value, not a placeholder:
 *    `2026_10_04_000300_add_recent_repliers_to_tf_messages_replies.php` says
 *    *"`DEFAULT 0` is also the correct PEER value … the writer leaves both
 *    halves at their default rather than inventing a peer that Telegram did not
 *    send"*. `null` is the honest answer and that is what it returns.
 *  - **kind 1/2/3 — a peer of a known kind.** A relation is built and returned.
 *    An empty result from it means the entity row genuinely is not mirrored yet,
 *    which is a different fact from an unmapped leg and is visible as such.
 *  - **anything else — unmapped.** The enum and the stored data have diverged.
 *    That is a defect in the mirror, and it is raised rather than swallowed.
 *
 * `PolymorphicRelationsResolveThroughTfPeersTest` is the sibling that proves the
 * middle case cannot be satisfied by a registry of resolved peers, so this class
 * is not the only thing standing between a host and a wrong answer — it is the
 * one that makes the wrong answer LOUD when it happens anyway.
 */
final class UnmappedEntityPeer extends RuntimeException
{
    public static function forKind(string $table, string $typeColumn, int $kind): self
    {
        return new self(sprintf(
            'mirror peer discriminator %s.%s holds %d, which is not one of the mirrored peer kinds '
            .'(1=user, 2=chat, 3=channel) and not 0 either. That means the stored discriminator and '
            .'%s disagree, so NO relation can be built: returning null here would be indistinguishable '
            .'from "this dialog names a user that simply is not mirrored yet", and a host reading that '
            .'would conclude Telegram never sent the peer. Fix the writer or the enum; do not silence this.',
            $table,
            $typeColumn,
            $kind,
            PeerShapeTool::class,
        ));
    }

    public static function forMissingColumn(string $table, string $column): self
    {
        return new self(sprintf(
            'mirror peer column %s.%s does not exist on the row this relation was built from, so the '
            .'relation has no key to resolve with. A relation with no key returns EMPTY, and empty is '
            .'indistinguishable from "no rows" — which is the exact confusion this exception exists to '
            .'prevent.',
            $table,
            $column,
        ));
    }
}
