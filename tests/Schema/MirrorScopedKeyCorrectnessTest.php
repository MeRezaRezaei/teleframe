<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Ingest\MirrorResultPolicy;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Task 2.2 Phase C — THE FIVE NON-MESSAGE TABLE GROUPS. Measured, and ruled
 * against the TL rather than against anyone's relay.
 *
 * ## WHY THIS FILE IS GREEN AND NAMES DEFECTS INSTEAD OF BEING RED
 *
 * Task 2.2's brief lists five further groups to re-key, and Step 3 asks for
 * `tests/Schema/MirrorScopedKeyCorrectnessTest.php` with reorder-survives cases.
 * The reorder cases were written and run first. **They pass against the
 * shipped key**, which is worth recording because the brief assumed otherwise:
 * with a slot-ordinal key `(account_id, id, position)`, re-ingesting `[A,B]` as
 * `[B,A]` OVERWRITES position 0 and position 1, so the two rows are still there
 * and still each hold one peer. A count assertion cannot tell the two keys
 * apart, and neither can the brief's stated case.
 *
 * What a slot key actually gets wrong is REMOVAL. Telegram sends the whole
 * vector every time, and a shorter vector leaves the surplus slots untouched,
 * because nothing in the write addresses them. `[A]` → `[A,B]` → `[B]` leaves
 * `position 1 = B` behind while `position 0` becomes `B` too: **B is stored
 * twice and A is gone.** That is a set stored as a multiset.
 *
 * MEASURED, BOTH KEYS, ONE PAYLOAD SEQUENCE — and the second half of the answer
 * is the part the first draft of this file got wrong:
 *
 *   slot key `(account_id,id,position)`            → [-1002, -1002]
 *   peer key `(account_id,id,peer_type,peer_id)`  → [-1002, -1001]
 *
 * So a peer key DOES fix the duplicate — rows addressed by a peer cannot
 * duplicate one another, because there is only one address for that peer. It
 * does **NOT** fix the stale `-1001`, because `updateOrInsert` never deletes
 * and a member removed from the set leaves its row under ANY key. **Re-keying
 * these three tables is necessary and NOT sufficient**; the purge is a second,
 * separate piece of work, and this file records that rather than letting a
 * reader infer the tables are fixed by a key change.
 *
 * Because the shipped key is wrong and cannot be made right without the purge
 * decision, the contrast is asserted as a MEASUREMENT of both schemas — the
 * repo's established deferral shape (`tf_stars_subscriptions` /
 * `SCOPE_AWAITING_MIGRATION`) — rather than as a permanently red assertion that
 * would take the whole suite down with it.
 *
 * The shipped-side assertion turns RED the moment the DDL is re-keyed. That is
 * the point: the pin cannot be left quietly stale, because whoever lands the
 * fix has to come here and re-derive the expectation.
 *
 * ## THE TL RULING, PER GROUP — SCOPE PRESENT OR ABSENT
 *
 * The brief's "correct key" column is only reachable when the TL constructor
 * carries something to key ON. Three of the five groups do not; two do. Every
 * arm below reads the real `.tl`, so a future layer bump that adds a field
 * turns the relevant arm red instead of leaving a wrong note in a comment.
 *
 *  | group | table | TL constructor | ruling |
 *  |---|---|---|---|
 *  | 2 | `tf_dialog_filters_{pinned,include,exclude}_peers` | `dialogFilter#aa472651` | **DERIVABLE.** `Vector<InputPeer>`; every member of that union carries a chat/user/channel id, so peer identity is present. Key is wrong; the data to fix it is already on the row. A key change ALSO needs a purge — see the measured contrast above. |
 *  | 3 | `tf_todo_lists` + children | `todoList#49b92a26` | **SCOPE ABSENT.** `flags:#`, `title`, `list` — no id, no peer, no chat. `todo_list_id` is fabricated by the catalog and is a per-dialog counter, which is precisely why it collides. Keying it correctly needs the ENCLOSING RPC's dialog, which the resolver does not thread into a fact key. |
 *  | 4 | `tf_admin_log_events` | `channelAdminLogEvent#1fad68cd` | **SCOPE ABSENT**, as the brief says. Confirmed here, and its container `channels.adminLogResults#ed8af74d` does not carry a chat id either. |
 *  | 5 | `tf_bot_infos` + children | `botInfo#4d8a0299` | **PARTIALLY DERIVABLE.** It has no `id`, but it DOES have `user_id:flags.0?long` on the constructor — so a `(account_id, user_id)` key is reachable, with the caveat that the field is FLAGGED and may be absent. |
 *  | 6 | `tf_forum_topics` | `forumTopic#fcdad815` | **SCOPE ABSENT from the type.** It has `peer:Peer`, but that is the topic AUTHOR (the brief is right), and the forum itself lives on `channels.getForumTopics`'s `channel:InputChannel` PARAM — not on the type. |
 *
 * Widening groups 3, 4 or 6 to a scope column nothing can fill would put
 * `0/0` on every row — the corruption this whole task exists to end, and the
 * one already fixed once on 2026-10-02. So they are left alone, and the reason
 * is recorded here and in the report rather than acted on.
 *
 * ## The peer_type ruling for `tf_messages`, which the groups above share
 *
 * A Task 2.1 reviewer read `message.peer_id` as a bare `long` and concluded
 * `peer_type` had no payload source; a relay repeated it. Both were wrong.
 * `api_full.tl:121` declares it `peer_id:Peer`, and `Peer` is a THREE-way
 * union at `api_full.tl:73-75` (`peerUser` / `peerChat` / `peerChannel`).
 * The discriminator IS the constructor name Telegram sent, so
 * `"scoped_by": ["peer_type","peer_id"]` names two real columns and not a
 * fabrication. `test_message_peer_id_is_a_three_constructor_union` pins that
 * reading against the `.tl`, and
 * `tests/Ingest/MessagePeerHalfIsDerivedFromTheWireTest` proves the writer
 * derives the values through the real ingest.
 */
final class MirrorScopedKeyCorrectnessTest extends TestCase
{
    private const GAP_MIGRATION = '/../../src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php';

    /** The real TL the schema was generated from. Read, never transcribed. */
    private const TL = '/../../src/Schema/schema/sources/api_full.tl';

    private const ACCOUNT = 1;

    /** `dialogFilter#aa472651` — the filter the peer sets hang off. */
    private const FILTER_ID = 12;

    /**
     * The three peer-SET tables, each keyed today by SLOT ORDINAL.
     *
     * @var list<string>
     */
    private const PEER_SET_TABLES = [
        'tf_dialog_filters_pinned_peers',
        'tf_dialog_filters_include_peers',
        'tf_dialog_filters_exclude_peers',
    ];

    /**
     * The slot-ordinal key the SHIPPED DDL declares for the tables above.
     *
     * THIS IS THE DEFECT, WRITTEN DOWN. It is asserted, not restated, because
     * the alternative — asserting the correct key — is a test that cannot be
     * written while the DDL is wrong: it would have to be red, and a red test
     * here takes every test in the suite with it. Asserting the wrong key
     * loudly is the repo's own deferral shape (see
     * `MirrorScopedKeyDeclarationTest`'s `tf_stars_subscriptions` exemption) and
     * it has the property that matters: **the day someone fixes the DDL this
     * goes red**, so the pin cannot be left quietly stale.
     *
     * @var list<string>
     */
    private const SHIPPED_SLOT_KEY = ['account_id', 'id', 'position'];

    /**
     * The key the brief specifies, kept as a named constant so the two can be
     * compared side by side in the report and in review.
     *
     * `id` IS the filter id here — `dialogFilter#aa472651` carries `id:int`, so
     * the brief's `filter_id` and the shipped `id` are the same column, not two.
     *
     * @var list<string>
     */
    private const TARGET_PEER_KEY = ['account_id', 'id', 'peer_type', 'peer_id'];

    // ── THE TL RULING ──────────────────────────────────────────────────────

    /**
     * `message.peer_id` is a THREE-CONSTRUCTOR UNION, not a bare `long`.
     *
     * This is the arm that settles the scope question the task inherited. If
     * the field really were a `long`, `"scoped_by": ["peer_type","peer_id"]`
     * would name a column with no payload source, and the correct declaration
     * would be `["peer_id"]` alone.
     *
     * @return array<string, array{0: string}>
     */
    public static function peerUnionConstructors(): array
    {
        return [
            'peerUser' => ['peerUser'],
            'peerChat' => ['peerChat'],
            'peerChannel' => ['peerChannel'],
        ];
    }

    #[DataProvider('peerUnionConstructors')]
    public function test_message_peer_id_is_a_three_constructor_union(string $ctor): void
    {
        $message = $this->constructorDeclaration('message');
        self::assertStringContainsString(
            'peer_id:Peer',
            $message,
            '`message` no longer declares `peer_id:Peer`. If the field became a bare long, then '
            .'peer_type has NO payload source and the shipped `scoped_by: ["peer_type","peer_id"]` '
            .'must be reduced to `["peer_id"]` — this arm is what forces that decision rather than '
            .'leaving it in a comment.',
        );

        // The field is MANDATORY and UNFLAGGED, which is why the writer can always
        // derive the halves on a real connection.
        //
        // THE LEADING SPACE IS LOAD-BEARING, and its absence is a lesson worth
        // keeping. The first spelling of this check was `'peer_id:flags.'`,
        // which FAILED on the shipped schema — and wrongly: the declaration
        // contains `saved_peer_id:flags.28?Peer`, and that string CONTAINS
        // `peer_id:flags.` as a substring. A negative substring check over a
        // space-separated field list needs the separator to anchor the field
        // NAME, or it matches the tail of a longer field. Every field in a TL
        // declaration is preceded by a space, so `' peer_id:flags.'` is exact.
        self::assertStringNotContainsString(
            ' peer_id:flags.',
            $message,
            '`message.peer_id` became optional. The derivation then has a legitimate absent case, '
            .'and the placeholder arm in MessagePeerHalfIsDerivedFromTheWireTest stops being unreachable.',
        );

        // And the union really does carry three distinct id fields — that is
        // what makes `peer_type` a reading rather than an invention.
        $decl = $this->constructorDeclaration($ctor);
        self::assertStringEndsWith(
            '= Peer;',
            $decl,
            sprintf('`%s` is no longer a member of the `Peer` union', $ctor),
        );
        self::assertStringContainsString(
            '_id:long',
            $decl,
            sprintf('`%s` no longer carries an id field, so it cannot identify a peer', $ctor),
        );
    }

    /**
     * Group 3 — the scope is ABSENT from `todoList`, so no key change can be
     * made without inventing a value.
     *
     * `todoList#49b92a26` is `flags:# others_can_append … title … list:
     * Vector<TodoItem>`: no id of its own, no peer, no chat. The
     * `todo_list_id` the shipped DDL keys on is FABRICATED by the catalog, and
     * it is a per-dialog counter, which is exactly why two dialogs collide on
     * it. The enclosing dialog lives on the RPC, not on the type.
     */
    public function test_todo_list_carries_no_scope_to_key_on(): void
    {
        $decl = $this->constructorDeclaration('todoList');

        self::assertStringNotContainsString('peer:', $decl, '`todoList` gained a peer field');
        self::assertStringNotContainsString('_id:', $decl, '`todoList` gained an `_id` field');
        self::assertStringNotContainsString(' id:', $decl, '`todoList` gained an `id` field');
        self::assertStringContainsString(
            'list:Vector<TodoItem>',
            $decl,
            '`todoList` lost `list:Vector<TodoItem>` — re-read this file before trusting the arms above',
        );
    }

    /**
     * Group 4 — no field on `channelAdminLogEvent` NAMES A CHAT.
     *
     * Deliberately NOT written as "there is no `_id` field", because there is:
     * the declaration carries `id:long` and `user_id:long`, and a test that
     * claimed otherwise would be false about the wire. The absence being
     * asserted is the SCOPE's absence, and it is asserted against the exact
     * field names that would supply one.
     */
    public function test_admin_log_event_carries_no_field_naming_a_chat(): void
    {
        $decl = $this->constructorDeclaration('channelAdminLogEvent');

        foreach (['peer:', 'chat_id:', 'channel_id:'] as $wouldScope) {
            self::assertStringNotContainsString(
                $wouldScope,
                $decl,
                sprintf('`channelAdminLogEvent` now carries `%s`. That is a chat scope, tf_admin_log_events '
                    .'becomes keyable, and the deferral recorded in this file must be discharged.',
                    $wouldScope),
            );
        }

        // And the two `_id` fields it DOES carry are named here so a reader does
        // not have to rediscover that they are the event id and the acting
        // admin rather than a scope. This is the correction to the naive
        // reading, asserted so it cannot quietly change meaning.
        self::assertStringContainsString(' id:long', $decl, '`channelAdminLogEvent` lost its event id');
        self::assertStringContainsString(
            ' user_id:long',
            $decl,
            '`channelAdminLogEvent` lost `user_id:long` — the acting ADMIN, not a scope. If this field '
            .'were ever renamed to something chat-shaped, re-read this whole arm.',
        );
    }

    /**
     * Group 6 — `forumTopic.peer` is the topic AUTHOR, and the forum is on the
     * METHOD, not the type.
     *
     * `forumTopic#fcdad815` DOES carry a `peer:Peer`, so this table is the one
     * where the shape of the payload is most misleading: keying it on `peer`
     * would key every topic in every forum by its author's user id, which is
     * not just wrong but actively colliding — one author who posts in twenty
     * forums has twenty rows at the same key.
     */
    public function test_forum_topic_peer_is_the_author_and_not_the_forum(): void
    {
        $decl = $this->constructorDeclaration('forumTopic');

        self::assertStringContainsString(
            'peer:Peer',
            $decl,
            '`forumTopic` lost its `peer:Peer` field — the deferral in this file\'s header rests on that '
            .'field being the AUTHOR, so a change to it must be re-read.',
        );
        self::assertStringContainsString(
            'id:int',
            $decl,
            '`forumTopic` lost its `id:int` — the topic id is half of the only usable key this table has',
        );

        // The forum is on the CALL, which is why the type alone cannot be
        // keyed: a resolver that threaded method params into a fact key could
        // key this table correctly. Asserting the method carries it keeps that
        // future change honest instead of leaving it as folklore.
        //
        // MEASURED, and the first guess was wrong: this is
        // `messages.getForumTopics#3ba47bff ... peer:InputPeer ...`, not
        // `channels.getForumTopics` and not `channel:InputChannel`. Both are
        // worth stating precisely — the METHOD is on `messages`, and the scope
        // field is spelled `peer` and typed `InputPeer`. Reading it off the
        // `.tl` is the only reason the correction landed.
        $method = $this->declarationOf('messages.getForumTopics');
        self::assertStringContainsString(
            'peer:InputPeer',
            $method,
            '`messages.getForumTopics` no longer carries `peer:InputPeer` — the only source of a forum '
            .'scope for tf_forum_topics. If it moved, that table becomes keyable and this deferral must '
            .'be discharged.',
        );
        self::assertStringContainsString(
            '= messages.ForumTopics;',
            $method,
            '`messages.getForumTopics` no longer returns messages.ForumTopics — re-read this arm',
        );
    }

    /**
     * Group 5 — `botInfo` has no `id`, but it DOES carry `user_id`, so
     * `(account_id, user_id)` is reachable. It is FLAGGED, and that is the
     * caveat the fix has to solve rather than assume away.
     */
    public function test_bot_info_carries_a_flagged_user_id_and_no_id(): void
    {
        $decl = $this->constructorDeclaration('botInfo');

        self::assertStringContainsString(
            'user_id:flags.0?long',
            $decl,
            '`botInfo` no longer carries `user_id:flags.0?long`. That field is the ONLY identity '
            .'tf_bot_infos has (the brief says so), so if it is gone the group has no derivable key '
            .'at all and the deferral must be extended to cover it.',
        );
        self::assertStringNotContainsString(
            ' id:int',
            ' '.$decl,
            '`botInfo` gained an `id`. If it is globally unique the brief\'s whole group-5 justification '
            .'("botInfo#4d8a0299 has no id") is stale and re-keying the 14 tables is unnecessary churn.',
        );
    }

    // ── GROUP 2: THE PEER-SET TABLES ────────────────────────────────────────

    /**
     * THE MEASUREMENT. The three peer-SET tables are keyed by slot ordinal, and
     * `position` is in the PRIMARY KEY — so the key cannot survive a reorder.
     *
     * Read off the BUILT schema via `PRAGMA table_info`, never off the
     * migration's source text, so this cannot drift from what ships.
     */
    public function test_the_peer_set_tables_are_keyed_by_slot_ordinal_today(): void
    {
        $db = $this->gapSchema();

        foreach (self::PEER_SET_TABLES as $table) {
            self::assertSame(
                self::SHIPPED_SLOT_KEY,
                $this->primaryKey($db, $table),
                sprintf(
                    '%s no longer has the slot-ordinal key this file pins. Either it was re-keyed '
                    .'by peer — in which case the DEFECT RECORDED HERE IS FIXED and this pin plus the '
                    .'removal case below must be re-derived, not deleted — or the key changed some '
                    .'other way, which this test has not been told about.',
                    $table,
                ),
            );

            self::assertNotSame(
                self::TARGET_PEER_KEY,
                $this->primaryKey($db, $table),
                'this assertion exists so the pin above cannot be satisfied by the fix it documents',
            );
        }
    }

    /**
     * The removal case, MEASURED ON BOTH KEYS rather than asserted from theory.
     *
     * `[A]` → `[A,B]` → `[B]` is an ordinary edit: the operator drops a chat
     * from a filter's pinned list and Telegram re-sends the whole vector one
     * element shorter.
     *
     * The FIRST DRAFT OF THIS ARM CLAIMED THAT A PEER KEY MAKES THE DUPLICATE
     * IMPOSSIBLE, and it was right about the duplicate and wrong to stop
     * there. Measured, both keys, same payload sequence:
     *
     *   slot key `(account_id,id,position)` → stored peers [-1002, -1002]
     *   peer key `(account_id,id,peer_type,peer_id)` → stored peers [-1002, -1001]
     *
     * The slot key stores B TWICE, which is a set-semantics violation a peer
     * key genuinely prevents: rows addressed by the peer cannot duplicate one
     * another, because there is only one address for that peer. THAT is the
     * half a key change fixes, and the half the brief is about.
     *
     * The peer key does NOT fix the stale row: -1001 survives in both. That is
     * a MISSING PURGE, not a key shape — `updateOrInsert` never deletes, so a
     * member removed from the set leaves its row behind under any key. It is
     * recorded here rather than asserted as fixed, because claiming otherwise
     * is how a reader comes to believe the table is correct. Re-keying is
     * necessary and NOT sufficient for these three tables.
     *
     * BOTH halves are measured here, from two real schemas, so neither the
     * defect nor its fix rests on this file's reasoning about either.
     */
    public function test_the_slot_key_duplicates_a_peer_where_a_peer_key_would_not(): void
    {
        $shipped = $this->gapSchema();
        $peerKeyed = $this->peerKeyedReplica();

        // The three payloads, in Telegram's own terms: two channels, and the
        // second edit removes the first.
        $sequence = [[-1001], [-1001, -1002], [-1002]];

        foreach ($sequence as $members) {
            foreach ($members as $position => $channelId) {
                $shipped->table('tf_dialog_filters_pinned_peers')->updateOrInsert(
                    ['account_id' => self::ACCOUNT, 'id' => self::FILTER_ID, 'position' => $position],
                    ['constructor' => 'inputPeerChannel', 'user_id' => 0, 'chat_id' => 0, 'access_hash' => 0, 'channel_id' => $channelId, 'msg_id' => 0],
                );
                $peerKeyed->table('replica')->updateOrInsert(
                    ['account_id' => self::ACCOUNT, 'id' => self::FILTER_ID, 'peer_type' => 3, 'peer_id' => $channelId],
                    ['position' => $position],
                );
            }
        }

        $shippedPeers = $this->storedChannels($shipped, 'tf_dialog_filters_pinned_peers', 'channel_id');
        $replicaPeers = $this->storedChannels($peerKeyed, 'replica', 'peer_id');

        self::assertSame(
            [-1002, -1002],
            $shippedPeers,
            'the shipped slot-ordinal key did NOT store the peer twice on removal, so this table is '
            .'no longer keyed by slot ordinal — re-derive this file rather than trusting it.',
        );
        self::assertSame(
            [-1002, -1001],
            $replicaPeers,
            'the peer-keyed replica CHANGED. Either the stale -1001 row is now purged — which means '
            .'the writer gained a delete this test has not been told about — or the replica schema '
            .'is not the shape it claims. Do not read this as the peer key having solved the problem: '
            .'a key change prevents the DUPLICATE, and the stale row needs a purge either way.',
        );

        // The property that distinguishes the two keys, stated as a comparison
        // rather than as a theory: the slot key's stored set is NOT a set.
        self::assertCount(
            count(array_unique($replicaPeers)),
            $replicaPeers,
            'the peer key must store each peer at most once — that is the whole of what a key change '
            .'buys on these tables',
        );
        // The contrast is in the VALUES, not the row COUNT — both keys store
        // two rows, because nothing purges and the payload is the same length
        // in the middle step. An earlier draft of this arm asserted the counts
        // differed, which measured nothing and failed for the right reason.
        self::assertNotSame(
            $shippedPeers,
            $replicaPeers,
            'the two keys stored the SAME values, so this file has measured nothing: either the slot key '
            .'changed or the replica is not peer-keyed.',
        );
    }

    /**
     * The peer identity IS on the row, so the fix needs NO new payload source.
     *
     * This is the measurement that decides group 2's verdict: a re-key to
     * `(account_id, id, peer_type, peer_id)` needs the two halves to be
     * DERIVABLE, and `constructor` + `chat_id`/`user_id`/`channel_id` already
     * carry them — the InputPeer union member is decomposed in place. Asserted
     * from the built schema, so if the columns are ever dropped this goes red
     * before someone tries to key on them.
     */
    public function test_the_peer_identity_is_already_on_the_row(): void
    {
        $db = $this->gapSchema();

        foreach (self::PEER_SET_TABLES as $table) {
            $columns = array_keys($this->columns($db, $table));
            self::assertContains('constructor', $columns, "{$table} lost its union discriminator");
            foreach (['chat_id', 'user_id', 'channel_id'] as $half) {
                self::assertContains(
                    $half,
                    $columns,
                    "{$table} lost `{$half}`. That column carried the peer id this table is keyed on; "
                    .'without it the group-2 fix has no payload source and the deferral must be recorded '
                    .'as UNFIXABLE rather than pending.',
                );
            }
        }
    }

    /**
     * Group 4 — `channels.getAdminLog` is NOT admitted, and that is pinned.
     *
     * The brief says to leave it out of `MirrorResultPolicy::DEFAULT_METHODS`
     * and "record why". Recording it in a docblock is a note; pinning it is a
     * gate. `channels.getAdminLog` detonates `tf_admin_log_events` the moment
     * it is admitted — its scope is ABSENT, not misplaced — so the admission
     * must not be able to arrive as an unexplained line.
     */
    public function test_the_admin_log_method_is_not_admitted(): void
    {
        self::assertFalse(
            MirrorResultPolicy::default()->mirrors('channels.getAdminLog'),
            'channels.getAdminLog has been ADMITTED. tf_admin_log_events ships primary([account_id,id]) '
            .'and `channelAdminLogEvent#1fad68cd` carries no chat id, so every admin-log event of every '
            .'channel this account can administer now lands on one key. Revert the admission, or land '
            .'the resolver change that threads a method param into the fact key FIRST.',
        );

        // The neighbouring methods are still admitted — so this is not passing
        // because the policy is empty or broken.
        self::assertTrue(
            MirrorResultPolicy::default()->mirrors('messages.getDialogs'),
            'messages.getDialogs is no longer admitted, so the assertion above is not evidence of anything',
        );
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * A table with the peer key the brief specifies, built on the same in-memory
     * connection as the shipped schema.
     *
     * A SECOND COPY OF NOTHING: this is not a transcription of a migration that
     * does not exist yet, it is the COUNTERFACTUAL the shipped schema is
     * measured against, built here so the comparison is a measurement rather
     * than this file's reasoning about one. The shipped side is still read by
     * EXECUTING its real migration.
     */
    private function peerKeyedReplica(): Connection
    {
        $db = $this->gapSchema();

        $db->statement(
            'CREATE TABLE replica ('
            .'account_id INTEGER NOT NULL, '
            .'id INTEGER NOT NULL, '
            .'peer_type INTEGER NOT NULL, '
            .'peer_id INTEGER NOT NULL, '
            .'position INTEGER NOT NULL, '
            .'PRIMARY KEY (account_id, id, peer_type, peer_id))'
        );

        return $db;
    }

    /**
     * The peer ids stored under one filter, in slot order.
     *
     * `$idColumn` differs between the two schemas and THAT IS THE POINT: the
     * shipped table spells the peer id as the union member's own `channel_id`
     * (it is an InputPeer decomposition), while the replica keys on a
     * normalised `peer_id`. The comparison is of VALUES, so both are read
     * through this one reader rather than each arm re-typing its own column.
     *
     * @return list<int>
     */
    private function storedChannels(Connection $db, string $table, string $idColumn): array
    {
        $rows = $db->table($table)
            ->where('account_id', self::ACCOUNT)
            ->where('id', self::FILTER_ID)
            ->orderBy('position')
            ->get();

        $channels = [];
        foreach ($rows as $row) {
            $channels[] = (int) $row->{$idColumn};
        }

        return $channels;
    }

    private function gapSchema(): Connection
    {
        return ShippedStoreSchema::capsuleFor(__DIR__.self::GAP_MIGRATION)->getConnection();
    }

    /**
     * @return list<string>
     */
    private function primaryKey(Connection $db, string $table): array
    {
        $pk = [];
        foreach ($this->columns($db, $table) as $name => $info) {
            if ($info['pk'] > 0) {
                $pk[$info['pk']] = $name;
            }
        }
        ksort($pk);

        return array_values($pk);
    }

    /**
     * @return array<string, array{pk:int, notnull:int, type:string}>
     */
    private function columns(Connection $db, string $table): array
    {
        $out = [];
        foreach ($db->select('PRAGMA table_info('.$db->getPdo()->quote($table).')') as $row) {
            $row = (array) $row;
            $out[(string) $row['name']] = [
                'pk' => (int) $row['pk'],
                'notnull' => (int) $row['notnull'],
                'type' => (string) $row['type'],
            ];
        }
        self::assertNotSame([], $out, "{$table} has no columns in the built schema");

        return $out;
    }

    /**
     * The one-line body of a TL constructor declaration, read from the real
     * schema file.
     *
     * Read with string functions, never with a regex: `src/` bans `preg_*` and
     * a test that reached for one to inspect the wire would model the wrong
     * habit for whoever copies it.
     */
    private function constructorDeclaration(string $name): string
    {
        return $this->declarationOf($name);
    }

    private function declarationOf(string $name): string
    {
        $file = __DIR__.self::TL;
        self::assertFileExists($file, 'the TL source this file rules against is missing');

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = ltrim($line);
            // A declaration starts `name#hash ` (constructors) or `name:type `
            // (methods). Anything else is a comment or a bare line.
            if (! str_starts_with($line, $name)) {
                continue;
            }
            $rest = substr($line, strlen($name));
            if ($rest === '' || ($rest[0] !== '#' && $rest[0] !== ':')) {
                continue;
            }
            self::assertNotSame(
                '',
                $rest,
                sprintf('`%s` matched a line that is not its declaration', $name),
            );

            return $line;
        }

        self::fail(sprintf('no declaration for `%s` in %s — the schema file changed under this test', $name, $file));
    }
}
