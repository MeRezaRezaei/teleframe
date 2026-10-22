<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

/**
 * Pins the two PEER legs of a message's key onto a relation to one of its facts.
 *
 * ## WHY THIS EXISTS RATHER THAN A COMPOSITE KEY ARGUMENT
 *
 * **Telegram numbers messages PER CHAT.** `dialog.top_message` (TL 852) is the
 * highest message id in that dialog and ids are dense per-dialog sequences from
 * 1, so `id=1`, `id=5` and `id=100` each exist in essentially EVERY dialog of a
 * real account — measured on one: 136 dialogs hold a message numbered 1, and
 * 80.98% of that account's history shares an `(account_id, id)` slot. A message's
 * identity is therefore `(account_id, peer_type, peer_id, id)`, which is the
 * primary key every message table now declares
 * (`2026_09_14_200010_create_tf_messages_tables.php`).
 *
 * Eloquent cannot express that as a relation argument. `hasOne()`,
 * `hasMany()` and `belongsTo()` take the foreign and local key as STRINGS and
 * call `str_contains()` on them, so passing an array raises
 * `TypeError: str_contains(): Argument #1 ($haystack) must be of type string,
 * array given` (measured, Laravel 11 — `HasOneOrMany::__construct` reached via
 * `HasRelationships::newHasOne()`). Laravel 11 has no multi-column `hasOne`.
 *
 * So the two peer legs are added as explicit `where()` clauses. That is not a
 * workaround invented here: `TfMessageReplyMarkupRows::buttons()` already pinned
 * the message `id` leg the same way before this change, for the same reason —
 * `parent_position` alone is not unique within a message.
 *
 * ## WHAT IT COSTS, STATED RATHER THAN HIDDEN
 *
 * A relation built this way reports `getForeignKeyName() === 'id'`, so the peer
 * legs live in the relation's QUERY rather than in its key metadata. Two
 * consequences, both asserted rather than assumed:
 *
 *  - `RelationIntegrityTest::test_model_relations_resolve_to_the_fk_pairs` reads
 *    the legs back off the relation's own query and compares them to the
 *    declared foreign key, so the relation and the constraint cannot drift
 *    apart.
 *  - `with()` / `whereHas()` / eager loading all work normally: the extra legs
 *    are part of the relation's query, which is what every one of those paths
 *    builds on.
 *
 * Omitting the legs is the silent failure this trait exists to prevent. With a
 * bare `id` leg the relation does not raise and does not return nothing — it
 * returns the SAME fact out of a DIFFERENT dialog, whichever wrote last, which
 * is precisely the corruption the widened primary key removes one layer down.
 *
 * @property int $peer_type the peer discriminator: 1=user, 2=chat, 3=channel
 * @property int $peer_id the peer id Telegram supplied (signed: chats and channels are negative)
 * @property int $message_peer_type the ENCLOSING MESSAGE's peer discriminator — present only on `TfMessageMedia`, the one table that owns the short names for its own payload peer
 * @property int $message_peer_id the ENCLOSING MESSAGE's peer id — same caveat
 */
trait ResolvesMessagePeerScope
{
    /**
     * Pin the peer legs onto a `hasOne`/`hasMany` toward a message's child fact.
     *
     * ## WHY THE SIGNATURE IS A `@template` AND NOT A CONCRETE RELATION TYPE
     *
     * This method MUTATES ITS ARGUMENT AND RETURNS THE SAME OBJECT — nothing
     * about it is specific to `hasOne`. So the honest type is the caller's own
     * relation type, unchanged, and `@template TRelation` says exactly that. A
     * concrete `@return HasOneOrMany` is a lie in both directions: it loses the
     * `HasOne`/`HasMany` distinction that 30 of the call sites' declared return
     * types carry (and phpstan correctly rejects `public function from():
     * HasOne` returning a bare `HasOneOrMany` — 77 errors, measured, before this
     * was made generic), and it would let a caller silently widen a `HasOne`
     * into something else.
     *
     * ## WHY THE BOUND IS `<*, *, *>` AND NOT `<Model, Model, mixed>`
     *
     * Because `TRelatedModel` and `TDeclaringModel` are INVARIANT on Laravel
     * 11's `HasOneOrMany` and `BelongsTo`, a bound spelled with concrete types
     * is unsatisfiable: `HasOne<TfMessageFromId, TfMessage>` is not a subtype of
     * `HasOneOrMany<Model, Model, mixed>`, and neither is any other widening —
     * measured, 63 errors on this directory alone with that spelling. The
     * wildcard `<*, *, *>` accepts every instantiation of the class while
     * still refusing a non-relation, which is the bound's entire job here
     * because the body only ever calls `where()`.
     *
     * Arity matters for the same reason. `HasOneOrMany` and `Relation` have
     * THREE template parameters and `BelongsTo` has TWO; omitting them is an
     * error (`generics.lessTypes`), and marking the invariant ones
     * `covariant` is another.
     *
     * @template TRelation of HasOneOrMany<*, *, *>
     *
     * @param  TRelation  $relation  built by the caller (`$this->hasOne(...)`)
     * @return TRelation the same instance, with the two peer legs added
     */
    private function pinPeerScope(HasOneOrMany $relation): HasOneOrMany
    {
        $relation->where('peer_type', $this->peer_type);
        $relation->where('peer_id', $this->peer_id);

        return $relation;
    }

    /**
     * Pin the peer legs onto a `belongsTo` back to the enclosing message.
     *
     * `BelongsTo` is not a `HasOneOrMany`, so this cannot reuse the method
     * above; the two where-clauses are the same two columns and the same
     * reason, which is why both halves live in one file. The relation is BUILT
     * by the caller (`$this->belongsTo(...)`) and handed in, so this trait never
     * has to assert that it lives on a model that can build relations.
     *
     * `BelongsTo` has TWO template parameters, not three, and it is not a
     * `HasOneOrMany` — the same arity and variance traps as above, and the same
     * reason the bound names both.
     *
     * @template TRelation of BelongsTo<*, *>
     *
     * @param  TRelation  $relation  built by the caller (`$this->belongsTo(...)`)
     * @return TRelation the same instance, with the two peer legs added
     */
    private function pinPeerScopeBelongsTo(BelongsTo $relation): BelongsTo
    {
        $relation->where('peer_type', $this->peer_type);
        $relation->where('peer_id', $this->peer_id);

        return $relation;
    }

    /**
     * Pin the peer legs onto a `belongsTo` back to the enclosing message, for
     * the ONE table that spells the message's scope with a `message_` PREFIX.
     *
     * ## WHY THIS IS A SEPARATE METHOD, AND WHY IT READS DIFFERENT COLUMNS
     *
     * `tf_messages_media` owns `peer_type`/`peer_id` for a peer carried INSIDE
     * the `MessageMedia` union payload — several media constructors ship one
     * (`messageMediaWebPage.peer`, `messageMediaStory.peer`, …) and the columns
     * are `->default(0)`. The ENCLOSING MESSAGE's scope on that table is
     * therefore the PREFIXED pair `message_peer_type`/`message_peer_id`
     * (`2026_09_14_200012:28-36`), and the FK declares exactly those four
     * columns (`2026_09_14_299999:81-85`).
     *
     * So the two methods differ in WHERE THEY READ THE VALUE FROM, which is the
     * half that is easy to get wrong and which WAS:
     *
     *  - the LEG COLUMNS are always the parent's `peer_type`/`peer_id`, because
     *    a `belongsTo` constrains the PARENT table. They are written qualified
     *    so that is unmistakable rather than inferred from the base query;
     *  - the leg VALUES come from the CHILD's attribute — `$this->peer_*` on an
     *    ordinary child, `$this->message_peer_*` on the media table.
     *
     * `pinPeerScopeBelongsTo()` here read `$this->peer_*`, which on the media
     * table is the PAYLOAD peer and is 0 on every ordinary media row, so
     * `$media->message` matched `tf_messages` rows whose peer was 0 and — since
     * no message is stored at 0/0 — resolved to NOTHING at all. That is the
     * exact silent failure `RelationIntegrityTest::
     * test_the_media_relation_resolves_to_its_seeded_message` now proves absent.
     *
     * A boolean parameter would have been the wrong shape: the differing half is
     * the attribute READ, and a flag invites the caller to pass the wrong one
     * with no compiler help. Two methods, one per convention, keeps each call
     * site unarguable.
     *
     * @template TRelation of BelongsTo<*, *>
     *
     * @param  TRelation  $relation  built by the caller (`$this->belongsTo(...)`)
     * @return TRelation the same instance, with the two peer legs pinned to the PREFIXED values
     */
    private function pinMessagePeerScopeBelongsTo(BelongsTo $relation): BelongsTo
    {
        $relation->where('tf_messages.peer_type', $this->message_peer_type);
        $relation->where('tf_messages.peer_id', $this->message_peer_id);

        return $relation;
    }
}
