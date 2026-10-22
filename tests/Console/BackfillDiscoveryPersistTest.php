<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Backfill\MemoryCursorStore;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * `teleframe:backfill --discover` PERSISTS the access_hashes it discovers.
 *
 * THE DEFECT THIS SUITE EXISTS FOR. `messages.getDialogs` answers with the
 * whole page: the dialogs, AND the companion `users` / `chats` vectors that
 * carry each peer's `access_hash`. `DialogDiscovery::accessHashIndex()` reads
 * those hashes, builds a fetchable InputPeer out of each one, and the command
 * then handed every one of them to a sink closure that ONLY printed:
 *
 *     function (array $entry): void { $this->line(sprintf('discovered %s …')); }
 *
 * So a live `--discover` printed real peers —
 *
 *     discovered channel:1915503741 {3:1915503741, top_message:12424}
 *     discovered user:8329480050    {1:8329480050,  top_message:103356}
 *
 * — and left `tf_channels = 0, tf_users_access_hash = 0, tf_chats = 0`. The
 * hashes Telegram had just handed over were read into memory and dropped. That
 * is not a cosmetic loss: `mirrorPeerLookup()` (BackfillCommand:318) resolves
 * `--peer=<id>` from exactly those tables, so while they stay empty EVERY
 * numeric `--peer` is refused with `peer_without_access_hash`, and the
 * operator's own documented escape hatch is the run that refills them.
 *
 * WHY THE SINK, NOT THE DISCOVERY: `DialogDiscovery` is right. Peer shapes,
 * `top_message`, the getDialogs cursor and the FLOOD_WAIT retry all work, and
 * the live output proves it. The page is already decoded and in hand when the
 * sink runs, so persisting it costs ZERO extra requests — discarding it was the
 * whole bug. Rewriting the walker to write would have coupled a pure,
 * transport-free pager to the database for no gain.
 *
 * WHY THE MIRROR ROUTE, NOT `UpdateIngestor`: the dialogs/channels/users facts
 * are mirror-surface facts, and `src/Schema/schema/mirror-catalog.json` already
 * DECLARES the edges that carry them —
 *
 *     tf_dialogs <- messages.dialogs.dialogs
 *     tf_chats   <- messages.dialogs.chats
 *     tf_users   <- messages.dialogs.users   (access_hash is a 1:1 CHILD, so
 *                                               it lands in tf_users_access_hash)
 *
 * — so `MirrorNestedSourceWalker` finds them and
 * `MirrorIngesterFactory::buildResultRoute()` writes them. The seam already
 * existed and was already proven; it was simply never handed this page.
 * `UpdateIngestor::ingest()` is the wrong door here, and cannot be made the
 * right one from this file: its generic `messages`-list branch
 * (UpdateIngestor.php:322) resolves to `writeEnvelope()`, which iterates the
 * `messages` members and writes message rows ONLY — it never reads `users` or
 * `chats`, so an envelope carrying only those vectors stores NOTHING at all.
 * The task brief assumed that seam handled the companion vectors; the code
 * says otherwise, and this suite pins what actually lands.
 *
 * The route is the UNGATED one on purpose. `buildResultMirror()` applies
 * `MirrorResultPolicy` first, and this suite must not depend on an admission
 * list another file owns: narrow `teleframe.mirror.result_methods`, or a policy
 * that does not carry `messages.getDialogs`, and the fix silently evaporates —
 * `--discover` prints peers again and stores nothing, with no error anywhere.
 * `buildResultRoute()` is documented as the write seam "a caller that has
 * already decided to mirror holds the decision itself", and this caller HAS
 * decided, at the fetch, for the account it fetched for.
 *
 * WHY THIS SUITE EXECUTES THE SHIPPED MIGRATIONS: `IngestTestCase` runs the
 * whole curated dial, so tf_dialogs / tf_chats / tf_channels /
 * tf_users_access_hash exist because the shipped DDL creates them, not because
 * this file re-typed a column list. Assertions are about ROWS THAT EXIST and
 * about the VALUE the payload carried — a count alone would pass on a row
 * holding access_hash 0, which is exactly the "looks resolved but is not"
 * shape `usableHash()` exists to reject.
 *
 * Provenance: the page shape is layer-227 `messages.dialogs`
 * (schema/sources/TL_telegram_v227.tl:2487 `messages.getDialogs#a0f4cb4f`
 * returns `messages.Dialogs`; api.tl:286 `messages.dialogs#15ba6c40 dialogs:
 * Vector<Dialog> messages:Vector<Message> chats:Vector<Chat>
 * users:Vector<User>`). The peer ids and the expected printed lines mirror the
 * live output recorded in the goals index (channel 1915503741 top_message
 * 12424, user 8329480050 top_message 103356), so the fixture is the operator's
 * own account shape rather than an invented one.
 */
final class BackfillDiscoveryPersistTest extends IngestTestCase
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
        return [self::ACCOUNT];
    }

    private const ACCOUNT = 7;

    /**
     * THE FIX, asserted on the rows: after one `--discover` the access_hash
     * the page carried is in `tf_users_access_hash`, the user fact is in
     * `tf_users`, and every dialog is in `tf_dialogs`.
     *
     * The VALUE is asserted, not only the count: a row written with
     * access_hash 0 would satisfy a count-only assertion and still leave every
     * `--peer` refused, because `usableHash()` treats 0 as "Telegram has not
     * told us".
     */
    public function test_discover_persists_the_access_hashes_it_printed(): void
    {
        $scope = $this->bind();

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--discover' => true])
            ->expectsOutputToContain(sprintf('discovered channel:%d', DiscoveryScope::CHANNEL_ID))
            ->expectsOutputToContain(sprintf('discovered user:%d', DiscoveryScope::USER_ID))
            ->assertExitCode(0);

        $db = $this->db();

        self::assertSame(
            1,
            (int) $db->table('tf_users_access_hash')->count(),
            'the companion users vector of the dialogs page carries the user access_hash, and it must land',
        );
        self::assertSame(
            DiscoveryScope::USER_HASH,
            (string) $db->table('tf_users_access_hash')->where('id', DiscoveryScope::USER_ID)->value('access_hash'),
            'the VALUE the payload carried — a count alone would pass on a row holding access_hash 0',
        );
        self::assertSame(
            self::ACCOUNT,
            (int) $db->table('tf_users_access_hash')->value('account_id'),
            'an access_hash is per-account state; the row must be scoped to the account it was fetched FOR',
        );

        // The user fact itself, so the child hash is anchored to the parent it
        // describes rather than floating free of it. The hash is NOT a
        // `tf_users` column — the shipped DDL puts it on the 1:1 child table
        // (2026_09_14_200001_create_tf_users_tables.php:104), which is exactly
        // the table `mirrorPeerLookup()` reads.
        self::assertSame(
            1,
            (int) $db->table('tf_users')->where('id', DiscoveryScope::USER_ID)->count(),
            'the page\'s companion users vector places the user fact the hash belongs to',
        );
        self::assertSame(
            'Someone',
            (string) $db->table('tf_users_first_name')->where('id', DiscoveryScope::USER_ID)->value('first_name'),
            'and the 1:1 children land too (first_name), proving the page went through the declared-edge '
            .'walk rather than being hand-picked apart by this command',
        );

        // The page's `dialogs` vector is the third declared edge, so it lands
        // too: one tf_dialogs row per dialog, keyed on the shipped
        // (account_id, peer_type, peer_id) primary key.
        self::assertSame(
            3,
            (int) $db->table('tf_dialogs')->count(),
            'the dialogs vector of the page places one tf_dialogs row per dialog',
        );
        self::assertSame(
            12424,
            (int) $db->table('tf_dialogs')->where('peer_id', DiscoveryScope::CHANNEL_ID)->value('top_message'),
            'the channel dialog keeps the top_message the printed line already showed',
        );

        // ONE page fetched, and the walk still ends where it used to: the fix
        // is a sink, not an extra request and not a second pagination pass.
        self::assertSame(1, $scope->dialogsPages, 'persisting the page must cost no extra request');
    }

    /**
     * `tf_chats` and `tf_channels` are TWO tables, and that changes what a
     * `tf_chats` row MEANS. `mirrorPeerLookup()`'s third branch answers
     * `inputPeerChat` for a peer classified as a group. Applied to a `channel`
     * row that names a DIFFERENT PEER on the wire — a megigroup sent as a
     * basic group, which Telegram answers `CHAT_ID_INVALID` to, spending a
     * budgeted request to learn nothing. Before the peer_type rewrite the branch
     * was accidentally safe because nothing ever wrote the table; persisting the
     * page removes that accident, so the branch must be gated on the constructor
     * it actually requires.
     *
     * THE PRECONDITION MOVED TABLE, 2026-10-11, because its premise was
     * DISPROVEN — and the product was wrong where it followed from.
     *
     * This assertion used to read the channel's constructor off `tf_chats`, on
     * the stated premise that "the catalog's entry admits `chat`, `chatEmpty`,
     * `chatForbidden`, `channel` and `channelForbidden` alike". MEASURED on a
     * `messages.getDialogs` page carrying one `channel` (1915503741,
     * access_hash 9172635481029) and one basic `chat` (444000555):
     *
     *   tf_chats             1 row  id=444000555 constructor='chat'
     *   tf_channels          1 row  id=1915503741 constructor='channel'
     *                                   access_hash=9172635481029
     *   tf_chats_access_hash 0 rows
     *
     * So the premise is false and the split is CORRECT: the shipped `Chat`
     * union is deliberately routed across two tables by
     * `Naming::CTOR_DOMAIN_OVERRIDES` (`src/Schema/Generator/Naming.php:131-133`)
     * — a channel id is NEGATIVE and `tf_channels.id` is the peer FK target for
     * `peer_type 3` (`2026_09_14_200002_create_tf_chats_tables.php:13-15`,
     * `2026_09_14_200003_create_tf_channels_tables.php:12-14`) — and
     * `MirrorNestedSourceWalker::tableFor()` routes each item of a `chats`
     * VECTOR by its OWN constructor, which is the only way a single vector can
     * feed two tables. Reading `tf_chats` for the channel asserted a layout the
     * schema does not have.
     *
     * BUT THE PREMISE WAS NOT THE ONLY THING WRONG, and this is why the fix was
     * NOT "correct the test and move on". `mirrorPeerLookup()`'s channel branch
     * read `tf_chats_access_hash` on the same disproven premise — "there is no
     * `tf_channels` entry in the catalog at all, and nothing in `src/` writes
     * it, so reading it could only ever find a row nobody puts there"
     * (`BackfillCommand.php:548-560`, deleted). With the hash living on
     * `tf_channels.access_hash`, that read found nothing and `--peer=1915503741`
     * was REFUSED with `peer_without_access_hash` and exit code 1 — a peer the
     * mirror had stored, refused, which is the precise defect this lookup
     * exists to prevent. So the assertion was not merely mis-aimed: following it
     * to the table the channel actually lands in is what FOUND the product
     * defect. It is moved, not weakened — the constructor it reads is the same
     * constructor, on the table the schema actually gives it, and the wire
     * assertions below are untouched and still carry the test.
     *
     * Prior history, kept because it is the same class of mistake in the other
     * direction: an earlier version of this test asserted the discovered channel
     * stays REFUSED, which was true only because `mirrorPeerLookup()` branched on
     * the SIGN of the id (`DialogDiscovery` reports channels POSITIVE, so
     * `$peerId < 0` was never true and the channel branch was dead code). That
     * inversion, 2026-10-02, made the channel resolve AS A CHANNEL — which is
     * what makes this a real test of the gate: the peer reaches the wire, so the
     * constructor it arrived with is observable, and it is `inputPeerChannel` —
     * never the `inputPeerChat` that would have named a different peer.
     */
    public function test_a_channel_row_is_not_mistaken_for_a_basic_group(): void
    {
        $db = $this->db();

        $this->bind();
        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--discover' => true])
            ->assertExitCode(0);

        // The SAME constructor, read from the table the shipped split gives it.
        self::assertSame(
            'channel',
            (string) $db->table('tf_channels')->where('id', DiscoveryScope::CHANNEL_ID)->value('constructor'),
            'precondition: the channel landed in tf_channels as a channel, which is the row the gate must read',
        );

        // And it landed in ONLY ONE of the two tables, which is what makes the
        // gate's own question well posed. `tf_chats` is the basic-group table;
        // a channel row there would be the confusion this test exists to catch,
        // so its ABSENCE is asserted rather than assumed.
        self::assertSame(
            0,
            (int) $db->table('tf_chats')->where('id', DiscoveryScope::CHANNEL_ID)->count(),
            'the channel must NOT also appear in tf_chats: that table holds chatEmpty/chat/chatForbidden only '
            .'(Naming::CTOR_DOMAIN_OVERRIDES), and a duplicate row is the double-write this split exists to prevent',
        );

        // The channel RESOLVES, and as a channel: `tf_dialogs.peer_type = 3` is
        // what says so, not the sign of the id. The resolved peer is read off
        // the recorded `messages.getHistory` call, so this is a statement about
        // the wire and not about a log line.
        $scope = $this->bind();

        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT,
            '--peer' => [(string) DiscoveryScope::CHANNEL_ID],
            '--no-persist' => true,
        ])->assertExitCode(0);

        $channel = null;
        foreach ($scope->calls as $call) {
            if ($call['method'] === 'messages.getHistory') {
                $channel = $call['params']['peer'] ?? null;
            }
        }

        self::assertSame(
            'inputPeerChannel',
            (string) (is_array($channel) ? ($channel['_'] ?? '') : ''),
            'a megogroup must go out as a channel: inputPeerChat would name a different peer on the wire '
            .'and buy a CHAT_ID_INVALID with a budgeted request',
        );
        self::assertSame(
            DiscoveryScope::CHANNEL_HASH,
            (string) (is_array($channel) ? ($channel['access_hash'] ?? '') : ''),
            'with the hash the discovery page itself carried — asserted as a VALUE, so a peer with the right '
            .'constructor and no hash cannot pass',
        );

        // POSITIVE: a real basic group carries no hash BY DESIGN and is
        // addressable, so refusing it would be its own defect.
        self::assertSame(
            'chat',
            (string) $db->table('tf_chats')->where('id', DiscoveryScope::CHAT_ID)->value('constructor'),
            'precondition: the basic group landed with the basic-group constructor',
        );

        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT,
            '--peer' => (string) DiscoveryScope::CHAT_ID,
            '--no-persist' => true,
        ])->doesntExpectOutputToContain(BackfillCommand::PEER_UNRESOLVED)
            ->assertExitCode(0);
    }

    /**
     * WHY PERSIST AT ALL, asserted end to end through the public surface.
     *
     * Before the fix a numeric `--peer` was refused with
     * `peer_without_access_hash` because `mirrorPeerLookup()` reads
     * `tf_channels` / `tf_users_access_hash` and both were empty — and the
     * refusal message tells the operator to crawl with `--full`. So after
     * `--discover` the SAME numeric id must now resolve, with the hash the
     * discovery page carried, and the run must get past the refusal.
     */
    public function test_a_numeric_peer_becomes_crawlable_after_one_discover_run(): void
    {
        $this->bind();

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--discover' => true])
            ->assertExitCode(0);

        // A SECOND account stays empty on purpose: the tables are scoped by
        // account_id, and an access_hash is per-account state. Reading another
        // tenant's hash would be reading another account's data.
        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT + 1,
            '--peer' => (string) DiscoveryScope::USER_ID,
            '--no-persist' => true,
        ])->expectsOutputToContain(BackfillCommand::PEER_UNRESOLVED)
            ->assertExitCode(1);

        // Same id, the account that discovered it: resolved, so the run
        // proceeds past the refusal instead of failing on it.
        $this->artisan('teleframe:backfill', [
            '--account' => self::ACCOUNT,
            '--peer' => (string) DiscoveryScope::USER_ID,
            '--no-persist' => true,
        ])->doesntExpectOutputToContain(BackfillCommand::PEER_UNRESOLVED)
            ->assertExitCode(0);
    }

    /**
     * THE CONTRACT `--discover` NOW HAS, pinned deliberately rather than left
     * to the help text.
     *
     * It is no longer "no writes", and that is the deliberate call: the page
     * was ALREADY fetched and decoded, so persisting it spends no request and
     * makes `--peer=<id>` usable for the next run — which is the whole reason
     * the refusal exists. The half that stays read-only is the half the help
     * text still promises: no CRAWL. `getHistory` is never dialled, so no
     * message row is written and no history budget is spent.
     *
     * A test that only asserted "tf_messages is 0" would be satisfied by a
     * `--discover` that also crawled nothing at all, so the request side is
     * asserted too: the run must dial `messages.getDialogs` and NOTHING else
     * on the history path.
     */
    public function test_discover_still_crawls_nothing(): void
    {
        $scope = $this->bind();

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--discover' => true])
            ->assertExitCode(0);

        $methods = array_column($scope->calls, 'method');

        self::assertContains('messages.getDialogs', $methods, 'discovery must still fetch the dialogs');
        self::assertNotContains('messages.getHistory', $methods, '--discover is documented as no crawl');
        self::assertSame(
            0,
            (int) $this->db()->table('tf_messages')->count(),
            'no history page is fetched, so no message row may exist',
        );
    }

    /**
     * The cursor and ingester seams are container doubles so the CRAWL
     * contributes nothing; the dialogs page itself is NOT a seam and is routed
     * through the real mirror write path, because the defect is precisely that
     * nothing was ever routed.
     */
    private function bind(): DiscoveryScope
    {
        $scope = new DiscoveryScope;

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
     * The app's OWN migrated connection — `IngestTestCase` ran the curated dial
     * on it, and the command and the mirror route both resolve it.
     */
    private function db(): Connection
    {
        return $this->app->make('db')->connection();
    }
}
