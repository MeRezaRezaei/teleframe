<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;
use MeRezaRezaei\Teleframe\Schema\Generator\Naming;

/**
 * Resolves an INLINE PEER PAIR — `{base}_type` + `{base}_id` — to the entity it
 * names. This is the HORIZONTAL axis the mirror's foreign keys deliberately
 * never carry.
 *
 * ## WHY A TRAIT AND NOT A `MorphTo`
 *
 * `MorphTo` is the framework's polymorphic relation and it is the wrong shape
 * here for three reasons that are each load-bearing:
 *
 *  - it resolves the alias through `Relation::$morphMap`, which is STATIC and
 *    process-global. A package that populated it would overwrite — or be
 *    overwritten by — the HOST application's own morph map. That is not a
 *    library's move;
 *  - it does not pin the TENANT leg. Every mirror table carries
 *    `account_id → telegram_accounts.user_id` and the relation is the only place
 *    that leg can be carried (see `PinsDeclaredForeignKeyLegs`);
 *  - the discriminator here is a `tinyint` off the TL constructor order
 *    (`PeerShapeTool`), not a class-name string, so the map would be a
 *    three-entry translation table that buys nothing the `match` below does not
 *    do readably.
 *
 * What this returns instead is a plain `BelongsTo`, built against the table the
 * discriminator names, and handed to `belongsTo()` so the tenant pin still runs.
 *
 * ## WHY THE TARGET TABLE IS DERIVED AND NOT DECLARED
 *
 * `Naming::domainModelFqcn()` is the SAME function the generator reads to decide
 * which class owns `tf_users` / `tf_chats` / `tf_channels`, and
 * `PeerShapeTool::PEER_*` is the enum the WRITE path fills those discriminators
 * with (`MirrorFactDecomposer::fillPeerHalf`). So the resolver, the writer and
 * the model layer cannot drift apart: there is one mapping and it is named once,
 * in the two places that own it.
 *
 * ## WHY KIND 0 IS NOT AN ERROR
 *
 * `{base}_type = 0` is a REAL stored value — "Telegram did not send this peer",
 * written as a default rather than as a NULL. The migration says so in its own
 * words; see {@see UnmappedEntityPeer}. So `null` here means "there is no peer"
 * and never "there is a peer I could not find the model for".
 *
 * @phpstan-require-extends Model
 */
trait ResolvesEntityPeer
{
    /**
     * The mirrored model class a peer discriminator names.
     *
     * `null` for BOTH kinds the mirror cannot resolve: 0 ("Telegram sent no
     * peer", a real stored value) and anything outside the enum. The caller
     * tells those two apart — {@see self::entityPeer()} raises on the second —
     * so this stays a total function with no exception in the middle of a
     * `match`, which is what keeps it usable from a gate.
     *
     * @return class-string<Model>|null
     */
    public static function entityPeerModel(int $kind): ?string
    {
        return match ($kind) {
            PeerShapeTool::PEER_USER => Naming::domainModelFqcn('users'),
            PeerShapeTool::PEER_CHAT => Naming::domainModelFqcn('chats'),
            PeerShapeTool::PEER_CHANNEL => Naming::domainModelFqcn('channels'),
            default => null,
        };
    }

    /**
     * A `BelongsTo` to the entity this row's peer pair names.
     *
     * `null` — and only `null` — when the discriminator is 0, which the shipped
     * migrations define as "Telegram did not send a peer". An unmapped kind
     * RAISES ({@see UnmappedEntityPeer}); it never degrades to an empty
     * relation, because an empty relation is the shape a wrong foreign key
     * produces and the two must never look alike.
     *
     * @param  string  $typeColumn  the `{base}_type` discriminator on THIS row
     * @param  string  $idColumn  the `{base}_id` value on THIS row
     * @return BelongsTo<Model, $this>|null
     *
     * @throws UnmappedEntityPeer
     */
    public function entityPeer(string $typeColumn, string $idColumn): ?BelongsTo
    {
        $kind = $this->peerKind($typeColumn);

        if ($kind === 0) {
            return null;
        }

        $related = self::entityPeerModel($kind);

        if ($related === null) {
            throw UnmappedEntityPeer::forKind($this->getTable(), $typeColumn, $kind);
        }

        return $this->belongsTo($related, $idColumn, 'id');
    }

    /**
     * The discriminator's value, with a missing column RAISING rather than
     * reading as 0.
     *
     * `getAttribute()` returns `null` for an attribute the row does not carry,
     * `(int) null` is `0`, and `0` means "no peer". So a renamed or dropped
     * discriminator column would silently turn every peer on the table into "no
     * peer" — the exact empty-vs-unmapped confusion, wearing a different hat.
     *
     * @throws UnmappedEntityPeer
     */
    private function peerKind(string $typeColumn): int
    {
        $value = $this->getAttributes()[$typeColumn] ?? null;

        if ($value === null) {
            throw UnmappedEntityPeer::forMissingColumn($this->getTable(), $typeColumn);
        }

        return (int) $value;
    }
}
