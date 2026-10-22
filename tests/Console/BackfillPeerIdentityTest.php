<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Backfill\MemoryCursorStore;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * PEER IDENTITY IS `tf_dialogs.peer_type`, NOT THE SIGN OF THE ID.
 *
 * THE DEFECT, measured live against the operator's own account on 2026-10-02.
 * `mirrorPeerLookup()` branched on `$peerId < 0`, on the premise that a
 * negative id is a channel. That premise is FALSE on MTProto: `-100…` is the
 * BOT API's spelling of a supergroup, and every MTProto channel id is
 * POSITIVE. `DialogDiscovery` prints them that way and stores them that way —
 *
 *     discovered channel:1232026269 {3:1232026269, top_message:38826534}
 *     tf_dialogs.peer_type = 3, tf_dialogs.peer_id = 1232026269
 *
 * — so `$peerId < 0` was never true for a channel this command produces. The
 * channel branch was DEAD CODE, the positive id fell through to the USER
 * table, missed, and the run refused a peer whose hash was sitting in the
 * database the whole time:
 *
 *     peer_without_access_hash: --peer=1232026269 cannot be crawled —
 *     this account holds no access_hash for peer id 1232026269 in
 *     tf_chats_access_hash / tf_users_access_hash / tf_chats
 *
 * while `SELECT * FROM tf_chats_access_hash WHERE id = 1232026269` returns a
 * row. 127 of them had landed by then. The refusal message was therefore not
 * merely unhelpful: it named `tf_chats_access_hash` as a place to look, and
 * the row WAS there.
 *
 * WHY `peer_type` IS THE RIGHT DISCRIMINATOR. `tf_dialogs` is keyed
 * `(account_id, peer_type, peer_id)` — the shape Telegram itself uses, and
 * the same shape `DialogDiscovery::entry()` builds a peer from. The table
 * already records which KIND of thing each id names, per account, with no
 * request spent. Inferring the same fact from arithmetic on the id is
 * inference the database has already done, done wrongly.
 *
 * WHY THE SIGN CANNOT COME BACK, even as a fallback: the cost of guessing
 * wrong is not a wrong answer, it is a REQUEST. A peer sent as the wrong
 * InputPeer constructor spends a budgeted `messages.getHistory` call to be
 * told CHANNEL_INVALID/USER_ID_INVALID — strictly worse than the honest
 * refusal this command is built around, and it is undetectable afterwards
 * because it is reported as the generic `fetch_failed`.
 */
final class BackfillPeerIdentityTest extends IngestTestCase
{
    /**
     * The tenant this fixture writes under.
     *
     * MEASURED, not assumed: every row inserted here carries
     * `account_id = 7` (self::ACCOUNT).
     *
     * It was undeclared. With foreign-key enforcement on, every insert was
     * refused at the DATABASE rather than at the code under test — the test
     * failed on its own fixture and reported nothing about the behaviour it
     * exists to prove.
     *
     * Declared HERE rather than by relaxing seedIngestTenant(), because that
     * helper contracts that each test declares the tenants it means to use, so
     * a test writing under an unplanted id fails LOUDLY. These were failing
     * loudly, which is the mechanism working.
     */
    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT, self::ACCOUNT + 1];
    }

    private const ACCOUNT = 7;

    /** The operator's real measured channel id — POSITIVE, as MTProto spells it. */
    private const CHANNEL_ID = 1232026269;

    /** A real negative TL `long`, as `tf_chats_access_hash.access_hash` stores it. */
    private const CHANNEL_HASH = '-8347482207780687720';

    private const USER_ID = 8329480050;

    private const USER_HASH = '5512378901644';

    /**
     * GREEN — THE DEFECT'S FIXTURE, THE OPPOSITE OUTCOME.
     *
     * This test is the INVERTED FORM of the defect characterisation, and the
     * inversion is the record. Before the fix, this exact fixture was a
     * passing test asserting THE REFUSAL:
     *
     *     1) test_defect_a_positive_channel_id_with_a_stored_hash_is_refused_today
     *        Expected status code 1 but received 0.
     *
     * — i.e. `mirrorPeerLookup()` read the id as a user because it was
     * positive, missed, and refused a peer whose hash was in the database. That
     * characterisation was deleted rather than left in place, because a test
     * pinning the bug is a test that fights the fix; its evidence lives here,
     * and its precondition — the row EXISTS — is asserted below so this test
     * cannot pass for the wrong reason either.
     *
     * Resolved as `inputPeerChannel`, carrying the hash VALUE the database
     * holds, because `tf_dialogs.peer_type = 3` says this id names a channel
     * and nothing about a positive id says it cannot.
     */
    public function test_a_positive_channel_id_with_peer_type_three_resolves_to_input_peer_channel(): void
    {
        $this->seedDiscoveredChannel();

        self::assertSame(
            self::CHANNEL_HASH,
            (string) DB::table('tf_channels')
                ->where('account_id', self::ACCOUNT)
                ->where('id', self::CHANNEL_ID)
                ->value('access_hash'),
            'precondition: the channel access_hash this account was given IS stored, so a resolution cannot '
            .'be excused as a hash that was never there — this is the half the old refusal test existed for',
        );

        $scope = $this->bind(new DiscoveryScope);

        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT,
            '--peer' => [(string) self::CHANNEL_ID],
            '--no-persist' => true,
        ])->assertExitCode(0);

        $peer = $this->peerOnHistory($scope);

        self::assertSame(
            'inputPeerChannel',
            (string) ($peer['_'] ?? ''),
            'peer_type 3 is a channel, so the peer on the wire must be an inputPeerChannel',
        );
        self::assertSame(
            self::CHANNEL_HASH,
            (string) ($peer['access_hash'] ?? ''),
            'with the hash the mirror already held — asserted as a VALUE, because a peer carrying the '
            .'right constructor and no hash would still be refused by Telegram',
        );
        self::assertSame(
            self::CHANNEL_ID,
            (int) ($peer['channel_id'] ?? 0),
            'and addressed by the positive MTProto id, which is what the wire expects',
        );
    }

    /**
     * THE SIGN IS NOT THE DISCRIMINATOR, AND NEITHER TABLE MAY BE REACHED BY
     * THE OTHER'S PEER.
     *
     * Both directions carry a DECOY: the same numeric id also has a row in the
     * OTHER hash table, with a different hash value. Resolving correctly means
     * the decoy was never read. A lookup that fell back to "try the user table
     * if the channel table missed" — or the reverse — would answer with the
     * decoy's hash, and that is a wrong peer on the wire.
     *
     * The hashes are deliberately distinguishable strings, so the assertion is
     * about WHICH row was read and not merely that some row was.
     */
    public function test_peer_type_selects_the_table_and_a_peer_never_reaches_the_other_table(): void
    {
        //  - peer_type 1 (a USER) whose id ALSO exists in the channel table.
        //
        // Both `tf_users_access_hash (account_id, id) -> tf_users` and
        // `tf_chats_access_hash (account_id, id) -> tf_chats` are composite foreign
        // keys added 2026-10-04, so BOTH entity rows must exist before either hash
        // can be written. They were missing here, so the insert was refused:
        //   19 FOREIGN KEY constraint failed … insert into "tf_users_access_hash"
        //   ("account_id", "id", "access_hash") values (7, 8329480050, …)
        //
        // Planting both parents is what keeps the test meaningful: its whole claim is
        // that ONE id can appear as BOTH a user and a chat, and that peer_type — not
        // the id — selects which one is read.
        DB::table('tf_users')->insert([
            'account_id' => self::ACCOUNT, 'id' => self::USER_ID, 'constructor' => 'user',
        ]);
        DB::table('tf_chats')->insert([
            'account_id' => self::ACCOUNT, 'id' => self::USER_ID, 'constructor' => 'channel',
        ]);
        DB::table('tf_dialogs')->insert([
            'account_id' => self::ACCOUNT, 'peer_type' => 1, 'peer_id' => self::USER_ID,
            'constructor' => 'user', 'top_message' => 7,
        ]);
        $this->seedIngestPeer(self::ACCOUNT, self::USER_ID, 'user', self::USER_HASH);
        $this->seedIngestPeer(self::ACCOUNT, self::USER_ID, 'chat', '-1111111111111111111');

        //  - peer_type 3 (a CHANNEL) whose id ALSO exists in the user table.
        DB::table('tf_dialogs')->insert([
            'account_id' => self::ACCOUNT, 'peer_type' => 3, 'peer_id' => self::CHANNEL_ID,
            'constructor' => 'channel', 'top_message' => 38826534,
        ]);
        // Entity-then-hash is NOT the shape here any more: the shipped `Chat`
        // split puts a channel in `tf_channels` with `access_hash` INLINE (a base
        // column, `2026_09_14_200003_create_tf_channels_tables.php:56`), so the
        // channel entity and its hash are ONE row. `seedIngestPeer(..., 'chat')`
        // planted `tf_chats` + `tf_chats_access_hash`, the pre-split layout the
        // product no longer produces.
        DB::table('tf_channels')->insert([
            'account_id' => self::ACCOUNT, 'id' => self::CHANNEL_ID,
            'constructor' => 'channel', 'access_hash' => self::CHANNEL_HASH,
        ]);
        // The SAME id also exists as a USER, with a DIFFERENT hash. peer_type = 3
        // must select the chat and never read this one — that is the assertion. The
        // entity row is required because the hash is a composite child of tf_users.
        $this->seedIngestPeer(self::ACCOUNT, self::CHANNEL_ID, 'user', '2222222222222222222');

        $scope = $this->bind(new DiscoveryScope);

        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT,
            '--peer' => [(string) self::USER_ID],
            '--no-persist' => true,
        ])->assertExitCode(0);

        $user = $this->peerOnHistory($scope);

        self::assertSame('inputPeerUser', (string) ($user['_'] ?? ''), 'peer_type 1 is a user');
        self::assertSame(
            self::USER_HASH,
            (string) ($user['access_hash'] ?? ''),
            'the USER table\'s hash — the decoy row in tf_chats_access_hash must never have been read, '
            .'because a user id reaching the channel table is a wrong-peer request',
        );

        $scope = $this->bind(new DiscoveryScope);

        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT,
            '--peer' => [(string) self::CHANNEL_ID],
            '--no-persist' => true,
        ])->assertExitCode(0);

        $channel = $this->peerOnHistory($scope);

        self::assertSame('inputPeerChannel', (string) ($channel['_'] ?? ''), 'peer_type 3 is a channel');
        self::assertSame(
            self::CHANNEL_HASH,
            (string) ($channel['access_hash'] ?? ''),
            'the CHANNEL table\'s hash — the decoy row in tf_users_access_hash must never have been read, '
            .'because a channel id reaching the user table is a wrong-peer request',
        );
    }

    /**
     * THE BOT API SPELLING STILL WORKS, and a zero hash is still refused.
     *
     * `-100…` is how an operator writes a supergroup, and this repo's own
     * existing fixture uses it. Telegram's MTProto ids are positive, so the
     * only negative id that can reach here is that borrowed prefix — which
     * makes it safe to normalise, and unsafe to keep interpreting as
     * "therefore this is a channel".
     *
     * The zero hash is the other half: a row that EXISTS with `access_hash = 0`
     * means Telegram never told us, and sending 0 looks resolved while being
     * worse than sending nothing.
     */
    public function test_the_bot_api_form_still_resolves_and_a_zero_hash_is_still_refused(): void
    {
        $this->seedDiscoveredChannel();

        $scope = $this->bind(new DiscoveryScope);

        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT,
            '--peer' => ['-100'.self::CHANNEL_ID],
            '--no-persist' => true,
        ])->assertExitCode(0);

        $peer = $this->peerOnHistory($scope);

        self::assertSame(
            'inputPeerChannel',
            (string) ($peer['_'] ?? ''),
            'the Bot API spelling is accepted, because it is what an operator actually types',
        );
        self::assertSame(
            self::CHANNEL_HASH,
            (string) ($peer['access_hash'] ?? ''),
            'and it resolves to the SAME peer as the positive spelling — normalisation, not a second peer',
        );

        // And the zero hash, on the same peer, in a second account.
        //
        // `tf_chats_access_hash (account_id, id) -> tf_chats` is a composite key, so
        // the CHAT must exist under this account too — otherwise the insert itself
        // fails with '19 FOREIGN KEY constraint failed' and the test fails on its
        // fixture instead of on the zero-hash behaviour.
        //
        // The CHANNEL is planted WITHOUT a hash, deliberately. This test asserts that a
        // ZERO access_hash is refused (`peer_without_access_hash`, exit 1), and the
        // resolver reads the channel's own row as a hash source too — so seeding a
        // usable hash here would make the peer RESOLVE, and the test would fail
        // having quietly lost the thing it exists to prove.
        DB::table('tf_channels')->insert([
            'account_id' => self::ACCOUNT + 1, 'id' => self::CHANNEL_ID,
            'constructor' => 'channel', 'access_hash' => 0,
        ]);

        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT + 1,
            '--peer' => [(string) self::CHANNEL_ID],
            '--no-persist' => true,
        ])
            ->expectsOutputToContain(BackfillCommand::PEER_UNRESOLVED)
            ->assertExitCode(1);
    }

    /**
     * THE CONSTRUCTOR GATE SURVIVES THE SPLIT AND THE REWRITE.
     *
     * A channel is NOT a basic group, and answering `inputPeerChat` for one
     * names a DIFFERENT PEER on the wire and buys a CHAT_ID_INVALID with a
     * budgeted request.
     *
     * WHERE THE CHANNEL FACT LIVES CHANGED, 2026-10-11, and the fixture had to
     * follow it or it stopped testing anything. This planted
     * `tf_chats.constructor = 'channel'` on the premise that "tf_chats carries
     * EVERY chat-shaped peer, megagroups included". MEASURED against the shipped
     * dial, that premise is FALSE: `Naming::CTOR_DOMAIN_OVERRIDES`
     * (`src/Schema/Generator/Naming.php:131-133`) routes `channel` /
     * `channelForbidden` to `tf_channels`, `tf_chats` holds `chatEmpty` / `chat`
     * / `chatForbidden` only, and `MirrorNestedSourceWalker::tableFor()` writes a
     * dialogs page's channel into `tf_channels` with the hash inline.
     *
     * Left as it was, this test still passed — but for the wrong reason: the
     * resolver never reads `tf_chats` for a channel's hash at all, so the planted
     * row was inert and the refusal it produced proved nothing about the gate.
     * The channel is now planted in `tf_channels` with `access_hash = 0`, which
     * is the real shape AND the hard case: the row exists, the peer is a channel,
     * and the hash is exactly the "Telegram never told us" value that must be
     * refused rather than sent.
     */
    public function test_a_channel_row_is_never_answered_as_a_basic_group(): void
    {
        DB::table('tf_dialogs')->insert([
            'account_id' => self::ACCOUNT, 'peer_type' => 3, 'peer_id' => self::CHANNEL_ID,
            'constructor' => 'channel', 'top_message' => 38826534,
        ]);
        DB::table('tf_channels')->insert([
            'account_id' => self::ACCOUNT, 'id' => self::CHANNEL_ID,
            'constructor' => 'channel', 'access_hash' => 0,
        ]);

        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT,
            '--peer' => [(string) self::CHANNEL_ID],
            '--no-persist' => true,
        ])
            ->expectsOutputToContain(BackfillCommand::PEER_UNRESOLVED)
            ->assertExitCode(1);

        // And the gate must not be a gate that refuses EVERYTHING: a real
        // basic group (peer_type 2, constructor `chat`, no hash by design) is
        // addressable, and refusing it would be its own defect.
        DB::table('tf_dialogs')->insert([
            'account_id' => self::ACCOUNT, 'peer_type' => 2, 'peer_id' => 4242,
            'constructor' => 'chat', 'top_message' => 3,
        ]);
        DB::table('tf_chats')->insert([
            'account_id' => self::ACCOUNT, 'id' => 4242, 'constructor' => 'chat',
        ]);

        $scope = $this->bind(new DiscoveryScope);

        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT,
            '--peer' => ['4242'],
            '--no-persist' => true,
        ])->assertExitCode(0);

        self::assertSame(
            'inputPeerChat',
            (string) ($this->peerOnHistory($scope)['_'] ?? ''),
            'a basic group needs no access_hash BY DESIGN, so it must still resolve',
        );
    }

    /**
     * UNKNOWN STAYS UNKNOWN.
     *
     * An id this account has no `tf_dialogs` row for and no `tf_chats`
     * constructor for is not a peer it can address. The refusal is free — no
     * request is spent — and it is the only honest answer.
     */
    public function test_a_peer_with_no_known_identity_is_still_refused(): void
    {
        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT,
            '--peer' => ['999000999'],
            '--no-persist' => true,
        ])
            ->expectsOutputToContain(BackfillCommand::PEER_UNRESOLVED)
            ->assertExitCode(1);
    }

    /**
     * The measured fixture: this account met the channel, dialog discovery
     * stored the dialog (`tf_dialogs`, peer_type 3) and the chat fact plus its
     * flag-gated `access_hash` (`tf_chats` / `tf_chats_access_hash`), all with
     * the POSITIVE id MTProto actually uses.
     */
    private function seedDiscoveredChannel(): void
    {
        DB::table('tf_dialogs')->insert([
            'account_id' => self::ACCOUNT, 'peer_type' => 3, 'peer_id' => self::CHANNEL_ID,
            'constructor' => 'channel', 'top_message' => 38826534,
        ]);
        // The channel is planted WHERE THE SHIPPED SCHEMA PUTS IT.
        //
        // This helper used to call `seedIngestPeer(..., 'chat', …)`, which plants
        // `tf_chats` + `tf_chats_access_hash` — the layout that was correct
        // BEFORE the `Chat` union was split, and is the same disproven premise
        // that `BackfillDiscoveryPersistTest` and `MultiEndpointHistoryLandsTest`
        // were asserting on 2026-10-11. `Naming::CTOR_DOMAIN_OVERRIDES`
        // (`src/Schema/Generator/Naming.php:131-133`) sends `channel` /
        // `channelForbidden` to the channels domain, a channel id is NEGATIVE and
        // `tf_channels.id` is the peer FK target for `peer_type 3`, and
        // `access_hash` is a BASE column on `tf_channels`
        // (`2026_09_14_200003_create_tf_channels_tables.php:56`) rather than a
        // 1:1 child. So `MirrorNestedSourceWalker::tableFor()` writes the channel
        // to `tf_channels` with the hash inline and writes NOTHING to
        // `tf_chats_access_hash` — and a fixture planting the old layout tested a
        // peer the product can no longer produce.
        //
        // No assertion in this class was relaxed: every test that calls this still
        // requires the same constructor, the same hash VALUE and the same exit
        // code. Only the table the fixture plants into moved, and it moved to the
        // one the resolver reads.
        DB::table('tf_channels')->insert([
            'account_id' => self::ACCOUNT,
            'id' => self::CHANNEL_ID,
            'constructor' => 'channel',
            'access_hash' => self::CHANNEL_HASH,
        ]);
    }

    /**
     * Container seams, all doubles: the crawl contributes nothing, so what a
     * test reads is the `peer` param that reached `messages.getHistory`.
     */
    private function bind(DiscoveryScope $scope): DiscoveryScope
    {
        $this->app->instance(BackfillCommand::SCOPE_RESOLVER_KEY, static fn (): UserAccountScope => $scope);
        $this->app->bind(
            BackfillCommand::INGESTER_KEY,
            static fn (): callable => static fn (int $accountId): callable => static fn (array $batch): array => ['ingested' => 0],
        );
        $this->app->bind(
            BackfillCommand::CURSOR_STORE_KEY,
            static fn (): callable => static fn (int $accountId): MemoryCursorStore => new MemoryCursorStore,
        );

        return $scope;
    }

    /**
     * The peer exactly as it reached `messages.getHistory`. Reading it off the
     * recorded call — rather than off anything the command prints — is what
     * makes this a statement about the WIRE and not about a message.
     *
     * @return array<string, mixed>
     */
    private function peerOnHistory(DiscoveryScope $scope): array
    {
        foreach ($scope->calls as $call) {
            if ($call['method'] === 'messages.getHistory') {
                $peer = $call['params']['peer'] ?? null;

                self::assertIsArray($peer, 'the resolved peer must be an InputPeer, never a bare id');

                return $peer;
            }
        }

        self::fail('messages.getHistory was never dialled, so no peer resolution reached the wire');
    }
}
