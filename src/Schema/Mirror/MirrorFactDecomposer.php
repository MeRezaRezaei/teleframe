<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumn;

/**
 * Fact decomposer — the mirror ingest write path (truth model, cycle 2).
 *
 * Owner verbatim 2026-09-14: "suppose we have the telgram data came from its
 * mtproto and they simple shoould be insertable into the fully nf 5 fully
 * migrated realational database and what ever forign key that fails gives us
 * a clue to the wrong path of ingesting we have".
 *
 * One decoded TL payload (['_' => constructor, field => value]) decomposes
 * into insertable rows for the parent table. The schema is the contract:
 * - every row carries (account_id, telegram-supplied key...) — we never invent
 *   identity (telegram-reliance invariant, cycle 1);
 * - booleans store 0/1; peer shapes expand to {field}_type / {field}_id;
 * - `bytes` values are HEX-ENCODED before they land: they arrive as raw
 *   binary, every bytes column ships as TEXT, and MySQL strict mode rejects
 *   binary in a TEXT column — which, inside the ingest transaction, took the
 *   whole page down. See `encodeBytes()`;
 * - a constructor the catalog does not know, or a payload that cannot fill a
 *   keying/peer column, is reported as an ingest clue — NOT swallowed.
 *
 * Children decomposition (vector/union children tables) is the next cycle.
 */
final class MirrorFactDecomposer
{
    /**
     * Shipped mirror tables that declare a BARE, defaultless `constructor`
     * column which `MirrorTable::$constructor` does not account for.
     *
     * ## The defect this list exists for
     *
     * The resolver sets `MirrorTable::$constructor` only when the ctor set can
     * VARY (`count($ctors) > 1`), because on a UNION table the discriminator is
     * what says which member is stored. That gate answers "can this ctor vary?",
     * not "does the column exist?" — and the shipped DDL, hand-authored per
     * domain, disagrees on exactly these eight tables. `ChatBannedRights` is a
     * SINGLE ctor, so the resolver says "no discriminator", while
     * `2026_09_14_200002_create_tf_chats_tables.php:125` says
     * `$table->text('constructor');` — NOT NULL, no default.
     *
     * The row therefore carried no `constructor` key, and MySQL strict answered
     * 1364 "Field 'constructor' doesn't have a default value". Live, against the
     * operator's own account, on the first `messages.getDialogs` page:
     *
     *     insert into `tf_chats_default_banned_rights`
     *       (`account_id`, `id`, `view_messages`, `send_message…
     *
     * and because the mirror write is wrapped in `$db->transaction(...)`
     * (`MirrorIngesterFactory::buildResultRoute()`) that ONE row rolled back the
     * whole page — which is exactly the observed shape: `tf_users` landed (its
     * entry does carry a discriminator), `tf_chats` stayed at 0. Nothing about
     * "the child row forgot its constructor" is visible until MySQL refuses the
     * insert, so it has to be written down here or rediscovered live.
     *
     * ## Why a curated list and not a rule
     *
     * Measured against the real resolver and the real migration set, not
     * reasoned about:
     *
     *  - 264 `tf_*` tables ship; 84 declare a `constructor` column, 12 of those
     *    declare it WITH `->default('')` (so they cannot raise 1364) and 72 bare.
     *  - Of the direct children `childRow()` actually walks, exactly these eight
     *    ship a bare discriminator that no other rule fills.
     *
     * Every STRUCTURAL alternative was tried against that same resolver and each
     * one admits at least 22 tables that must NOT gain the column, because
     * `MirrorFactWriter` would drop it and report an "is not in the shipped DDL"
     * unmapped clue per row — noise in the one ledger the operator reads to find
     * what the mirror is not modelling:
     *
     *  - "is the child's wire field an object?" admits `tf_messages_replies`,
     *    `tf_users_restriction_reason`, `tf_todo_lists_title` (…22 tables), all
     *    of which ship no discriminator column;
     *  - "does the child carry no column named after its own wire field?" admits
     *    the same 22;
     *  - "does the payload item carry its own `_`?" admits the same 22 again.
     *
     * A blanket "always write the ctor" would additionally name 12 tables that
     * declare `constructor` WITH a default and 180 that declare no such column,
     * so it is rejected on evidence, not on taste. The shipped DDL is the only
     * exact authority and it is hand-authored, which makes a curated list the
     * honest shape of the answer — and
     * `tests/Mirror/BareDiscriminatorColumnTest::test_the_curated_list_is_exactly_the_set_the_shipped_ddl_demands`
     * recomputes this set from the migration files and the real resolver on every
     * run, so the list cannot drift: a new bare column, or a table whose DDL gains
     * a default, is a red test rather than a stale constant.
     *
     * ## Provenance, per entry
     *
     *  - `tf_chats_admin_rights`, `tf_chats_default_banned_rights`
     *      2026_09_14_200002_create_tf_chats_tables.php — `ChatAdminRights` /
     *      `ChatBannedRights`, both single-ctor, both flag-bools + `until_date`.
     *  - `tf_dialogs_notify_settings`
     *      2026_09_14_200004_create_tf_dialogs_tables.php — `PeerNotifySettings`.
     *  - `tf_messages_service`, `tf_messages_reply_markup_rows`
     *      2026_09_14_200010_create_tf_messages_tables.php — `MessageService` and
     *      `ReplyMarkupRow`, both single-ctor; the row table is depth-2 today
     *      (`childRow()` does not recurse) and is listed anyway so the value is
     *      right the moment recursion lands.
     *  - `tf_sticker_sets`
     *      2026_09_14_200023_create_tf_sticker_sets_tables.php — `StickerSet`,
     *      whose sibling scalar stores (`…_installed_date`, `…_thumb_version`)
     *      declare the column WITH a default and are deliberately NOT here.
     *  - `tf_users_stories_max_id` (`RecentStory`) and `tf_users_usernames`
     *      (`Username`, positioned)
     *      2026_09_14_200001_create_tf_users_tables.php — while the sibling
     *      `tf_users_restriction_reason` ships no column at all.
     *  - the five `tf_channels_*` mirrors, added 2026-10-11
     *      `tf_channels_admin_rights` / `_banned_rights` /
     *      `_default_banned_rights` (`ChatAdminRights` / `ChatBannedRights`,
     *      single-ctor, flag-bools + `until_date`), `_stories_max_id`
     *      (`RecentStory`) and `_usernames` (`Username`, positioned).
     *      2026_09_14_200003_create_tf_channels_tables.php. These five entered
     *      this list because the catalog gained a `tf_channels` parent on
     *      2026-10-11 (`05e1488d`), which put all eighteen `tf_channels_*`
     *      tables inside the resolver's reachable graph for the first time —
     *      before that the resolver never produced them and so never needed a
     *      discriminator to write. They are the `channel`-sector twins of the
     *      `tf_chats_*` and `tf_users_*` entries above and are demanded for the
     *      identical reason: a single-ctor union decomposes to `constructor=''`,
     *      while the hand-authored shipped table declares the column anyway.
     *      MEASURED against the resolver + the migration scanner: the demanded
     *      set is exactly these five plus the eight above.
     *
     * @var list<string>
     */
    private const BARE_DISCRIMINATOR_TABLES = [
        'tf_channels_admin_rights',
        'tf_channels_banned_rights',
        'tf_channels_default_banned_rights',
        'tf_channels_stories_max_id',
        'tf_channels_usernames',
        'tf_chats_admin_rights',
        'tf_chats_default_banned_rights',
        'tf_dialogs_notify_settings',
        'tf_messages_reply_markup_rows',
        'tf_messages_service',
        'tf_sticker_sets',
        'tf_users_stories_max_id',
        'tf_users_usernames',
    ];

    public function __construct(
        private readonly MirrorTableResolver $resolver,
        private readonly MirrorCatalog $catalog,
    ) {}

    /**
     * Decompose a decoded TL payload into insertable rows for a parent table.
     *
     * @param  int  $accountId  session account id (our scoping key)
     * @param  array<string, mixed>  $payload  decoded TL object: ['_' => ctor, field => value]
     * @return array{
     *     rows: list<array{table: string, row: array<string, int|string|null>}>,
     *     clues: list<string>,
     * }
     */
    public function decompose(string $tfName, int $accountId, array $payload): array
    {
        $entry = $this->catalog->table($tfName);
        $ctor = (string) ($payload['_'] ?? '');

        if (! in_array($ctor, $entry->ctors, true)) {
            return [
                'rows' => [],
                'clues' => ["{$tfName}: constructor '{$ctor}' is not in catalog ctors — cannot place the fact"],
            ];
        }

        $table = $this->resolver->resolveAll([$tfName])[0];
        // The discriminator column exists when the ctor set can vary (>1 ctor),
        // OR when this table is one of the few whose hand-authored shipped DDL
        // declares a bare `constructor` anyway — see BARE_DISCRIMINATOR_TABLES
        // for the whole 1364 story. Writing it for any OTHER table would put a
        // column the shipped DDL does not have into the row; the writer drops
        // such a column, but reports it as an unmodelled field, which floods the
        // clue ledger with noise instead of fixing anything.
        $row = ['account_id' => $accountId];
        if ($table->constructor !== '' || in_array($tfName, self::BARE_DISCRIMINATOR_TABLES, true)) {
            $row['constructor'] = $ctor;
        }
        $clues = [];

        foreach ($table->columns as $column) {
            if ($column->peer) {
                $this->fillPeerHalf($column, $payload, $row, $clues, $tfName);

                continue;
            }

            $field = $column->name;

            if ($column->name === 'constructor') {
                continue; // discriminator already set
            }

            if ($column->name === 'account_id') {
                continue; // scoping key, set by the caller
            }

            if (in_array($column->name, $table->keyColumns, true)) {
                $value = $this->keyValue($entry, $column->name, $payload);
                if ($value === null) {
                    $source = $entry->keySource($column->name);
                    $clues[] = "{$tfName}: keying column '{$column->name}' missing from payload"
                        .($source === $column->name ? '' : " (supplied by '{$source}')")
                        .' — cannot place the fact';

                    continue;
                }
                $row[$column->name] = $this->castValue($value, $column, $clues, $tfName, $column->name);

                continue;
            }

            if (in_array($column->name, $table->booleanColumns, true)) {
                $row[$column->name] = (int) (bool) ($payload[$field] ?? false);

                continue;
            }

            $value = $payload[$field] ?? null;
            if ($value !== null) {
                $row[$column->name] = $this->castValue($value, $column, $clues, $tfName, $column->name);
            }
        }

        return $this->decomposeChildren($tfName, $accountId, $row, $payload, $clues);
    }

    /**
     * The value that supplies a key column, read from the entry's expressed
     * derivation when it declares one.
     *
     * A shipped PRIMARY KEY may name a column the constructor does not carry
     * (`tf_bot_infos.id`, `tf_saved_star_gifts.id`) while the value IS on the
     * wire — under the ctor's own `user_id`, or under a nested `StarGift.id`.
     * The entry's `keyFrom` states that path; without it the column name is
     * the payload field, which is the historical behaviour and still what
     * every undeclared key uses.
     *
     * @param  array<string, mixed>  $payload
     */
    private function keyValue(MirrorTableEntry $entry, string $keyColumn, array $payload): mixed
    {
        $path = $entry->keySource($keyColumn);
        if (! str_contains($path, '.')) {
            return $payload[$path] ?? null;
        }

        $value = $payload;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Decompose a parent row's children into child-table rows.
     *
     * Every child table of a parent is keyed by (account_id, parent keyColumns
     * [+ position]) — the FK that links the child back to its parent. A child
     * row can only be placed when its parent key exists; a payload field that
     * carries a nested object/vector becomes child rows; a missing payload
     * field means the fact is absent (row existence = fact existence).
     *
     * @param  int  $accountId  scoping key for every produced row
     * @param  array<string, int|string|null>  $parentRow  the already-decomposed parent row
     * @return array{
     *     rows: list<array{table: string, row: array<string, int|string|null>}>,
     *     clues: list<string>,
     * }
     */
    public function decomposeChildren(string $tfName, int $accountId, array $parentRow, array $payload, array $carriedClues = []): array
    {
        $table = $this->resolver->resolveAll([$tfName])[0];
        $rows = [['table' => $tfName, 'row' => $parentRow]];
        $clues = $carriedClues;

        foreach ($table->children as $child) {
            if ($child->parentTf !== $tfName) {
                continue; // shared catalog node (tf_message_medias, tf_chats, ...): own context, own key
            }

            $plain = (string) substr($child->tfName, strlen($tfName) + 1);
            $value = $payload[$plain] ?? null;

            if ($value === null) {
                continue; // absent fact → no row (row existence = fact existence)
            }

            if ($child->positioned) {
                // Vector children: every element becomes one positioned row.
                if (! is_array($value) || array_is_list($value) === false && ! $this->isVectorList($value)) {
                    $clues[] = "{$child->tfName}: positioned child expects a list payload for '{$plain}'";

                    continue;
                }
                $items = $this->vectorItems($value);
                foreach ($items as $position => $item) {
                    // A scalar element is NOT the payload object `childRow()`
                    // was written for, so it is shaped here or the call raises
                    // a TypeError no catch downstream can convert to a clue.
                    // See `childElement()` for the measurement and the reason
                    // the value is placed rather than dropped.
                    $element = $this->childElement($child, $plain, $item, $position, $clues);
                    if ($element === null) {
                        continue; // unplaceable element — the clue says which
                    }

                    $rows[] = [
                        'table' => $child->tfName,
                        'row' => $this->childRow($child, $accountId, $parentRow, $position, $element, $clues, $payload),
                    ];
                }

                continue;
            }

            $rows[] = [
                'table' => $child->tfName,
                'row' => $this->childRow($child, $accountId, $parentRow, null, is_array($value) ? $value : [$plain => $value], $clues, $payload),
            ];
        }

        return ['rows' => $rows, 'clues' => $clues];
    }

    /**
     * One element of a positioned vector child, shaped for `childRow()`.
     *
     * ## THE FATAL THIS EXISTS FOR (MEASURED 2026-10-04)
     *
     * `childRow()` takes `array $item`, and it was called with whatever
     * `vectorItems()` yielded. For a `Vector<SomeUnion>` that is the nested
     * payload object the signature expects. For a `Vector<long>` it is an `int`:
     *
     *     TypeError: MirrorFactDecomposer::childRow(): Argument #5 ($item)
     *     must be of type array, int given, called in
     *     src/Schema/Mirror/MirrorFactDecomposer.php on line 294
     *       at src/Schema/Mirror/MirrorFactDecomposer.php:317
     *
     * reached from a real `messageActionChatCreate`
     * (`messageActionChatCreate#bd47cbad title:string users:Vector<long>
     * = MessageAction`, `src/Schema/schema/sources/api_full.tl:145`). A
     * `TypeError` is not the `QueryException` `MirrorFactWriter::write()`
     * catches (`src/Schema/Mirror/MirrorFactWriter.php:639`), so it left the
     * write and took the whole PAGE — the same blast radius as the
     * duplicate-key failures that writer's own docblock records for the page
     * transaction. No shipped test reached it, because the one fixture on this
     * path carries an EMPTY users list.
     *
     * ## WHY A SCALAR IS PLACED AND NOT CLUE-AND-SKIPPED
     *
     * The resolver ALREADY built the column for this element and said so in
     * its own words: `resolveVectorFromType()` declares that "scalar elements
     * (`Vector<long>`, `Vector<string>`, ...) become a positioned child holding
     * a single `value` column so no element data is dropped"
     * (`src/Schema/Mirror/MirrorTableResolver.php:458-460`), and
     * `scalarValueColumn()` (lines 534-545) names that column `value` for
     * every scalar element type. The shipped DDL declares it too
     * (`tf_message_actions_users.value`, `2026_09_29_000100_create_tf_mirror_gap_tables.php:215`).
     *
     * So skipping the element with a clue would discard data the schema is
     * already shaped to hold — a silent loss wearing a clue as a disguise. The
     * value is wrapped under the value column and the row places with NO clue:
     * a filled users list is a fully modelled fact, and a clue per member of
     * every group-create action Telegram ever sends is exactly the ledger noise
     * the `BARE_DISCRIMINATOR_TABLES` note above this class rejects.
     *
     * The `1:N child` DECLARATION is therefore correct and is left alone. The
     * resolver and the DDL agree completely; this was the only layer that could
     * not cope.
     *
     * ## WHY THE POSITION IS NOT RENUMBERED AROUND A HOLE
     *
     * `$position` is the element's ORDINAL IN THE WIRE LIST and it is part of
     * the child's primary key. Re-numbering after a dropped element would make
     * the surviving rows describe a list Telegram never sent, silently and
     * plausibly. So a hole stays a hole and the clue below reports it — the row
     * that is missing is exactly the row the operator cannot otherwise see.
     *
     * @param  mixed  $item  the raw wire element: a payload object, a scalar, or neither
     * @param  list<string>  $clues
     * @return array<string, mixed>|null the item as `childRow()` can take it, or null when it cannot be placed
     */
    private function childElement(MirrorTable $child, string $plain, mixed $item, int $position, array &$clues): ?array
    {
        if (is_array($item)) {
            return $item;
        }

        $valueColumn = $this->scalarChildValueColumn($child);

        if ($valueColumn !== null
            && (is_int($item) || is_float($item) || is_string($item) || is_bool($item))
        ) {
            return [$valueColumn => $item];
        }

        $clues[] = "{$child->tfName}: vector element {$position} of '{$plain}' is neither a payload object "
            .'nor a storable scalar (type '.get_debug_type($item).') — cannot place the fact; the '
            ."{$child->tfName} row at position {$position} is not written and the remaining elements keep "
            .'their wire ordinals, so the list has a hole where this element was';

        return null;
    }

    /**
     * The ONE column a scalar-vector child's element value belongs in, or null
     * when there is no single such column.
     *
     * A scalar-vector child is built by `resolveVectorFromType()` as the parent
     * key, a `position` ordinal, and exactly one value column — so the
     * candidate set is the columns that are none of those, and it has exactly
     * one member. MEASURED 2026-10-04 against the real `TlScheme` and the real
     * resolver, the two scalar-vector children the shipped catalogue DECLARES
     * resolve to (parent key columns in brackets):
     *
     *   tf_message_actions_users
     *     cols=[account_id, peer_type, peer_id, message_action_id, position, value]
     *     keyColumns=[peer_type, peer_id, message_action_id]
     *   tf_saved_star_gifts_collection_id
     *     cols=[account_id, id, position, value]
     *     keyColumns=[id]
     *
     * The single candidate is `value` in BOTH, matching `scalarValueColumn()`
     * (src/Schema/Mirror/MirrorTableResolver.php:534-545). The first row is
     * why the `account_id` skip below is not redundant: `account_id` is in
     * NEITHER keyColumns list — a child inherits its PARENT's key columns, and
     * the parent key here is peer-scoped — so only the explicit name check
     * keeps it out of the candidate set. Asserted by execution, not narrated,
     * in
     * `tests/Schema/Mirror/ScalarVectorChildElementTest::test_a_scalar_vector_child_has_exactly_one_value_column`.
     *
     * The null return is load-bearing, not defensive padding: a union child
     * (one built by `unionDecompose()`) can have MORE than one non-key column,
     * and for such a child the first one is NOT where a scalar element goes.
     * MEASURED: `tf_attach_menu_bots_peer_types` is a union child whose only
     * non-key column is `constructor`, so an un-guarded first-candidate pick
     * would file a message id under a discriminator. Wrapping under a guess
     * would be the silent-corruption failure this class is written against, so
     * an ambiguous child takes the clue in `childElement()` instead.
     */
    private function scalarChildValueColumn(MirrorTable $child): ?string
    {
        $candidates = [];
        foreach ($child->columns as $column) {
            if ($column->name === 'account_id' || $column->name === 'position') {
                continue;
            }
            if (in_array($column->name, $child->keyColumns, true)) {
                continue;
            }
            $candidates[] = $column->name;
        }

        return count($candidates) === 1 ? $candidates[0] : null;
    }

    /**
     * @param  int|null  $position  set for positioned (vector) children
     * @param  array<string, mixed>  $item  nested payload for this child
     * @param  list<string>  $clues
     * @param  array<string, mixed>  $parentPayload  the PARENT fact's own payload — the peer source
     * @return array<string, int|string|null>
     */
    private function childRow(MirrorTable $child, int $accountId, array $parentRow, ?int $position, array $item, array &$clues, array $parentPayload = []): array
    {
        $row = ['account_id' => $accountId];
        if ($child->constructor !== '') {
            $row['constructor'] = (string) ($item['_'] ?? $child->constructor);
        } elseif (in_array($child->tfName, self::BARE_DISCRIMINATOR_TABLES, true)) {
            // The shipped DDL declares a BARE discriminator this table's model
            // never accounted for, so the row must carry a value or MySQL strict
            // answers 1364 and the whole page's transaction rolls back.
            //
            // The child's OWN ctor wins: naming the union member that produced
            // the row is exactly what a discriminator is for
            // (`chatBannedRights`, `username`, `notifySettings`). The parent's
            // ctor is the fallback and is never empty — `decompose()` checked it
            // against the catalog's ctor list before any child was reached. An
            // EMPTY string is deliberately never written: a blank discriminator
            // satisfies neither MySQL nor a reader, so when neither ctor is
            // nameable the column is left off and the insert fails visibly —
            // the same "never fabricate" choice `placeRow()` makes for a primary
            // key it cannot cover.
            $ctor = (string) ($item['_'] ?? ($parentPayload['_'] ?? ''));
            if ($ctor !== '') {
                $row['constructor'] = $ctor;
            }
        }

        foreach ($child->columns as $column) {
            if ($column->name === 'account_id') {
                continue;
            }
            if ($column->name === 'position') {
                $row[$column->name] = $position;

                continue;
            }
            if ($column->peer) {
                // The child's OWN payload first (a nested object that carries its
                // own peer — `fwd_from`'s MessageFwdHeader.from_id), then the
                // parent fact's payload: a peer-declared CHILD is materialized
                // from a peer field that lives on the PARENT wire object
                // (`message.from_id` → `tf_messages_from_id`), so `$item` alone
                // is that peer value and never contains the field under its own
                // name. Probing only `$item` is what silently zeroed every
                // depth-1 peer child row.
                $this->fillPeerHalf($column, $item, $row, $clues, $child->tfName, $parentPayload);

                continue;
            }
            if (in_array($column->name, $child->keyColumns, true)) {
                // Parent key travels into the child row — the FK that links them.
                if (array_key_exists($column->name, $parentRow)) {
                    $row[$column->name] = $parentRow[$column->name];
                } else {
                    $clues[] = "{$child->tfName}: parent key column '{$column->name}' missing from parent row — cannot link FK";
                }

                continue;
            }
            if ($column->name === 'constructor') {
                continue;
            }
            if (in_array($column->name, $child->booleanColumns, true)) {
                $row[$column->name] = (int) (bool) ($item[$column->name] ?? false);

                continue;
            }
            if (array_key_exists($column->name, $item)) {
                $row[$column->name] = $this->castValue($item[$column->name], $column, $clues, $child->tfName, $column->name);
            }
        }

        return $row;
    }

    /** A structurally-valid vector payload: list of arrays, or associative with numeric keys. */
    private function isVectorList(array $value): bool
    {
        foreach (array_keys($value) as $k) {
            if (! is_int($k)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<mixed> */
    private function vectorItems(array $value): array
    {
        return array_values($value);
    }

    /**
     * Fill one half of a peer pair. A peer column pair ({field}_type + {field}_id,
     * both peer=true) reads from the single payload field {field}: the wire
     * ctor object (peerUser/peerChat/peerChannel + their id field — the TL
     * wire shape) OR an already canonical pair ['_type'|'type' => int,
     * '_id'|'id' => int]. PeerShapeTool normalizes both to the canonical
     * _type/_id pair (spec enum 1=user 2=chat 3=channel). Each half writes
     * only its own column so the (account_id, key) row stays complete.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, int|string|null>  $row
     * @param  list<string>  $clues
     * @param  array<string, mixed>  $peerPayload  SECOND source probed only when
     *                                             $payload carries no peer field — the parent fact's payload, for a
     *                                             child row whose peer column names a field of the PARENT object.
     */
    private function fillPeerHalf(MirrorColumn $column, array $payload, array &$row, array &$clues, string $tfName, array $peerPayload = []): void
    {
        $isType = str_ends_with($column->name, '_type');
        $field = $isType
            ? substr($column->name, 0, -5)
            : substr($column->name, 0, -3);

        // Canonical peer mapping: the curated peer pair `peer_type`/`peer_id`
        // encodes a TL `Peer` field, but the wire does not spell that field
        // one way. `Message` carries it as `peer_id`; `Dialog`, `SavedDialog`
        // and `StarsSubscription` carry it as `peer` (TL 852, 1807, 1966).
        // The stripped column stem is therefore NOT a reliable wire key on its
        // own, and hard-mapping it to `peer_id` made every `peer`-named carrier
        // look absent — the row never placed, and the only symptom was a
        // "peer column missing" clue. Probe both spellings and take the one the
        // payload actually carries; the column pair is unchanged either way.
        $candidates = $field === 'peer' ? ['peer', 'peer_id'] : [$field, $field === 'peer_id' ? 'peer' : $field];
        $value = null;
        foreach ([$payload, $peerPayload] as $source) {
            foreach ($candidates as $candidate) {
                if (isset($source[$candidate]) && is_array($source[$candidate])) {
                    $value = $source[$candidate];
                    $field = $candidate;
                    break 2;
                }
            }
        }

        if (! is_array($value)) {
            $clues[] = "{$tfName}: peer column '{$candidates[0]}' missing from payload — cannot place the fact";

            return;
        }

        [$type, $id] = PeerShapeTool::normalize($value);

        // Unresolvable peers surface as an ingest clue (one per pair, from the
        // type half) — the "FK failed → wrong ingest path" signal — instead of
        // silently writing 0/0 into the mirror. Peer ids are never 0 (PeerIdTool
        // ranges: user > 0, chat < 0, channel << 0).
        if ($type === 0 || $id === 0) {
            if ($isType) {
                $clues[] = "{$tfName}: peer '{$field}' not resolvable to a canonical peer — cannot place the fact";
            }

            return;
        }

        $row[$column->name] = $isType ? $type : $id;
    }

    /** @param list<string> $clues */
    private function castValue(mixed $value, MirrorColumn $column, array &$clues, string $tfName, string $field): int|string|null
    {
        if (is_int($value) || is_float($value)) {
            return (int) $value;
        }
        if (is_string($value)) {
            // `bytes` is the one TL type that is NOT text, and this is the one
            // place that knows it. See encodeBytes() for why the mirror stores
            // it as hex; the short form is that a bytes column ships as TEXT
            // and raw binary in a TEXT column is rejected outright by MySQL in
            // strict mode, which aborts the whole ingest transaction — one
            // photo on a page rolls back every row on it.
            return $column->hex ? $this->encodeBytes($value) : $value;
        }
        if (is_bool($value)) {
            return (int) $value;
        }

        $clues[] = "{$tfName}: column '{$field}' got an uncastable value (type ".get_debug_type($value).')';

        return null;
    }

    /**
     * Encode a TL `bytes` value for the shipped TEXT column that holds it.
     *
     * ## The defect this closes
     *
     * A TL `bytes` field arrives from the decoder as a RAW BINARY PHP string,
     * but every bytes column in the shipped DDL is declared TEXT — e.g.
     * `tf_chats_photo.stripped_thumb` at
     * `src/Laravel/Migrations/2026_09_14_200002_create_tf_chats_tables.php`:
     * `$table->text('stripped_thumb')`. MySQL in strict mode refuses binary in
     * a TEXT column (SQLSTATE 1366, "Incorrect string value"), and because the
     * mirror write is wrapped in `$db->transaction(...)`
     * (`MirrorIngesterFactory::buildResultRoute()`) that single failure rolled
     * back every OTHER row on the page too — observed live as `tf_users`
     * landing while `tf_chats` stayed at 0.
     *
     * So the bytes/text distinction is NOT cosmetic and the next reader must
     * not "simplify" it away: `stripped_thumb` and `title` are both TEXT
     * columns, and only one of them may be written as it arrived.
     *
     * ## Why HEX, and not base64
     *
     * Not a preference — three artefacts of this repo already decided it and
     * they agree:
     *
     *  1. NF5 §8 (`docs/superpowers/specs/2026-09-11-telegram-mirror-schema-nf5-design.md`)
     *     states it for the 79 bytes fields in the mirror: "stored hex-encoded
     *     as plain VARCHAR … the MTProto serialization layer calls `hex2bin()`
     *     … No binary column, no base64 bloat — hex is exactly 2× the bytes,
     *     reversible with one stdlib call."
     *  2. `MirrorColumn::$hex` and `MirrorTable::$hexColumns` are already named
     *     for hex, and `MirrorTableResolver::isBytesBaseField()` says so in its
     *     own docblock: "stored as hex-encoded VARCHAR/TEXT (NF5 §8) — the
     *     column must carry the hex marker so ingest encodes". THIS method is
     *     the "so ingest encodes" half that was never written.
     *  3. The base64 note on the generated DTOs
     *     (`src/Schema/Generated/Data/Types/ChatPhotoData.php` — "bytes params
     *     carried as base64 strings") is a docblock annotation on a different
     *     layer. No `base64_encode()` anywhere in the repo encodes a mirror
     *     value; the real call sites are `SessionData` (wire transport),
     *     `TelegramVault` (sealed blobs) and `CallbackData` (callback payload).
     *     Following it would have left `hexColumns` a lie.
     *
     * ## Exactly once, never conditionally
     *
     * There is deliberately NO "does this already look encoded?" guard, because
     * such a guard cannot be written safely: the base64 and hex alphabets are
     * subsets of arbitrary binary. The bytes `41 42` are the ASCII string
     * `"AB"`, which is valid hex, and `2f 39 41` is `"/9A"`, which is valid
     * base64 — so a heuristic would leave THAT raw binary in the column,
     * reinstating the very MySQL 1366 above, but only for payloads that happen
     * to look encoded. Data-dependent, rare, invisible.
     *
     * Encoding unconditionally is also the only LOSSLESS choice: an
     * already-encoded input would come out as `hex(hex(x))`, which is
     * inelegant but perfectly decodable, whereas skipping a value that was not
     * encoded destroys it.
     *
     * ## The round trip
     *
     * `bin2hex()` here is the ONLY encode; `hex2bin()` at the MTProto call
     * site (NF5 §8) is the only decode. `tests/Mirror/MirrorBytesColumnEncodingTest.php`
     * pins both directions against the real shipped migration.
     *
     * ## Already-stored data
     *
     * On MySQL strict, any row that carried raw binary was never written (the
     * transaction rolled back), so there is nothing to migrate. A lenient
     * driver would have stored raw binary in a TEXT column; re-ingesting from
     * Telegram repairs those rows idempotently by primary key, which is
     * preferable to a blanket back-fill — `hex2bin()` on an already-hex value
     * is a corruption, so no safe automatic migration exists.
     */
    private function encodeBytes(string $bytes): string
    {
        return bin2hex($bytes);
    }
}
