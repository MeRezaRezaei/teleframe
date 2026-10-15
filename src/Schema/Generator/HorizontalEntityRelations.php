<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Mirror\Models\ResolvesEntityPeer;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorShape;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableEntry;

/**
 * The HORIZONTAL relations: entity-to-entity edges the declared foreign keys
 * deliberately do not carry.
 *
 * ## THE GAP THIS EXISTS TO CLOSE, AND WHY IT IS NOT FK WORK
 *
 * Measured on the shipped DDL, every declared intra-mirror constraint joins a
 * child to its OWN parent — "companion hangs off its subject". `tf_users`,
 * `tf_chats` and `tf_dialogs` have **zero inbound foreign keys anywhere**. So
 * the graph is a forest of vertical edges with three isolated trees in it, and
 * the operator's requirement —
 *
 * > "we must make it laravel models and all relations between them so when an
 * > update comes we can query it in fact"
 *
 * — is not satisfiable by any FK this schema could declare.
 *
 * `2026_09_14_299999_create_tf_foreign_keys.php:25-38` says why the peer pairs
 * are unconstrained, and the first reason is sound and is the whole story here:
 *
 * > "One composite FK cannot branch on `peer_type`, and the peer tables disagree
 * > in key column count (2 vs the triad's 3)."
 *
 * So these edges are declared IN THE MODEL LAYER, from the executed shape —
 * which is the same authority the vertical relations read, applied to the axis
 * the constraints cannot express.
 *
 * ## THREE MECHANISMS, ALL DERIVED, NONE TRANSCRIBED
 *
 *  1. **Discriminated peer pairs.** A `tf_*` table carrying an integer-typed
 *     `{base}_type` beside an integer-typed `{base}_id`. The type check is the
 *     generator's OWN `castFor()`, so it is the same authority that decides
 *     `tinyint(1)` is a boolean — and that check is what excludes `venue`:
 *     `venue` is a `GeoPoint` in TEXT columns (`PolymorphicRelationsResolveThroughTfPeersTest`
 *     already asserts that), so its `venue_type` casts to `string` and no peer
 *     edge is derived for it. Nothing in this file names `venue`.
 *  2. **Catalog-declared scalar entity columns.** `MirrorCatalog` declares each
 *     anchor's fields with their stored type. Where it declares `user_id` /
 *     `chat_id` / `channel_id` as an integer scalar, that IS a reference to the
 *     identity table, and it becomes a `belongsTo` plus a reverse `hasMany`.
 *     The catalog is also what EXCLUDES the false positives: `tf_messages_media`
 *     and `tf_messages_service_action` carry `user_id` / `chat_id` /
 *     `channel_id` columns, and `2026_09_14_200012:56-88` calls them
 *     *"scalar residue of each ctor (nested objects deferred)"* — they hold 0 on
 *     essentially every row. The catalog declares no such field for them, so no
 *     relation is emitted, and emitting one would be the operator's named trap:
 *     a `belongsTo` that always returns an empty relation.
 *  3. **The tenant root.** `tf_accounts` is the one mirror table with no
 *     constraint at all, and it is the root every query scopes through. It gets
 *     a `hasMany` to each TL domain ANCHOR, on `account_id` = `id`, with the
 *     anchor set read from {@see Naming} rather than from a list of table names.
 *
 * ## WHY MECHANISM 1 EMITS BOTH DIRECTIONS
 *
 * A `belongsTo` constrains the PARENT table, so the discriminator — which lives
 * on the CHILD — cannot be expressed on it at all. A `belongsTo` to the right
 * entity table would therefore match on `peer_id` alone, and Telegram's peer id
 * spaces overlap in exactly the way that produces a confidently wrong answer.
 * The reverse side is the honest half: `hasMany($child, '{base}_id', 'id')` with
 * `where("{child}.{base}_type", $kind)` constrains both legs, and the kind is
 * the constraint rather than an assumption.
 *
 * The forward half is emitted by `ResolvesEntityPeer::entityPeer()`, which
 * chooses the table FROM the discriminator instead of guessing it.
 *
 * @see ResolvesEntityPeer
 */
final class HorizontalEntityRelations
{
    /**
     * The three mirrored peer kinds, and the identity table each one names.
     *
     * Read from {@see PeerShapeTool} — the enum the WRITE path fills the
     * discriminator with — and from {@see Naming} — the table→class rule the
     * generator already reads. Nothing here is a hand-written table list.
     *
     * @var array<int,string> kind => identity table
     */
    private const KIND_TABLE = [
        PeerShapeTool::PEER_USER => 'users',
        PeerShapeTool::PEER_CHAT => 'chats',
        PeerShapeTool::PEER_CHANNEL => 'channels',
    ];

    /**
     * The suffix a column name must carry to be an ENTITY reference rather than
     * some other bigint the dial also stores.
     *
     * `user_id` is a user; `venue_id` is a GeoPoint stored as TEXT and
     * `receipt_msg_id` is a message. Naming the suffix is what lets mechanism 2
     * ask the catalog about the ones it recognises and stay silent about the
     * rest.
     */
    private const ENTITY_COLUMNS = [
        'user_id' => 'users',
        'chat_id' => 'chats',
        'channel_id' => 'channels',
    ];

    /**
     * Peer bases that NAME ONE entity, and which one.
     *
     * ## WHY THIS IS A HAND LIST AND NOT A DERIVATION
     *
     * Nothing in the executed DDL says which kinds a discriminator may hold. The
     * column is a `tinyint`; the write path (`MirrorFactDecomposer::fillPeerHalf`)
     * stores whatever the wire normalised into it; and the TL declares the field
     * as `Peer`, which is exactly the union that admits all three. So "can this
     * base ever be kind 2?" is a fact about TELEGRAM's type system, not about
     * this schema, and it has to be stated.
     *
     * The default is the other way round, and deliberately so: a base whose name
     * carries `peer` is an UNQUALIFIED `Peer` and gets all three kinds
     * (`peer`, `saved_from_peer`, `message_peer`, `reply_to_peer_id`,
     * `boost_peer`, `theme_peer`, `starref_peer` — measured on the shipped
     * shape). A base that NAMES an entity gets only that entity. A base that does
     * neither gets NO reverse relation at all.
     *
     * ## WHY THE DEFAULT HAS TO BE "NOTHING", NOT "ALL THREE"
     *
     * The failure is not a wrong answer, it is a permanently EMPTY one.
     * `messageActionChatJoinedByLink` carries `to_id:Peer` and Telegram always
     * writes kind 1 there, so a reverse relation from `tf_chats` would return an
     * empty set for every row the mirror can hold — indistinguishable, to a
     * host, from the bug this whole slice exists to kill. Emitting fewer edges
     * costs a missing accessor; emitting a guaranteed-empty one costs a
     * confidently wrong reading, and the operator named that as the worse of the
     * two.
     *
     * `HorizontalEntityRelationsTest::test_every_derived_peer_base_names_an_entity()`
     * is what keeps this list CLOSED: a peer base the shape grows that is neither
     * peer-named nor listed here turns that test red and forces the next author
     * to say which entity it is.
     *
     * @var array<string,string> base => domain
     */
    private const BASE_ENTITY = [
        // `message.from_id`, `messageFwdHeader.from_id`, `messageActionChatJoinedByLink.to_id`,
        // `savedStarGift.host_id`, `starGift.owner_id`, `savedStarGift.released_by`,
        // `messages.replies.recent_repliers`, `message.guestchat_via_from` — every one of
        // them a USER, and none of them ever a chat or a channel.
        'from_id' => 'users',
        'saved_from_id' => 'users',
        'guestchat_via_from' => 'users',
        'recent_repliers' => 'users',
        'to_id' => 'users',
        'host_id' => 'users',
        'released_by' => 'users',
        'owner_id' => 'users',
    ];

    public function __construct(
        private readonly MirrorShape $shape,
        private readonly ?MirrorCatalog $catalog = null,
    ) {}

    /**
     * Every horizontal relation to emit, keyed by the table that declares it.
     *
     * @param  array<string,string>  $classFor  table => generated class
     * @return array<string,list<array{method:string,signature:string,body:string,doc:string,nullable:bool,usesPeerTrait:bool}>>
     */
    public function relationsFor(array $classFor): array
    {
        /** @var array<string,list<array{method:string,signature:string,body:string,doc:string,nullable:bool,usesPeerTrait:bool}>> */
        $relations = [];

        foreach ($this->peerPairs() as $child => $bases) {
            foreach ($bases as $base) {
                $this->emitPeerPair($relations, $classFor, $child, $base);
            }
        }

        foreach ($this->catalogEntityColumns() as $child => $columns) {
            foreach ($columns as $column => $domain) {
                $this->emitScalarEntity($relations, $classFor, $child, $column, $domain);
            }
        }

        $this->emitTenantRoot($relations, $classFor);

        foreach ($relations as $table => $list) {
            usort($list, static fn (array $a, array $b): int => strcmp($a['method'], $b['method']));
            $relations[$table] = $list;
        }

        return $relations;
    }

    /**
     * Every `tf_*` table carrying at least one integer peer pair.
     *
     * @return array<string,list<string>> table => bases, ksort order
     */
    public function peerPairs(): array
    {
        $pairs = [];

        foreach ($this->shape->tableNames() as $table) {
            if (! str_starts_with($table, 'tf_')) {
                continue;
            }

            $columns = $this->shape->columnNames($table);
            foreach ($columns as $column) {
                if (! str_ends_with($column, '_type') || ! str_contains($column, '_')) {
                    continue;
                }

                $base = substr($column, 0, -5);
                if ($base === '' || ! in_array($base.'_id', $columns, true)) {
                    continue;
                }

                // THE DISCRIMINATOR HAS TO BE AN INTEGER. This is the whole
                // reason `venue` — a GeoPoint in TEXT columns — does not become
                // a peer, and the check is `ModelGenerator::castFor()`, the same
                // type authority the generated casts are derived from.
                if (! $this->isInteger($table, $column) || ! $this->isInteger($table, $base.'_id')) {
                    continue;
                }

                $pairs[$table][] = $base;
            }
        }

        foreach ($pairs as $table => $bases) {
            sort($bases);
            $pairs[$table] = $bases;
        }
        ksort($pairs);

        return $pairs;
    }

    /**
     * Entity-reference columns the CATALOG declares, as table => column => domain.
     *
     * The catalog is the discriminator between a real reference and a bigint the
     * wire left behind: it declares `tf_updates.channel_id` as `BIGINT NOT NULL`
     * (the `Update` union carries a bare `long` there, per its own
     * `_folded_scalar_note`) and declares nothing of the sort on
     * `tf_messages_media`, whose matching columns are deferred-nested-object
     * residue.
     *
     * @return array<string,array<string,string>>
     */
    public function catalogEntityColumns(): array
    {
        if ($this->catalog === null) {
            return [];
        }

        $found = [];

        foreach ($this->catalog->tableNames() as $table) {
            if (! $this->shape->has($table)) {
                continue;
            }

            $columns = $this->shape->columnNames($table);
            $entry = $this->catalog->table($table);

            foreach (self::ENTITY_COLUMNS as $column => $domain) {
                if (! in_array($column, $columns, true)) {
                    continue;
                }
                if (! $this->catalogDeclares($entry, $column)) {
                    continue;
                }
                if (! $this->isInteger($table, $column)) {
                    continue;
                }
                // A column the ENGINE already constrains is not a horizontal
                // gap — `relationsFor()` emits a relation per declared foreign
                // key, on both sides, and emitting a second one under a
                // different name would give the host two accessors for one edge
                // and no way to tell which is the constrained one.
                if ($this->isConstrainedColumn($table, $column)) {
                    continue;
                }

                $found[$table][$column] = $domain;
            }
        }

        ksort($found);

        return $found;
    }

    /**
     * @param  array<string,list<array{method:string,signature:string,body:string,doc:string,nullable:bool,usesPeerTrait:bool}>>  $relations
     * @param  array<string,string>  $classFor
     */
    private function emitPeerPair(array &$relations, array $classFor, string $child, string $base): void
    {
        if (! isset($classFor[$child])) {
            return;
        }

        $typeColumn = $base.'_type';
        $idColumn = $base.'_id';

        // --- forward: the child names the entity, the discriminator picks it.
        $kinds = $this->kindsFor($base);
        $relations[$child][] = [
            'method' => lcfirst(self::camel($base)),
            'signature' => 'BelongsTo',
            'nullable' => true,
            'usesPeerTrait' => true,
            'body' => sprintf("return \$this->entityPeer('%s', '%s');", $typeColumn, $idColumn),
            'doc' => sprintf(
                'HORIZONTAL, not a foreign key. `%s`.`%s` is a `tinyint` discriminator and `%s`.`%s` '
                .'the Telegram peer id beside it, and one FK cannot branch on a type int '
                .'(2026_09_14_299999:25-31). Resolves against %s by the discriminator, pinned to this '
                .'account. Null when the discriminator is 0 — Telegram did not send a peer — and '
                .'RAISES on a discriminator outside the enum rather than returning an empty relation, '
                .'which is what a wrong key produces.',
                $child,
                $typeColumn,
                $child,
                $idColumn,
                $kinds === [] || $kinds === self::KIND_TABLE
                    ? '`'.Naming::domainTable('users').'`, `'.Naming::domainTable('chats').'` or `'.Naming::domainTable('channels').'`'
                    : '`'.Naming::domainTable((string) reset($kinds)).'`',
            ),
        ];

        // --- reverse: the entity reaches every row whose pair names it.
        foreach ($this->kindsFor($base) as $kind => $domain) {
            $entity = Naming::domainTable($domain);
            if (! array_key_exists($entity, $classFor) || ! array_key_exists($child, $classFor)) {
                continue;
            }

            $relations[$entity][] = [
                'method' => lcfirst(self::camel($base)).ucfirst(ModelGenerator::studlyStem($child)),
                'signature' => 'HasMany',
                'nullable' => false,
                'usesPeerTrait' => false,
                'body' => sprintf(
                    "return \$this->hasMany(%s::class, '%s', 'id')\n            ->where('%s', %d);",
                    $classFor[$child],
                    $idColumn,
                    $child.'.'.$typeColumn,
                    $kind,
                ),
                'doc' => sprintf(
                    'HORIZONTAL, not a foreign key: every `%s` row whose `%s`.`%s` is %d, so this '
                    .'reaches `%s` without depending on which id space the peer fell in. Both legs '
                    .'are constrained — the id leg by the relation and the kind leg by the predicate '
                    .'— so this cannot match the wrong entity table.',
                    $child,
                    $child,
                    $typeColumn,
                    $kind,
                    Naming::domainTable($domain),
                ),
            ];
        }
    }

    /**
     * @param  array<string,list<array{method:string,signature:string,body:string,doc:string,nullable:bool,usesPeerTrait:bool}>>  $relations
     * @param  array<string,string>  $classFor
     */
    private function emitScalarEntity(array &$relations, array $classFor, string $child, string $column, string $domain): void
    {
        $entity = Naming::domainTable($domain);
        if (! isset($classFor[$child]) || ! isset($classFor[$entity])) {
            return;
        }

        $relations[$child][] = [
            'method' => lcfirst(self::camel($column)),
            'signature' => 'BelongsTo',
            'nullable' => false,
            'usesPeerTrait' => false,
            'body' => sprintf("return \$this->belongsTo(%s::class, '%s', 'id');", $classFor[$entity], $column),
            'doc' => sprintf(
                'HORIZONTAL, not a foreign key. `%s`.`%s` is a bare `long` in the `%s` union that '
                .'`MirrorCatalog` declares on this table, so it names `%s`.`id` directly and carries '
                .'no discriminator. No FK is declared on it because the column exists on every row of '
                .'the table whether or not that ctor sent one — the value is 0 when it did not.',
                $child,
                $column,
                ucfirst(explode('_', $child)[0]),
                $entity,
            ),
        ];

        $relations[$entity][] = [
            'method' => lcfirst(self::camel($column)).ucfirst(ModelGenerator::studlyStem($child)),
            'signature' => 'HasMany',
            'nullable' => false,
            'usesPeerTrait' => false,
            'body' => sprintf("return \$this->hasMany(%s::class, '%s', 'id');", $classFor[$child], $column),
            'doc' => sprintf(
                'HORIZONTAL, not a foreign key: every `%s` row naming this `%s` in `%s`.'
                .' No FK is declared on it — see the forward side for why.',
                $child,
                $entity,
                $column,
            ),
        ];
    }

    /**
     * @param  array<string,list<array{method:string,signature:string,body:string,doc:string,nullable:bool,usesPeerTrait:bool}>>  $relations
     * @param  array<string,string>  $classFor
     */
    private function emitTenantRoot(array &$relations, array $classFor): void
    {
        $root = 'tf_accounts';
        if (! isset($classFor[$root])) {
            return;
        }

        foreach (Naming::domainModelNames() as $domain) {
            $table = Naming::domainTable($domain);
            if ($table === $root || ! isset($classFor[$table])) {
                continue;
            }

            $relations[$root][] = [
                'method' => self::pluralStem($domain),
                'signature' => 'HasMany',
                'nullable' => false,
                'usesPeerTrait' => false,
                'body' => sprintf("return \$this->hasMany(%s::class, 'account_id', 'id');", $classFor[$table]),
                'doc' => sprintf(
                    'HORIZONTAL, and the root of them. `tf_accounts` carries NO constraint at all — '
                    .'it has no `account_id` column, so it structurally cannot carry the tenant FK — '
                    .'yet 264 `tf_*` tables point at it. This reaches `%s` for one tenant. The '
                    .'companion tables under it are reached from their ANCHOR, not from here; the '
                    .'anchor set is read from `Naming`, so it cannot drift from the model layer.',
                    $table,
                ),
            ];
        }
    }

    /**
     * Does the catalog DECLARE this field on this table?
     *
     * `base` and `children` are the two field lists a catalog entry carries, and
     * both are `[fieldName, declaredType]` pairs. The check is for the NAME and
     * nothing else: what matters is whether the mirror says this field belongs
     * here, not what shape the catalog gave it.
     */
    private function catalogDeclares(MirrorTableEntry $entry, string $column): bool
    {
        foreach ([$entry->base, $entry->children] as $list) {
            foreach ($list as $field) {
                if ($field[0] === $column) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The kinds a peer base may legitimately hold, as kind => domain.
     *
     * Empty when the base names no entity and is not an unqualified `Peer` — see
     * {@see self::BASE_ENTITY} for why the answer is then "emit nothing" rather
     * than "emit everything".
     *
     * @return array<int,string>
     */
    public function kindsFor(string $base): array
    {
        if (isset(self::BASE_ENTITY[$base])) {
            $named = self::BASE_ENTITY[$base];

            return array_filter(self::KIND_TABLE, static fn (string $domain): bool => $domain === $named);
        }

        return $this->namesAPeer($base) ? self::KIND_TABLE : [];
    }

    /**
     * Does this base name the UNQUALIFIED `Peer` union?
     *
     * Word-wise, not by substring: `starref_peer` names a peer and
     * `guestchat_via_from` does not, and `str_contains($base, 'peer')` cannot
     * tell those apart from a base that merely has the letters somewhere.
     */
    public function namesAPeer(string $base): bool
    {
        foreach (explode('_', $base) as $part) {
            if ($part === 'peer') {
                return true;
            }
        }

        return false;
    }

    /** Does the ENGINE report an integer type for this column? `ModelGenerator::castFor()` is the authority. */
    private function isInteger(string $table, string $column): bool
    {
        return ModelGenerator::castFor((string) $this->shape->columnType($table, $column)) === 'int';
    }

    /**
     * Does a DECLARED foreign key on `$table` already carry `$column`?
     *
     * Read from the reflected shape, never from the DDL text — the same rule
     * `relationsFor()` follows, and for the same reason: a constraint emitted
     * by a shared `Blueprint` helper is invisible to a text search.
     */
    private function isConstrainedColumn(string $table, string $column): bool
    {
        foreach ($this->shape->foreignKeys($table) as $foreign) {
            if (in_array($column, $foreign['from'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * `users` → `users`, `messages` → `messages`, `sticker_sets` → `stickerSets`,
     * `channel_participants` → `channelParticipants`.
     *
     * The trailing `s` is added only when the stem does not already end in one,
     * so a domain that is plural in the SOURCE data does not come out as
     * `userss`. The domain names come from {@see Naming}, so this is naming
     * polish over a derived list and not a second list of tables.
     */
    private static function pluralStem(string $domain): string
    {
        $camel = lcfirst(self::camel($domain));

        return str_ends_with($camel, 's') ? $camel : $camel.'s';
    }

    /** `from_id` → `FromId`; no regex — string ops only, and this file is inside `src/Schema/**` where the ban does not apply but consistency does. */
    private static function camel(string $snake): string
    {
        return implode('', array_map(
            static fn (string $part): string => ucfirst($part),
            explode('_', $snake),
        ));
    }
}
