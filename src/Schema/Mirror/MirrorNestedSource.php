<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;

/**
 * One declared NESTED SOURCE: a table the ingest path can never reach as a
 * top-level constructor, and the exact parent field it really arrives in.
 *
 * The mirror ingest path routes on the top-level `'_'` of a payload
 * (`MirrorIngesterFactory`): a TL object reaches a table only when it IS the
 * update's own constructor. Several catalog tables therefore receive nothing,
 * because in the real protocol the object only ever appears as a NESTED field
 * of another constructor — `botInfo` inside `userFull.bot_info`,
 * `savedStarGift` inside `payments.savedStarGifts.gifts`,
 * `channelParticipant` inside `updateChannelParticipant.new_participant`.
 * Without a declaration the catalog cannot say so, and the table is a door in
 * a wall: correct DDL, a working write path, and no data.
 *
 * `$parentCtorMap` is the set of parent constructors that genuinely carry this
 * field, resolved by `MirrorCatalog` against the TL scheme at load. Nothing
 * here is inferred from a live payload: a declaration naming a field the wire
 * cannot carry fails loudly at startup instead of silently never producing a
 * row. That is the same rule, and the same code path, as the existing
 * `keyFrom` derivation check (`MirrorCatalog::assertDerivable`).
 *
 * `$context` names parent fields to OVERLAY onto the child payload before it is
 * decomposed. Some nested facts carry only part of their own identity: the
 * shipped `tf_channel_participants` keys on `(account_id, channel_id, user_id)`
 * while the `channelParticipant` constructor carries only `user_id` — the
 * `channel_id` lives on the ENCLOSING update. An overlay is the whole of that
 * mechanism: the context values are merged under the child's own fields (the
 * child always wins a collision), and the ordinary decomposer then fills every
 * column by name, so no new keying code path is introduced. Every context field
 * is checked to be a real parameter of the parent constructor, so an overlay
 * can only ever supply a value the wire genuinely carries.
 */
final class MirrorNestedSource
{
    /**
     * @param  string  $tfName  the tf_ table this nested object fills
     * @param  string  $parentTlType  TL type whose constructors carry the field
     * @param  string  $field  wire field on the parent carrying the object(s)
     * @param  TlParam  $param  the resolved TL parameter — the scheme is the proof
     * @param  array<string,bool>  $parentCtorMap  parent constructor name => true
     * @param  list<string>  $context  parent fields overlaid onto the child payload
     */
    public function __construct(
        public readonly string $tfName,
        public readonly string $parentTlType,
        public readonly string $field,
        public readonly TlParam $param,
        public readonly array $parentCtorMap,
        public readonly array $context = [],
    ) {}

    /** True when the wire field carries a LIST of objects, not one object. */
    public function isVector(): bool
    {
        return $this->param->kind() === 'vector';
    }

    /**
     * The parent constructors that genuinely carry this field.
     *
     * @return list<string>
     */
    public function parentCtors(): array
    {
        return array_keys($this->parentCtorMap);
    }

    /** Human-readable provenance for clue strings. */
    public function describe(): string
    {
        return "{$this->parentTlType}.{$this->field} → {$this->tfName}";
    }
}
