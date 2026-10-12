<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

/**
 * One hand-owned row of `src/Schema/schema/mirror-catalog.json`.
 *
 * `$key` is the OPTIONAL explicit primary-key column list, added 2026-09-29
 * (Cycle 31, second finding). The catalog's own invariant
 * (`MirrorCatalog::assertInvariants`) already declares that `base[0]` is the
 * table's identity and that the column NAME is not constrained — the curated
 * dial keys on `id`, `peer`, `link`, `title`, `emoticon`, ... . The resolver's
 * `deriveKeyColumns()` could only honour that by NAME (`id` / `peer` / `*_id`),
 * so any table keyed on a real-but-unnamed identity silently fell through to a
 * fabricated `{singular}_id`. Six tables were affected and the two derivations
 * disagreed with the shipped DDL on four of them. An explicit `key` states the
 * identity in the data instead of guessing it from a column-name heuristic;
 * absent, the historical derivation applies unchanged.
 *
 * `$keyFrom` (added 2026-09-29) is the EXPRESSED KEY DERIVATION, and it exists
 * for the one case `key` alone cannot honestly cover: a shipped DDL whose
 * PRIMARY KEY is a column the constructor does not carry, but whose value IS
 * on the wire under another name or one level down. `tf_bot_infos` ships
 * `primary(['account_id','id'])` while `botInfo` (TL 697) carries no `id` — the
 * identity is the ctor's own `user_id`. `tf_saved_star_gifts` ships
 * `primary(['account_id','id'])` while `savedStarGift` (TL 2024) carries no
 * `id` — the identity is the NESTED `StarGift.id`. A rename cannot fix either
 * (the renamed column would still be unfillable); a new column holding a value
 * nobody has would be a lie. So the entry states the derivation in the data:
 * `key` says which column the shipped DDL keys on, `keyFrom` says where the
 * value genuinely comes from. `MirrorCatalog` validates the path against the
 * TL scheme at load, so a derivation that names a value the payload cannot
 * carry fails loudly instead of silently dropping the row.
 *
 * `$sources` (added 2026-09-29) is the NESTED SOURCE DECLARATION, and it exists
 * for the tables `key`/`keyFrom` cannot reach at all: the ingest path routes on
 * a payload's TOP-LEVEL constructor, so an object that only ever arrives
 * nested inside another constructor (`botInfo` in `userFull.bot_info`,
 * `savedStarGift` in `payments.savedStarGifts.gifts`, `channelParticipant` in
 * `updateChannelParticipant.new_participant`) is never routed to its table —
 * a door in a wall: correct DDL, a working write path, no data. `sources` says
 * where the object really comes from: `['parent' => <TL type>, 'field' =>
 * <wire field>]`. `MirrorCatalog` validates each one against the TL scheme at
 * load — the parent type must exist and at least one of its constructors must
 * carry that field with exactly this table's TL type — so a declaration can
 * never paper over a field the payload cannot carry.
 *
 * `$scopedBy` (added 2026-10-03) is the DECLARED ENCLOSING SCOPE, and it
 * exists because `key` states a whole identity and could not express the one
 * shape that is both the most common and the most damaging: an `id` that is
 * only unique INSIDE a scope. Telegram numbers messages PER CHAT, so
 * `(account_id, id)` is not an identity at all — but the column is named `id`,
 * and `deriveKeyColumns()` rule 1 reads `base[0]` by NAME and "never asks what
 * scope Telegram scopes it to". `scoped_by` is that question, answered in the
 * data: a list of BASE COLUMN NAMES forming the enclosing scope, resolved by
 * the same rule `$key` is. When set, `deriveKeyColumns()` returns
 * `[...$scopedBy, ...$naturalKey]`, which also stops the fabrication branch
 * from firing.
 *
 * It is deliberately a separate field rather than an extension of `key`:
 * `key` REPLACES the derivation and is validated against the constructor,
 * while `scoped_by` PREFIXES a derived key and is validated against `base`. An
 * entry that has both has said two things, and `key` — the whole identity —
 * wins, so `scoped_by` is then redundant rather than contradictory.
 *
 * MEASURED on the shipped catalog 2026-10-03: 2 of 36 entries needed it —
 * `tf_messages` (base field `peer_id`) and `tf_stars_subscriptions` (base field
 * `peer`). Both shipped a bare `id` key while declaring the curated peer-pair
 * shape `peer_type TINYINT + peer_id BIGINT` in the very next base field.
 */
final class MirrorTableEntry
{
    /**
     * @param  list<string>  $ctors
     * @param  list<array{string,string}>  $base
     * @param  list<string>  $bools
     * @param  list<array{string,string}>  $children
     * @param  list<string>|null  $key  explicit PK columns; null = derive from `base[0]` by name
     * @param  list<string>|null  $scopedBy  base column names forming the enclosing scope; prefixes the derived key
     * @param  array<string,string>|null  $keyFrom  key column => dot-path into the payload that SUPPLIES it
     * @param  list<array{parentLabel:string,field:string,ctor?:string,type?:string,context:list<string>}>|null  $sources  declared nested sources
     */
    public function __construct(
        public readonly string $tfName,
        public readonly string $tlType,
        public readonly array $ctors,
        public readonly array $base,
        public readonly array $bools,
        public readonly array $children,
        public readonly ?array $key = null,
        public readonly ?array $scopedBy = null,
        public readonly ?array $keyFrom = null,
        public readonly ?array $sources = null,
    ) {}

    /**
     * The payload path that supplies a key column: the declared derivation when
     * one exists, otherwise the column's own name (a top-level param).
     *
     * @return string dot-path into the decoded payload
     */
    public function keySource(string $keyColumn): string
    {
        return $this->keyFrom[$keyColumn] ?? $keyColumn;
    }
}
