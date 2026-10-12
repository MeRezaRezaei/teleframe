<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use PHPUnit\Framework\TestCase;

/**
 * THE GATE: a message's identity is (account_id, peer_type, peer_id, id) —
 * never (account_id, id).
 *
 * ## ⚠️ THIS TEST IS CURRENTLY RED — IT IS A SPECIFICATION, NOT A PASSING GATE
 *
 * Status as of 2026-10-02: **RED (2 failures, 1 error).** The production work it
 * specifies has NOT landed. It is kept here deliberately, as the executable
 * specification for a multi-pass fix, and it must NOT be deleted to make a suite
 * green or "fixed" by weakening its assertions.
 *
 * Why it is still red — the scope, measured: widening `tf_messages`' primary key
 * alone is NOT safe, because of `tf_messages`' 34 children only 5 carry a peer
 * column at all (`tf_messages_fwd_from`, `tf_messages_reply_to`,
 * `tf_messages_service`, `tf_messages_service_reply_to`, and the parent). The
 * other ~29 have **no column to resolve `id` with**, so a widened parent with
 * unwidened children produces children whose `id` cannot resolve to any parent
 * row. Each of those needs two new columns plus a hand-authored DDL key change —
 * several passes, not one.
 *
 * The repository already contains the correct answer in its own generator,
 * disabled: `src/Schema/Generator/MigrationGenerator.php:196` emits
 * `$table->unique(['peer_id','message_id','account_id'],'ux_tf_messages_scope')` —
 * per-PEER. It is off per the operator's verbatim "i dont want the auto
 * generation any more" (`tests/Schema/ShipDialGoldenTest.php:9-14`).
 *
 * ## Why this file exists — the measurement, not the theory
 *
 * Telegram numbers messages PER CHAT. `dialog.top_message` is the highest
 * message id in that dialog and ids are dense per-dialog sequences starting
 * at 1, so `id=1`, `id=5` and `id=100` each exist in essentially EVERY dialog
 * of a real account. Measured on the operator's live database: 136 dialogs
 * carry a message with id=1, 135 with id=5, 135 with id=100; sum(top_message)
 * across those dialogs is 204,138,975 messages while max(top_message) is only
 * 38,826,515 — i.e. only that many (account_id, id) key slots exist, so
 * 165,312,562 messages (80.98%) would overwrite one another.
 *
 * The write path agrees with the wire: `messages.getHistory#4423e6c5` takes a
 * MANDATORY `peer:InputPeer` and an `offset_id` that is a peer-relative
 * cursor (`api_full.tl:2432`), and `message#7600b9d3` carries `id:int` AND
 * `peer_id:Peer` in the SAME constructor. If ids were globally unique, `peer:`
 * would be decoration. It is not decoration; it is half the address.
 *
 * ## The failure mode this replaces is SILENT
 *
 * `UpdateIngestor::writeMessage()` upserts, so a collision is not an error —
 * the second message's body, date and flags silently REPLACE the first's, and
 * a peer-scoped read (`where id < N order by id desc`, the per-peer page
 * MessageQuery issues) then returns whichever dialog wrote last. Nothing
 * raises: no SQLSTATE, no migrate-time signal, no startup signal. That is why
 * this is asserted against the SHIPPED DDL by executing the migration rather
 * than by re-typing its columns — a hand-transcribed copy of the key is a
 * second copy that drifts, which is the exact disease
 * `tests/Store/ShippedStoreSchema.php` was written to kill.
 *
 * ## Which tables are asserted, and why the set is derived
 *
 * The set below is read from the migration's own `Schema::create` +
 * `primary([...])` declarations, so a table cannot be widened here without this
 * test seeing it, and a table that stops being peer-scoped cannot linger in
 * the set. `assertPeerScopedTables()` is the single place that decides the
 * rule; the child-shape reasoning it encodes is documented on
 * self::PARENT_SCOPED / self::PARENT_INHERITED below.
 *
 * Provenance: src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php
 * (the two parent surfaces and their children),
 * src/Teleframe/Ingest/UpdateIngestor.php::writeMessage (the upsert key),
 * docs/superpowers/specs/2026-09-11-telegram-mirror-schema-nf5-design.md §7
 * (the keying rule this corrects).
 */
final class MessagePeerScopedPrimaryKeyTest extends TestCase
{
    /**
     * The two PARENT message surfaces. Both are keyed on the full peer-scoped
     * address because both hold Telegram messages, and both number them per
     * chat.
     */
    private const PARENT_TABLES = [
        'tf_messages',
        'tf_messages_service',
    ];

    /**
     * The 1:1 and 1:N CHILD tables that hang off a message and are therefore
     * peer-scoped too.
     *
     * WHY THEY MUST CARRY THE PEER, per shape — this is the part that must not
     * be blanket-applied, so each shape is reasoned rather than assumed:
     *
     *  - 1:1 children (`tf_messages_from_id`, `tf_messages_reply_to`,
     *    `tf_messages_views`, `tf_messages_forwards`, …). "1:1" describes the
     *    CARDINALITY of message→child, not the child's identity. A child row is
     *    a FACT ABOUT one message; once the parent's identity is four columns,
     *    "the from_id of message 5" is ambiguous — there is one in every
     *    dialog — so a child keyed (account_id, id) would still collapse peer
     *    A's author onto peer B's. The parent's id is unique only WITHIN the
     *    parent's key; a child that drops half of it is not "already unique".
     *
     *  - 1:N children (`tf_messages_entities`, `tf_messages_restriction_reason`,
     *    `tf_messages_reply_markup_rows`, …). Here the fact is a VECTOR, and
     *    `position` disambiguates the elements — but position disambiguates
     *    them WITHIN one message, i.e. within one (account_id, peer_type,
     *    peer_id, id). Element 0 of the entity vector of message 5 in dialog A
     *    is a different entity from element 0 of message 5 in dialog B, and
     *    both sit at position 0. So position is necessary and NOT sufficient.
     *
     *  - `tf_messages_reply_markup_rows_buttons` carries FOUR coordinates
     *    (account_id, peer_type, peer_id, id, position, parent_position) for
     *    the same reason its own comment gives: a button's identity is its
     *    position within a row, WHICH row (parent_position), the tenant, AND
     *    now the peer the message lives in.
     *
     * The invariant across all three shapes: a child's key must be at least as
     * specific as its parent's, because a child cannot exist without its
     * parent (`decomposeChildren()` links them; there is no orphan fact).
     */
    private const CHILD_TABLE_PREFIXES = ['tf_messages_'];

    private const MESSAGES_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php';

    /**
     * PROOF 1 (RED, historical): with the pre-fix key the two messages below
     * collapsed into ONE row.
     *
     * Asserted as a COLLISION (the row count and the overwritten body), not as
     * the absence of an exception — `updateOrInsert` never raises on this
     * defect, which is precisely why it survived. The literal is expressed
     * against the SHIPPED DDL, so after the fix this test is not merely
     * inverted but replaced by `test_two_peers_with_the_same_id_are_two_rows`.
     */
    public function test_the_pre_fix_key_collapses_two_peers_messages_into_one_row(): void
    {
        $db = $this->shippedMessagesSchema();

        // Exactly what writeMessage() issued under the old key.
        foreach ([['peer_type' => 3, 'peer_id' => -100123, 'body' => 'from channel A'], ['peer_type' => 3, 'peer_id' => -100999, 'body' => 'from channel B']] as $m) {
            $db->table('tf_messages')->updateOrInsert(
                ['account_id' => 1, 'id' => 5],
                ['peer_type' => $m['peer_type'], 'peer_id' => $m['peer_id'], 'constructor' => 'message', 'message' => $m['body']],
            );
        }

        $rows = $db->table('tf_messages')->where('account_id', 1)->where('id', 5)->get();
        self::assertCount(
            1,
            $rows,
            'the pre-fix key no longer collapses the collision — either the PK was widened (correct, and '
            .'test_two_peers_with_the_same_id_are_two_rows is now the live gate) or this fixture stopped '
            .'modelling what the old write path actually issued',
        );
        self::assertSame(
            'from channel B',
            $rows->first()->message,
            'the surviving row is the SECOND write: peer A\'s message body was silently overwritten',
        );
    }

    /**
     * PROOF 2 (GREEN): two peers, same `id`, TWO rows — and the VALUES are
     * asserted, not just the count, because a wrong `peer_type`/`peer_id` on
     * either row is the same corruption wearing a different hat.
     *
     * FIXTURE CORRECTION (2026-10-03, Task 2.2). The expected array is SORTED
     * before the comparison, and the three expectations in it are untouched.
     * The literal is written in magnitude order — -500, then -100123, then
     * -100999 — which is how the eye reads the fixture, but it is not what any
     * ascending sort produces: -100999 < -100123, because they are negative.
     * No ORDER BY can rescue it, so the ordering was an incidental assumption
     * standing in for the claim. The claim is SET membership: these three
     * (peer_type, peer_id, body) triples exist, one row each. The query still
     * orders deterministically — without it, row order would be whatever the
     * storage engine felt like — and both sides are then sorted by the same
     * comparison, so a wrong peer_type, a swapped peer_id or a wrong body all
     * still fail exactly as before.
     */
    public function test_two_peers_with_the_same_id_are_two_rows_with_their_own_peer(): void
    {
        $db = $this->shippedMessagesSchema();

        $this->writeMessage($db, ['peer_type' => 3, 'peer_id' => -100123, 'body' => 'from channel A']);
        $this->writeMessage($db, ['peer_type' => 3, 'peer_id' => -100999, 'body' => 'from channel B']);
        $this->writeMessage($db, ['peer_type' => 2, 'peer_id' => -500, 'body' => 'from group chat']);

        $rows = $db->table('tf_messages')->where('account_id', 1)->where('id', 5)
            ->orderBy('peer_type')->orderBy('peer_id')->get();
        self::assertCount(3, $rows, 'three peers each holding message id 5 must be three rows');

        $seen = [];
        foreach ($rows as $row) {
            $seen[] = [(int) $row->peer_type, (int) $row->peer_id, (string) $row->message];
        }
        $expected = [
            [2, -500, 'from group chat'],
            [3, -100123, 'from channel A'],
            [3, -100999, 'from channel B'],
        ];
        sort($seen);
        sort($expected);

        self::assertSame($expected, $seen, 'each row must carry ITS OWN peer and ITS OWN body — a 0/0 or swapped peer is the same corruption');
    }

    /**
     * The re-ingest half: the SAME message arriving twice is one row, not two.
     *
     * A widened key is only correct if it is still idempotent — otherwise the
     * fix trades silent data loss for silent row duplication, which is the
     * other half of the same mirror invariant (`row existence = fact
     * existence`, and the bus redelivers).
     */
    public function test_the_same_message_re_ingested_is_still_one_row(): void
    {
        $db = $this->shippedMessagesSchema();

        $this->writeMessage($db, ['peer_type' => 3, 'peer_id' => -100123, 'body' => 'first']);
        $this->writeMessage($db, ['peer_type' => 3, 'peer_id' => -100123, 'body' => 'edited later']);

        $rows = $db->table('tf_messages')->where('account_id', 1)->where('id', 5)->get();
        self::assertCount(1, $rows, 're-ingesting the same message must upsert, not duplicate');
        self::assertSame('edited later', $rows->first()->message, 'the upsert must still take the newer body');
    }

    /**
     * PROOF 3: every peer-scoped table this migration declares is asserted to
     * hold two coexisting rows, table-driven off the migration's own
     * declarations so the set cannot drift from the DDL.
     *
     * Both parents and every child are exercised, because the child reasoning
     * on this class is the part a blanket rule gets wrong: a child whose parent
     * is peer-scoped cannot be narrower than its parent.
     *
     * FIXTURE CORRECTION (2026-10-03, Task 2.2) — and the ASSERTION IS UNCHANGED,
     * because the row the loop writes is now shaped from the table's OWN live
     * columns instead of from a hardcoded seed. Two defects in the old seed were
     * latent only while the parent was still wrong, and this loop asserts on
     * `tf_messages` FIRST and threw there, so neither had ever executed:
     *
     *  1. It seeded `'constructor' => 'message'` unconditionally. Only 4 of the
     *     36 message tables have a `constructor` column — the two parents and
     *     the two reply-header children — because `constructor` is the union
     *     DISCRIMINATOR and a table over a single non-union TL type has none.
     *     `tf_messages_from_id` and its 30 siblings therefore failed the insert
     *     outright: "table tf_messages_from_id has no column named constructor".
     *     A column cannot be conjured into the schema to satisfy a fixture; the
     *     fixture is what was wrong about the table.
     *  2. It supplied only the key columns, so every NOT NULL column the DDL
     *     declares WITHOUT a default and does not name in the key — `date` on
     *     `tf_messages_fwd_from`, `text`/`reason` on `tf_messages_post_author`
     *     and `tf_messages_from_rank`, `hash` on `tf_messages_factcheck`, and
     *     the rest — would have failed the insert as NOT NULL violations. That
     *     is precisely the loud-failure posture the migration's peer columns now
     *     rely on, so the fixture has to model it: the real writers feed those
     *     columns with type-shaped sentinels, and so does this.
     *
     * `liveColumns()` reads the column list, NOT NULL flag and declared type
     * from the BUILT schema, so the fixture cannot drift from the DDL in the
     * way a hardcoded list would. The assertion is untouched, and one was
     * ADDED: each table's key must carry both peer halves, which is the claim
     * the whole sweep exists to make.
     */
    public function test_every_peer_scoped_message_table_holds_two_peers_rows(): void
    {
        $db = $this->shippedMessagesSchema();
        $tables = $this->peerScopedTables();

        self::assertNotSame([], $tables, 'no peer-scoped table was discovered in the shipped migration — the discovery below has rotted');

        foreach ($tables as $table => $keyColumns) {
            $columns = $this->liveColumns($db, $table);
            self::assertNotSame([], $columns, "{$table}: the migration declared it but the built schema has no columns — the read below would pass vacuously");

            foreach ([[3, -100123], [3, -100999]] as [$peerType, $peerId]) {
                $row = [];
                foreach ($keyColumns as $col) {
                    $row[$col] = match ($col) {
                        'account_id' => 1,
                        'peer_type' => $peerType,
                        'peer_id' => $peerId,
                        'id' => 5,
                        'position' => 0,
                        'parent_position' => 0,
                        'token' => 'tok',
                        default => 1,
                    };
                }
                // The discriminator where the table has one, and a type-shaped
                // sentinel for every other NOT NULL column the DDL declares
                // without a default — see the FIXTURE CORRECTION above.
                if (isset($columns['constructor'])) {
                    $row['constructor'] = 'message';
                }
                foreach ($columns as $col => $info) {
                    if (array_key_exists($col, $row) || $info['default'] !== null || $info['notnull'] !== 1) {
                        continue;
                    }
                    $row[$col] = $this->sentinelFor($info['type']);
                }

                // Only columns the table really has, so a table that gained or
                // lost a column fails on the ASSERTION below rather than on an
                // unknown-column SQLSTATE from the fixture.
                $row = array_intersect_key($row, $columns);

                $db->table($table)->updateOrInsert(
                    array_intersect_key($row, array_flip($keyColumns)),
                    $row,
                );
            }

            foreach (['peer_type', 'peer_id'] as $half) {
                self::assertContains(
                    $half,
                    $keyColumns,
                    "{$table}: its declared key is ".json_encode($keyColumns).", which omits {$half}. This sweep writes two peers' rows and "
                    .'expects both to survive, so a key without the peer halves is the parent\'s original defect, one level down.',
                );
            }

            $stored = $db->table($table)->where('account_id', 1)->where('id', 5)->get();
            $peers = [];
            foreach ($stored as $r) {
                $peers[] = [(int) $r->peer_type, (int) $r->peer_id];
            }
            sort($peers);

            // FIXTURE CORRECTION (2026-10-03, Task 2.2) — the expected pair is
            // sorted TOO, and the assertion itself is unchanged in strength.
            // `sort()` compares the tuples element-wise and NUMERICALLY, so
            // [3,-100123] sorts after [3,-100999]; the literal was written in
            // magnitude order (123 before 999), which is readable but is not
            // what a numeric sort emits. Sorting both sides states the claim
            // this test actually makes — the SET of peers that hold a row is
            // exactly these two — instead of leaving an incidental ordering
            // assumption standing in for it. Both halves of the pair are still
            // compared: a row carrying peer_type 3 / peer_id -100123 is a
            // different assertion from one carrying 3 / -100999.
            $expected = [[3, -100123], [3, -100999]];
            sort($expected);

            self::assertSame(
                $expected,
                $peers,
                "{$table}: two peers' rows must coexist. Its declared key is "
                .json_encode($keyColumns).' — a key missing peer_type/peer_id makes this table '
                .'collide across dialogs exactly as the parent did.',
            );
        }
    }

    /**
     * The child tables must not be left behind by a parent-only fix.
     *
     * A separate assertion from the table-driven sweep above, because it names
     * the specific regression: someone widens the two parents (the measured
     * 80.98% data loss) and stops there, leaving 31 children to overwrite each
     * other across peers — so the parent is right and every fact hanging off it
     * is still wrong. If this fails, the fix is incomplete, not absent.
     */
    public function test_no_message_child_table_is_left_narrower_than_its_peer_scoped_parent(): void
    {
        $db = $this->shippedMessagesSchema();
        $narrow = [];
        $mismatched = [];

        foreach ($this->childTables() as $table => $declared) {
            $actual = $this->livePrimaryKey($db, $table);
            if ($actual !== $declared) {
                // The migration and the database disagree about this table's
                // identity. Recorded separately so a parse that drifts from
                // the DDL cannot masquerade as a keying verdict.
                $mismatched[$table] = ['declared' => $declared, 'live' => $actual];
            }
            foreach (['peer_type', 'peer_id'] as $half) {
                if (! in_array($half, $actual, true)) {
                    $narrow[$table] = $actual;
                }
            }
        }

        self::assertSame(
            [],
            $mismatched,
            'the primary key this test read from the migration source differs from the one the '
            .'migration actually built — the declared/live comparison below would be meaningless',
        );

        self::assertSame(
            [],
            $narrow,
            'message child tables whose PRIMARY KEY omits peer_type/peer_id. A child cannot exist '
            ."without its parent, and the parent's identity is (account_id, peer_type, peer_id, id), "
            .'so a narrower child key re-collapses the same facts across peers. '
            .'Declared-vs-live mismatches here mean the migration and the database disagree.',
        );
    }

    /**
     * PROOF 7: the migration is IDEMPOTENT — re-running `up()` from scratch
     * yields byte-identical schema, so `migrate:fresh` twice is the same
     * schema. The repo's regeneration dial stamps generated artifacts; this
     * migration is hand-authored (bin/regenerate no longer emits migrations —
     * see ShipDialGoldenTest), so the invariant it owes is that its declared
     * shape is a function of the file alone, not of run order.
     */
    public function test_the_messages_migration_produces_an_identical_schema_on_a_fresh_run(): void
    {
        $first = $this->schemaSignature($this->shippedMessagesSchema());
        $second = $this->schemaSignature($this->shippedMessagesSchema());

        self::assertSame(
            $first,
            $second,
            'two fresh runs of the shipped messages migration produced different schemas — '
            .'the file is not the only source of its shape',
        );
        self::assertNotSame([], $first, 'no schema was read — the assertion above would pass vacuously');
    }

    /**
     * PROOF 4 (cost): promoting the per-peer index to the PK must not leave a
     * REDUNDANT duplicate index behind.
     *
     * The migration already carried a NON-UNIQUE index on exactly
     * (account_id, peer_type, peer_id, id) for the per-peer read path. Once
     * that column list IS the primary key, a second index on the same columns
     * is pure write amplification on the hottest table in the mirror — every
     * upsert maintaining two identical B-trees. This asserts the claim the
     * report makes rather than repeating it.
     */
    public function test_the_per_peer_index_is_not_left_duplicated_by_the_primary_key(): void
    {
        $db = $this->shippedMessagesSchema();

        $indexes = [];
        foreach ($db->select("PRAGMA index_list('tf_messages')") as $row) {
            $row = (array) $row;
            $cols = [];
            foreach ($db->select('PRAGMA index_info('.$db->getPdo()->quote((string) $row['name']).')') as $info) {
                $info = (array) $info;
                $cols[(int) $info['seqno']] = (string) $info['name'];
            }
            ksort($cols);
            $indexes[] = ['name' => (string) $row['name'], 'unique' => (int) $row['unique'] === 1, 'cols' => array_values($cols)];
        }

        $shapes = [];
        foreach ($indexes as $index) {
            $shapes[implode(',', $index['cols'])][] = $index['unique'] ? 'unique' : 'non-unique';
        }

        foreach ($shapes as $cols => $kinds) {
            self::assertCount(
                1,
                $kinds,
                "tf_messages carries MORE THAN ONE index on ({$cols}) — kinds: ".json_encode($kinds)
                .'. Once the per-peer column list IS the primary key, a second index on the same '
                .'columns doubles write cost on the hottest table in the mirror for no read benefit.',
            );
        }
    }

    /**
     * Every `Schema::create` table in the messages migration that is a message
     * surface, mapped to its DECLARED primary key.
     *
     * @return array<string, list<string>>
     */
    private function peerScopedTables(): array
    {
        $tables = [];
        foreach ($this->declaredTables() as $table => $keyColumns) {
            $isParent = in_array($table, self::PARENT_TABLES, true);
            $isChild = false;
            foreach (self::CHILD_TABLE_PREFIXES as $prefix) {
                if (str_starts_with($table, $prefix)) {
                    $isChild = true;
                }
            }
            if ($isParent || $isChild) {
                $tables[$table] = $keyColumns;
            }
        }

        return $tables;
    }

    /**
     * @return array<string, list<string>>
     */
    private function childTables(): array
    {
        $children = [];
        foreach ($this->peerScopedTables() as $table => $keyColumns) {
            if (in_array($table, self::PARENT_TABLES, true)) {
                continue;
            }
            $children[$table] = $keyColumns;
        }

        return $children;
    }

    /**
     * Parse the migration's own `Schema::create` + `primary([...])` pairs.
     *
     * Read from SOURCE rather than from a built schema on purpose for the
     * discovery half: `PRAGMA` reports only what the last run produced, so it
     * cannot tell a table that was never widened from one that does not exist.
     * The DECLARED set is cross-checked against the LIVE schema by
     * `test_no_message_child_table_is_left_narrower_than_its_peer_scoped_parent`
     * (which also asserts the two agree), so a parse that silently misses a
     * table fails there instead of passing quietly here.
     *
     * @return array<string, list<string>>
     */
    private function declaredTables(): array
    {
        $body = (string) file_get_contents(self::MESSAGES_MIGRATION);

        $tables = [];
        $offset = 0;
        while (($start = strpos($body, "Schema::create('", $offset)) !== false) {
            $start += strlen("Schema::create('");
            $end = strpos($body, "'", $start);
            if ($end === false) {
                break;
            }
            $name = substr($body, $start, $end - $start);
            $offset = $end;

            $next = strpos($body, "Schema::create('", $offset);
            $block = $next === false ? substr($body, $offset) : substr($body, $offset, $next - $offset);

            $tables[$name] = $this->declaredPrimaryKeyColumns($block);
        }

        return $tables;
    }

    /**
     * The declared primary key columns of one `Schema::create` block.
     *
     * Handles BOTH spellings the file uses: the inline
     * `$table->primary(['a', 'b']);` and the named
     * `$table->primary([...], 'some_name');` that the reply-markup buttons
     * block needs because Laravel's generated name exceeds the 64-character
     * identifier limit MySQL and Postgres silently truncate at.
     *
     * @return list<string>
     */
    private function declaredPrimaryKeyColumns(string $block): array
    {
        $marker = '$table->primary(';
        $start = strpos($block, $marker);
        if ($start === false) {
            return [];
        }
        $start += strlen($marker);
        $open = strpos($block, '[', $start);
        if ($open === false) {
            return [];
        }

        $cols = [];
        $depth = 0;
        $quote = null;
        for ($i = $open, $len = strlen($block); $i < $len; $i++) {
            $ch = $block[$i];
            if ($quote !== null) {
                if ($ch === $quote) {
                    $quote = null;
                }

                continue;
            }
            if ($ch === "'" || $ch === '"') {
                $quote = $ch;

                continue;
            }
            if ($ch === '[') {
                $depth++;

                continue;
            }
            if ($ch === ']') {
                $depth--;
                if ($depth === 0) {
                    break;
                }

                continue;
            }
            if ($ch === ',' && $depth === 1) {
                $cols[] = $this->columnName(substr($block, $open + 1, $i - $open - 1));
            }
        }
        $last = substr($block, $open + 1, $i - $open - 1);
        $tail = trim($last);
        if ($tail !== '' && $tail[0] !== ']') {
            $cols[] = $this->columnName($last);
        }

        return array_values(array_filter($cols, static fn (string $c): bool => $c !== ''));
    }

    /** The last quoted column name in one comma-separated key fragment. */
    private function columnName(string $fragment): string
    {
        $matches = [];
        if (preg_match_all("/'([^']+)'/", $fragment, $matches) === 0) {
            return '';
        }

        return trim((string) end($matches[1]));
    }

    /**
     * The LIVE columns of a table: declared type, NOT NULL flag and default,
     * read from the built schema rather than from the migration source, so the
     * fixture above cannot name a column the table does not have — the defect
     * that made the sweep unrunnable on 32 of the 36 tables.
     *
     * @return array<string, array{type: string, notnull: int, default: mixed}>
     */
    private function liveColumns(Connection $db, string $table): array
    {
        $columns = [];
        foreach ($db->select('PRAGMA table_info('.$db->getPdo()->quote($table).')') as $info) {
            $info = (array) $info;
            $columns[(string) $info['name']] = [
                'type' => strtolower((string) $info['type']),
                'notnull' => (int) $info['notnull'],
                'default' => $info['dflt_value'],
            ];
        }

        return $columns;
    }

    /**
     * A value the driver will accept for a NOT NULL column the DDL declares
     * without a default, chosen from the DECLARED type rather than guessed.
     *
     * This is what the real writers do: `MirrorFactDecomposer` feeds a scalar a
     * constructor does not carry with a sentinel of the field's own type, and
     * `UpdateIngestor`'s `MESSAGE_BOOLS` dial does the same for the flags. The
     * fixture has to do it too, because omitting the column is not "no value" —
     * it is a NOT NULL violation, and the message the mirror is now built on is
     * that a missing key column is a clue rather than a silently zeroed row.
     */
    private function sentinelFor(string $type): int|string
    {
        if (str_contains($type, 'int')) {
            return 1;
        }
        if (str_contains($type, 'bool')) {
            return 0;
        }

        return 'x';
    }

    /**
     * The LIVE primary key of a table, read from the built schema.
     *
     * @return list<string>
     */
    private function livePrimaryKey(Connection $db, string $table): array
    {
        $cols = [];
        foreach ($db->select('PRAGMA table_info('.$db->getPdo()->quote($table).')') as $info) {
            $info = (array) $info;
            if ((int) ($info['pk'] ?? 0) === 0) {
                continue;
            }
            $cols[(int) $info['pk']] = (string) $info['name'];
        }
        ksort($cols);

        return array_values($cols);
    }

    /**
     * A comparable fingerprint of every table's columns and key, for the
     * idempotence proof. Includes column TYPES and the NOT NULL / default
     * shape, not just names: a migration that changed only a default would
     * otherwise look identical.
     *
     * @return array<string, mixed>
     */
    private function schemaSignature(Connection $db): array
    {
        $signature = [];
        $names = $db->select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        foreach ($names as $row) {
            $table = (string) ((array) $row)['name'];
            if (str_starts_with($table, 'sqlite_')) {
                continue;
            }
            $cols = [];
            foreach ($db->select('PRAGMA table_info('.$db->getPdo()->quote($table).')') as $info) {
                $info = (array) $info;
                $cols[] = [
                    'name' => (string) $info['name'],
                    'type' => strtolower((string) $info['type']),
                    'notnull' => (int) $info['notnull'],
                    'default' => $info['dflt_value'],
                    'pk' => (int) $info['pk'],
                ];
            }
            $signature[$table] = $cols;
        }

        return $signature;
    }

    /**
     * Run the shipped messages migration on a throwaway in-memory sqlite and
     * hand back the connection — never a transcribed copy of the DDL.
     */
    private function shippedMessagesSchema(): Connection
    {
        $previous = Container::getInstance();
        $capsule = ShippedStoreSchema::capsuleFor(self::MESSAGES_MIGRATION);

        try {
            return $capsule->getConnection();
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * One `tf_messages` write under the FULL peer-scoped key, expressed the way
     * `UpdateIngestor::writeMessage()` expresses its `updateOrInsert`.
     *
     * @param  array{peer_type: int, peer_id: int, body: string}  $message
     */
    private function writeMessage(Connection $db, array $message): void
    {
        $db->table('tf_messages')->updateOrInsert(
            ['account_id' => 1, 'peer_type' => $message['peer_type'], 'peer_id' => $message['peer_id'], 'id' => 5],
            ['constructor' => 'message', 'message' => $message['body']],
        );
    }
}
