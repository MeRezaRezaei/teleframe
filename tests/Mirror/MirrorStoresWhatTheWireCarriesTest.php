<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Database\ConnectionInterface;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * THE MIRROR MUST STORE WHAT THE WIRE CARRIES — NOT WHAT THE CATALOG SPELLS.
 *
 * Every major defect found in the 2026-10-02 audit was faithful to an INTERNAL
 * CONVENTION and blind to the real input:
 *   - a credential scanner whose alphabet did not contain the credential's;
 *   - a `PortabilityScanner` that understood one PHP spelling, not the wire's;
 *   - a nested-source walker keyed on a namespace-qualified ctor the wire
 *     never sent, so a real page matched no edge and stored nothing;
 *   - a peer resolver that keyed on a COLUMN NAME instead of an identity;
 *   - and a test fixture that spelled the constructor the way the CATALOG does.
 * That last one is why this file exists: it hid a real defect behind green.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE TWO SPELLINGS, AND WHERE EACH WAS MEASURED
 * ─────────────────────────────────────────────────────────────────────────
 *
 * WIRE SPELLING — what a decoded `messages.getDialogs` result's `_` carries.
 * Measured by encoding each page through the real `TLEncoder` and decoding it
 * back through the real `TLDecoder`; the `_` that comes out is what the codec
 * resolved from the constructor ID, so it cannot be a fixture's invention:
 *
 *   messages.dialogsSlice#71e094f3 -> decoded '_' = 'messages.dialogsSlice'
 *   messages.dialogs#15ba6c40      -> decoded '_' = 'messages.dialogs'
 *
 * and, read straight off the shipped registry (`TLRegistry`):
 *
 *   TLRegistry::isRegistered('messages.dialogs')      === true
 *   TLRegistry::isRegistered('messages.dialogsSlice') === true
 *   TLRegistry::nameOf(TLRegistry::id('messages.dialogs'))
 *       === 'messages.dialogs'      (id 0x15ba6c40)
 *   TLRegistry::nameOf(TLRegistry::id('messages.dialogsSlice'))
 *       === 'messages.dialogsSlice' (id 0x71e094f3)
 *
 * BARE `dialogs` — NOT A REGISTERED CONSTRUCTOR:
 *
 *   TLRegistry::isRegistered('dialogs')  === false
 *   TLRegistry::id('dialogs')            throws
 *       InvalidArgumentException: "TLRegistry: unknown constructor 'dialogs'"
 *   TLEncoder::encodeObject('dialogs', …) throws the same, so a fixture written
 *       in that spelling cannot even be BUILT.
 *
 * The bare spelling is where the CATALOG lives: `Naming::TYPE_TO_DOMAIN` maps
 * the TL TYPE `Dialog` to the domain `dialogs`
 * (src/Schema/Generator/Naming.php:47), and that is a table-naming convention,
 * not a constructor name. The three scheme lines are
 * `schema/sources/TL_telegram_v227.tl:279-281`:
 *
 *   279  messages.dialogs#15ba6c40 dialogs:Vector<Dialog> messages:Vector<Message> chats:Vector<Chat> users:Vector<User> = messages.Dialogs;
 *   280  messages.dialogsSlice#71e094f3 count:int dialogs:Vector<Dialog> messages:Vector<Message> chats:Vector<Chat> users:Vector<User> = messages.Dialogs;
 *   281  messages.dialogsNotModified#f0e3e596 count:int = messages.Dialogs;
 *
 * THE CATALOG SPELLING — where the mirror declares the edges
 * (src/Schema/schema/mirror-catalog.json), qualified, because the walker does
 * one exact lookup on the node's `_`:
 *
 *   :341-349   "tf_chats"   -> messages.dialogs.chats       (:343)
 *                            -> messages.dialogsSlice.chats  (:347)
 *   :410-419   "tf_dialogs" -> messages.dialogs.dialogs     (:412)
 *                            -> messages.dialogsSlice.dialogs (:416)
 *   :1737-1745 "tf_users"   -> messages.dialogs.users       (:1739)
 *                            -> messages.dialogsSlice.users   (:1743)
 *
 * RE-READ ON 2026-10-11: the `tf_dialogs` and `tf_users` source ranges above
 * were STALE (they read :413-421 and :1763-1771) after the catalog enrichment.
 * They are corrected here. The `tf_chats` range :341-349 was already right.
 *
 * `tf_channels` (`:1984-2115`) is the FOURTH table on this page and it is
 * shaped differently on purpose: it declares `channel` / `channelForbidden`
 * (`:1986-1989`) and it has NO `sources` key at all. It is not reached by an
 * edge on a parent page; `MirrorNestedSourceWalker::tableFor()` claims it by
 * CTOR NAME while walking the very same `chats` vector. See the split section
 * below.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE `Chat` SPLIT — WHY A `channel` ON THIS PAGE IS NOT IN `tf_chats`
 * ─────────────────────────────────────────────────────────────────────────
 *
 * A reader who finds `self::CHANNEL_ID` asserted against `tf_channels` will
 * reasonably suspect the fixture was bent to fit a bug. It was not; the split
 * is what the SHIPPED DDL already required, and this file was the last thing
 * still asserting the pre-split world.
 *
 * `Chat` is ONE five-constructor union — chatEmpty / chat / chatForbidden /
 * channel / channelForbidden — and BOTH `channel` ctors declare `= Chat;`, so
 * the result type routes them to the chats domain by name. The shipped tables
 * do not agree with that: they carry FOUR tables, not one.
 *
 *   tf_chats    <- chatEmpty / chat / chatForbidden   (peer FK target, type 2)
 *   tf_channels <- channel / channelForbidden         (peer FK target, type 3)
 *
 * The three hand-authored migrations say so in their own headers:
 *
 *   2026_09_14_200002_create_tf_chats_tables.php:12-15
 *       "The channel / channelForbidden ctors resolve to Chat but are routed to
 *        tf_channels (Naming::CTOR_DOMAIN_OVERRIDES) — so this table is the peer
 *        FK target for peer_type 2 … and holds chatEmpty / chat / chatForbidden."
 *   2026_09_14_200003_create_tf_channels_tables.php:12-14
 *       "The channel / channelForbidden ctors declare `= Chat;` but are routed
 *        to their own table via Naming::CTOR_DOMAIN_OVERRIDES ('channel' →
 *        channels) — tf_channels is the peer FK target for peer_type 3."
 *   2026_09_14_200002_create_tf_chats_tables.php:13
 *       names the same override that 200003 names.
 *
 * AND THE OVERRIDE IS REAL CODE, not prose —
 * `src/Schema/Generator/Naming.php:131-133`:
 *
 *   private const CTOR_DOMAIN_OVERRIDES = [
 *       'channel' => 'channels',
 *       'channelForbidden' => 'channels',
 *   ];
 *
 * CONSEQUENCE FOR THIS TEST, and the reason the assertions below moved rather
 * than weakened. The page's `chats` vector carries a `chat` AND a `channel`.
 * Before the split was honoured both landed in `tf_chats`; now each lands in the
 * table its own ctor names. The two facts this file exists to pin split along
 * with the tables:
 *
 *   - `title` is a shared fact of BOTH ctors, so it is asserted twice — once
 *     against `tf_chats` for the chat, once against `tf_channels` for the
 *     channel. Same fact, two homes, both real.
 *   - `access_hash` is NOT a shared fact (it is `flags.13?long` on `channel`,
 *     TL:104, and `chat` does not carry it at all), so it lands on exactly ONE
 *     row, in exactly ONE table — `tf_channels.access_hash`, which is an INLINE
 *     column. MEASURED against the shipped migration
 *     (`2026_09_14_200003:63`, `$table->bigInteger('access_hash')->default(0)`)
 *     there is NO `tf_channels_access_hash` table, so the asymmetry is now
 *     asserted across two DIFFERENT tables rather than two rows of one.
 *
 * DRIFT WORTH KNOWING (out of this file's reach): the docblock of
 * `2026_09_29_000400_create_tf_chats_access_hash_table.php:35-38` still asserts
 * "`tf_channels` is NOT a catalog table — it appears nowhere in the NF5 spec and
 * nothing in `src/` writes it… Reading it was the actual bug". That was TRUE
 * before the enrichment and is FALSE now: `tf_channels` is a catalog table
 * (`mirror-catalog.json:1984`) with a shipped migration and a writer. It is
 * flagged here rather than fixed here because this file owns only itself. Its
 * own table, `tf_chats_access_hash`, stays in the migration list and stays a
 * real 1:1 child of `tf_chats` — the chat simply carries no such fact, so it
 * correctly ends up with no row.
 *
 *
 * The fixtures below are ENCODE ARGS, and every test encodes them with the real
 * `TLEncoder` and decodes them with the real `TLDecoder` before anything reads
 * them. That is the whole point of the file, and it is not ceremony:
 *
 *  - `TLEncoder` walks the DECLARED fields and DROPS any key the signature does
 *    not declare, silently. So a fixture that invents a field — the exact habit
 *    that hid the defect — cannot survive the encode step unnoticed; the field
 *    is gone before the mirror is asked to store it. `test_a_hash_written_into_
 *    a_peer_vanishes_before_the_mirror_is_asked` pins that behaviour as its
 *    own fact.
 *  - the payload the mirror stores is therefore the DECODED BYTES, not the PHP
 *    array the test wrote. An assertion can no longer pass on a value the wire
 *    never carried.
 *
 * Every `access_hash` below is synthetic and hand-chosen. This repo has no live
 * account; `access_hash` as a COLUMN name is the subject, never a real value.
 *
 * HONEST LIMIT: this proves what the shipped TL, the shipped codec and the
 * shipped mirror do with a page shaped from layer 227. It does not prove what
 * Telegram's servers return — that needs a live session, which is operator-only
 * and out of scope here. What it does prove is that the layer-227 wire spelling
 * reaches the row, which is the half every 2026-10-02 defect broke.
 *
 * Provenance: schema/sources/TL_telegram_v227.tl:73,75,88,89,102,104,121,212,213,245,279-281;
 * src/Schema/schema/mirror-catalog.json:334,341-349,404,410-419,1731,1737-1745,1984-2115;
 * src/Schema/Generator/Naming.php:131-133;
 * src/Laravel/Migrations/2026_09_14_200001_create_tf_users_tables.php:63,104,111,125,
 *   2026_09_14_200002_create_tf_chats_tables.php:12-15,48,
 *   2026_09_14_200003_create_tf_channels_tables.php:12-14,58-95,
 *   2026_09_14_200004_create_tf_dialogs_tables.php:51,79,
 *   2026_09_29_000400_create_tf_chats_access_hash_table.php:62.
 */
final class MirrorStoresWhatTheWireCarriesTest extends TestCase
{
    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    /**
     * The migrations whose DDL these rows land in, NAMED AS FILES so the
     * shipped DDL is executed rather than transcribed. A test that re-types a
     * migration's columns is a second copy of them, and a second copy drifts —
     * the ingest suite already lived through that: it hand-built `tf_users`
     * with three boolean columns (the shipped table has thirty-one) and
     * `tf_dialogs` with a `top_message_id` column that does not exist.
     *
     * All five run into ONE in-memory connection: this page walks FIVE tables
     * (`tf_chats`, `tf_channels`, `tf_dialogs`, `tf_users` and their 1:1
     * children) in a single pass, so a per-test connection holding only one
     * migration would fail on the others — a fixture bug that reads exactly
     * like a mirror bug.
     *
     * WHY `200003_create_tf_channels_tables` IS IN THIS LIST. `Chat` is ONE
     * five-constructor union, but the shipped DDL deliberately SPLITS it in two
     * (see the class docblock, "THE `Chat` SPLIT"). A `channel` on this page
     * therefore lands in `tf_channels`, not `tf_chats` — so if this list omits
     * `200003`, the walker correctly targets a table that was never created and
     * the failure reads as a mirror bug when it is a fixture bug. Listed in
     * apply order, immediately after its `tf_chats` sibling `200002`.
     *
     * @var list<string>
     */
    private const MIGRATIONS = [
        __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200001_create_tf_users_tables.php',
        __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200002_create_tf_chats_tables.php',
        __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200003_create_tf_channels_tables.php',
        __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php',
        __DIR__.'/../../src/Laravel/Migrations/2026_09_29_000400_create_tf_chats_access_hash_table.php',
    ];

    private const ACCOUNT = 7;

    // ── Synthetic, hand-chosen. No real account's hash is in this file. ─────

    /** `user.access_hash:flags.0?long` (TL:89) for the FIRST peer dialog. */
    private const USER_HASH_4242 = 111222333444555666;

    /** `user.access_hash:flags.0?long` (TL:89) for the SECOND peer dialog. */
    private const USER_HASH_4243 = 333333444444555666;

    /** `channel.access_hash:flags.13?long` (TL:104) for the THIRD peer dialog. */
    private const CHANNEL_HASH = 777888999000111222;

    private const USER_ID_4242 = 4242;

    private const USER_ID_4243 = 4243;

    /** The page carries this user with NO `access_hash` at all — see `userEmpty` (TL:88). */
    private const USER_ID_NO_HASH = 4244;

    private const CHAT_ID = 555;

    private const CHANNEL_ID = 1001234567890;

    // ─────────────────────────────────────────────────────────────────────
    // TEST 1 — THE INVARIANT. Both registered page constructors of the
    // `messages.Dialogs` result type, spelled as the codec spells them.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, array{string}>
     */
    public static function pageConstructors(): array
    {
        return [
            // Both are MEASURED registered constructors of the SAME result
            // type (TL:279 and TL:280), and both are declared by the catalog
            // for all three companion tables. The defect this file's sibling
            // `SavedDialogsSlicePlacesRowTest` documents was the catalog
            // declaring only ONE of a result type's ctors — so a page-level
            // test that walked only the slice could not have caught it twice.
            'messages.Dialogs — full page' => ['messages.dialogs'],
            'messages.DialogsSlice — counted page' => ['messages.dialogsSlice'],
        ];
    }

    /**
     * One property, end to end: a realistic `messages.getDialogs` page lands
     * every fact it carries, WITH ITS VALUES.
     *
     * WHY VALUES AND NOT COUNTS. A row written with `access_hash = 0` satisfies
     * any count assertion and still leaves every `--peer` refused, because
     * `usableHash()` reads 0 as "Telegram has not told us". The counts are
     * here as a floor; the hash VALUES and the `top_message` / `title` /
     * `first_name` values are what make this test mean anything.
     *
     * @param  string  $pageCtor  the wire spelling under test
     */
    #[DataProvider('pageConstructors')]
    public function test_a_get_dialogs_page_lands_every_fact_with_its_values(string $pageCtor): void
    {
        self::assertTrue(
            TLRegistry::isRegistered($pageCtor),
            "the page spelling under test must be a REGISTERED constructor — '{$pageCtor}' is not on the wire",
        );

        // Build it the way the wire builds it: encode, then read the bytes back.
        $bytes = TLEncoder::encodeObject($pageCtor, $this->pageArgsFor($pageCtor));
        $offset = 0;
        $payload = TLDecoder::decodeObject($bytes, $offset);

        // The page arrived under the spelling the codec resolved, not the one
        // this test happened to type.
        self::assertSame($pageCtor, $payload['_'], 'the decoded page must carry the wire spelling in its `_`');

        // And that spelling is PHYSICALLY in the buffer: TL ids travel
        // little-endian, so it is the byte-reversed form that must appear. This
        // is the assertion the catalog's spelling could never satisfy.
        self::assertStringContainsString(
            $this->littleEndianHex(TLRegistry::id($pageCtor)),
            bin2hex($bytes),
            "the {$pageCtor} constructor id must be literally in the encoded bytes",
        );

        $db = $this->shippedConnection();
        $write = $this->ingest($db, $payload);

        // A page that places must place CLEANLY: no mapping clue, no FK clue,
        // no value clue. A clue here means a fact on this page had nowhere to
        // go, which is precisely the silent loss class.
        self::assertSame([], $write['clues'], 'the page must map with no unmapped-field clue');
        self::assertSame([], $write['fkClues'], 'and with no FK clue');
        self::assertSame([], $write['unmappedClues'], 'and the writer must report nothing unmapped');
        self::assertSame([], $write['valueClues'], 'and no value that failed to fit its column');

        // ── tf_dialogs: the page's own vector, three rows, three identities ──
        //
        // Identity IS the peer here: there is no telegram-supplied dialog id,
        // so the composite key is (account_id, peer_type, peer_id) and the peer
        // shape is inline on the parent row.
        $dialogs = $db->table('tf_dialogs')->orderBy('peer_id')->get();
        self::assertCount(3, $dialogs, 'all THREE dialogs on the page must place, not just the first');

        self::assertSame(
            ['dialog', 'dialog', 'dialog'],
            $dialogs->pluck('constructor')->all(),
            'the discriminator must be the ctor the wire sent — TL:279 gives `messages.Dialogs` two ctors and this page uses `dialog` (TL:212)',
        );

        // peer_type is EXPANDED from the peer field, not stored as the peer's
        // constructor name: 1=user, 2=chat, 3=channel.
        self::assertSame(
            [['peer_type' => 1, 'peer_id' => self::USER_ID_4242], ['peer_type' => 1, 'peer_id' => self::USER_ID_4243], ['peer_type' => 3, 'peer_id' => self::CHANNEL_ID]],
            $dialogs->map(fn ($r): array => ['peer_type' => (int) $r->peer_type, 'peer_id' => (int) $r->peer_id])->all(),
            'the peer must be expanded into (peer_type, peer_id) — 1=user, 3=channel',
        );

        $first = $dialogs->firstWhere('peer_id', self::USER_ID_4242);
        self::assertSame(7001, (int) $first->top_message, 'top_message must come from the child payload');
        self::assertSame(7000, (int) $first->read_inbox_max_id, 'and so must read_inbox_max_id');
        self::assertSame(3, (int) $first->unread_count, 'unread_count is a required field of `dialog` (TL:212), not a flag — it must land');
        self::assertSame(1, (int) $first->pinned, 'pinned:flags.2?true must be stored when the flag word sets bit 2');

        $second = $dialogs->firstWhere('peer_id', self::USER_ID_4243);
        self::assertSame(7002, (int) $second->top_message, 'the SECOND row\'s own fields must be stored, not the first row\'s');
        self::assertSame(0, (int) $second->unread_count, 'and a zero must store as zero, not be read as absent');

        $third = $dialogs->firstWhere('peer_id', self::CHANNEL_ID);
        self::assertSame(7003, (int) $third->top_message, 'the channel dialog carries its own top_message');
        self::assertSame(1, (int) $third->unread_mark, 'unread_mark:flags.3?true on the THIRD row must be stored too — flag handling is per row');

        // ── tf_users + tf_users_access_hash: the companion USERS vector ──────
        //
        // THE HASH VALUE, not the row count. This is the assertion the whole
        // file is built around: `access_hash` is `flags.0?long` on `user`
        // (TL:89), so it only reaches the store if the flag word survives the
        // encode AND the decode AND the decomposition.
        self::assertSame(
            [['id' => 4242, 'constructor' => 'user'], ['id' => 4243, 'constructor' => 'user'], ['id' => 4244, 'constructor' => 'userEmpty']],
            $db->table('tf_users')->orderBy('id')->get()->map(fn ($r): array => ['id' => (int) $r->id, 'constructor' => $r->constructor])->all(),
            'every user of the companion vector places, discriminated by its real ctor',
        );

        $hashes = $db->table('tf_users_access_hash')->orderBy('id')->get()
            ->mapWithKeys(fn ($r): array => [(int) $r->id => (int) $r->access_hash])->all();
        self::assertSame(
            [self::USER_ID_4242 => self::USER_HASH_4242, self::USER_ID_4243 => self::USER_HASH_4243],
            $hashes,
            'the access_hash each user carried must land WITH ITS VALUE — a row written as 0 satisfies any count assertion and leaves every --peer refused',
        );
        self::assertArrayNotHasKey(
            self::USER_ID_NO_HASH,
            $hashes,
            '`userEmpty` (TL:88) carries no access_hash, so no row may exist for it — row existence IS fact existence',
        );

        // ── tf_chats: the CHAT half of the `Chat` union ─────────────────────
        //
        // This page's `chats` vector carries TWO different ctors of ONE union,
        // and the shipped DDL splits that union across TWO tables (class
        // docblock, "THE `Chat` SPLIT"). The `chat` is asserted here; the
        // `channel` is asserted against `tf_channels` below. Both are strict.
        self::assertSame('synthetic chat', (string) $db->table('tf_chats')->where('id', self::CHAT_ID)->value('title'), 'the CHAT half of the union must land its title on tf_chats with the wire value');
        self::assertSame('chat', (string) $db->table('tf_chats')->where('id', self::CHAT_ID)->value('constructor'), 'tf_chats must carry the real ctor the wire sent, not the union name Chat');
        self::assertSame(
            2,
            (int) $db->table('tf_chats')->where('id', self::CHAT_ID)->value('participants_count'),
            'chat.participants_count is a REQUIRED field of `chat` (TL:102) — it must be 2, not the wire-false sentinel',
        );

        // ── tf_channels: the CHANNEL half, and the access_hash asymmetry ─────
        //
        // `access_hash` is NOT one of the `Chat` union's shared facts: `chat`
        // does not carry it at all, only `channel` / `channelForbidden` do
        // (`flags.13?long`, TL:104). MEASURED against the shipped migration
        // (2026_09_14_200003:63) the channel's hash is an INLINE column and
        // there is NO `tf_channels_access_hash` table — so the asymmetry is now
        // asserted across two DIFFERENT tables, which is a STRONGER statement
        // than the two-rows-of-one-table form it replaces: the chat must carry
        // NO hash anywhere, and the channel must carry its real one.
        self::assertSame(
            'synthetic channel',
            (string) $db->table('tf_channels')->where('id', self::CHANNEL_ID)->value('title'),
            "the channel's title is a shared fact of `channel` (TL:104) and must land on the channel's OWN table — tf_chats does not hold channels",
        );
        self::assertSame(
            'channel',
            (string) $db->table('tf_channels')->where('id', self::CHANNEL_ID)->value('constructor'),
            'the row in tf_channels must carry the real ctor the wire sent',
        );
        self::assertSame(
            self::CHANNEL_HASH,
            (int) $db->table('tf_channels')->where('id', self::CHANNEL_ID)->value('access_hash'),
            "channel.access_hash is flags.13?long (TL:104) — a DIFFERENT flag bit from user's flags.0, and it must land with its own value",
        );

        // The other half of the asymmetry, which the old form asserted by
        // omission: the chat carries NO access_hash fact, so it must get no row
        // on the chat-side hash table either. This is the guard that catches a
        // hash being faked onto the chat.
        self::assertSame(
            [],
            $db->table('tf_chats_access_hash')->get()
                ->mapWithKeys(fn ($r): array => [(int) $r->id => (int) $r->access_hash])->all(),
            'the chat carries NO access_hash fact, so `tf_chats_access_hash` must be EMPTY — only channel/channelForbidden ever earn a hash row',
        );

        // POSITIVE: the split is REAL, not a rename. The two tables are
        // genuinely distinct, so a future change that folded them back together
        // cannot pass on the assertions above alone.
        self::assertNull(
            $db->table('tf_chats')->where('id', self::CHANNEL_ID)->value('title'),
            'the `Chat` split is real: the channel must NOT also land in tf_chats',
        );
        self::assertNull(
            $db->table('tf_channels')->where('id', self::CHAT_ID)->value('title'),
            'and symmetrically the chat must NOT land in tf_channels',
        );
        self::assertCount(
            1,
            $db->table('tf_channels')->get(),
            'one channel on the page means exactly one tf_channels row — no phantom, no duplicate',
        );

        // ── 1:1 children: row existence = fact existence ─────────────────────
        self::assertSame(
            ['syn4242'],
            $db->table('tf_users_username')->pluck('username')->all(),
            'user.username:flags.3?string (TL:89) is a 1:1 child row, and its VALUE must be the one the wire carried',
        );
        self::assertSame(
            ['Other', 'Syn'],
            $db->table('tf_users_first_name')->orderBy('first_name')->pluck('first_name')->all(),
            'and first_name lands per user — two users, two rows, two different values',
        );

        // `notify_settings` is a required field of `dialog` (TL:212) and a
        // single-ctor `PeerNotifySettings` (TL:245), so all three dialogs get
        // one row keyed by the same identity as the parent.
        $notify = $db->table('tf_dialogs_notify_settings')->orderBy('peer_id')->get();
        self::assertCount(3, $notify, 'a required field on all three dialogs must place a child row for each');
        self::assertSame(
            ['peerNotifySettings', 'peerNotifySettings', 'peerNotifySettings'],
            $notify->pluck('constructor')->all(),
            'the child carries the REAL ctor of the nested object',
        );
        self::assertSame(
            [self::USER_ID_4242, self::USER_ID_4243, self::CHANNEL_ID],
            $notify->map(fn ($r): int => (int) $r->peer_id)->all(),
            'and it is keyed by the DIALOG\'s identity, not by its own',
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // TEST 2 — THE GUARD. The bare `dialogs` spelling is not on the wire: it
    // was once claimed in prose by a now-rewritten suite, and prose hides.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * The bare spelling `dialogs` is NOT a registered constructor, so a
     * fixture written as `'_' => 'dialogs'` is testing the CATALOG's
     * vocabulary — `Naming::TYPE_TO_DOMAIN` maps the TL TYPE `Dialog` to the
     * domain name `dialogs` (src/Schema/Generator/Naming.php:47), and that is a
     * table-naming convention, not a constructor name.
     *
     * Kept as a test rather than a comment because a comment cannot fail. The
     * opposite claim WAS asserted in prose by `BackfillBareDialogsCtorTest`,
     * which asserted that `messages.getDialogs` "answers with the page under
     * the BARE type `'dialogs'`" — and prose is what let a green suite stand
     * on an inverted claim for a whole branch. That suite is now
     * `BackfillDialogsSliceCtorTest`, re-anchored on the SECOND REAL dialogs
     * ctor. If a future schema ever did register the bare form, this goes red
     * and every such sentence has to be re-argued.
     */
    public function test_the_bare_dialogs_spelling_is_not_a_registered_constructor(): void
    {
        self::assertFalse(
            TLRegistry::isRegistered('dialogs'),
            'the bare spelling is NOT a registered constructor — if it ever is, the docblock claims in this repo that say otherwise are wrong',
        );

        try {
            TLRegistry::id('dialogs');
            self::fail("resolving an id for 'dialogs' must throw — it has no canonical signature because it was never on the wire");
        } catch (\InvalidArgumentException $e) {
            self::assertSame(
                "TLRegistry: unknown constructor 'dialogs'",
                $e->getMessage(),
                'the refusal must name the unknown constructor rather than fail somewhere less legible',
            );
        }

        self::assertSame(
            'messages.dialogs',
            TLRegistry::nameOf(TLRegistry::id('messages.dialogs')),
            'while the WIRE spelling round-trips through the registry to its own qualified name',
        );
        self::assertSame(
            'messages.dialogsSlice',
            TLRegistry::nameOf(TLRegistry::id('messages.dialogsSlice')),
            'and so does the slice — nameOf is the inverse of id for both',
        );

        // The falsification, part one: a fixture in that spelling cannot even
        // be constructed, because the encoder asks the registry for the ctor.
        try {
            TLEncoder::encodeObject('dialogs', $this->pageArgs());
            self::fail("encoding a 'dialogs' page must be impossible — it is not a registered constructor");
        } catch (\InvalidArgumentException $e) {
            self::assertSame(
                "TLRegistry: unknown constructor 'dialogs'",
                $e->getMessage(),
                'and the refusal must name the unknown constructor rather than fail somewhere less legible',
            );
        }

        // The falsification, part two — and this is the dangerous half: hand the
        // walker a page typed in the catalog's spelling and it places NOTHING
        // and says NOTHING. MEASURED: 0 facts, no exception, no clue. A page
        // routed to a lookup that cannot match is indistinguishable, from the
        // output, from a page that genuinely has no facts.
        $walker = new MirrorNestedSourceWalker($this->shippedCatalog());
        $bare = ['_' => 'dialogs'] + $this->pageArgs();

        $facts = iterator_to_array($walker->collect($bare));
        self::assertSame([], array_values($facts), 'a `dialogs`-spelled page must place no facts at all — the walk cannot match it');

        // …while the SAME page one string different — the wire spelling — does.
        // That one-string difference is the entire defect class of this file.
        $wireFacts = iterator_to_array($walker->collect(['_' => 'messages.dialogs'] + $this->pageArgs()));
        self::assertNotSame([], array_values($wireFacts), 'the wire spelling of the very same page must place facts — otherwise this guard proves nothing');
    }

    // ─────────────────────────────────────────────────────────────────────
    // TEST 3 — WHY THE FIXTURE IS ENCODED. An invented field dies silently.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * `peerUser` (TL:73) is `peerUser#59511722 user_id:long = Peer` — it carries
     * NO `access_hash`, and neither does `peerChannel` (TL:75). At layer 227
     * the hashes live only in the companion `users` / `chats` vectors, which is
     * why TEST 1's hashes are asserted there and not on the peers.
     *
     * So a fixture written `['_' => 'peerUser', 'user_id' => 4242,
     * 'access_hash' => …]` — which is exactly what the shipped
     * `SavedDialogsSlicePlacesRowTest::SLICE` does — carries an invented field.
     * MEASURED behaviour of the real encoder on such a payload: the key is
     * DROPPED, no exception, no log, and the decoded peer simply has no
     * `access_hash`. A test asserting only "the page placed 3 rows" is green on
     * a page whose hash never existed. That is the silent kind of wrong, and it
     * is why TEST 1 encodes its fixtures.
     */
    public function test_a_hash_written_into_a_peer_vanishes_before_the_mirror_is_asked(): void
    {
        // The schema itself is the first authority: no peer carries a hash.
        self::assertSame('peerUser#59511722 user_id:long = Peer', TLRegistry::signature('peerUser'), 'peerUser declares only user_id — the canonical line is the first authority on that');
        self::assertSame('peerChannel#a2a5371e channel_id:long = Peer', TLRegistry::signature('peerChannel'), 'and peerChannel declares only channel_id — neither peer carries a hash');
        self::assertNotContains(
            'access_hash',
            $this->declaredFieldNames('peerUser'),
            'peerUser declares no access_hash field — TL:73',
        );
        self::assertNotContains(
            'access_hash',
            $this->declaredFieldNames('peerChannel'),
            'and peerChannel declares none either — TL:75',
        );

        // And the encoder's behaviour on an invented one: present in the
        // fixture, absent from the bytes, with no error anywhere.
        $invented = TLEncoder::encodeObject('peerUser', ['user_id' => self::USER_ID_4242, 'access_hash' => self::USER_HASH_4242]);
        $offset = 0;
        $decoded = TLDecoder::decodeObject($invented, $offset);

        self::assertArrayNotHasKey(
            'access_hash',
            $decoded,
            'the encoder must DROP an undeclared key — so a fixture asserting on it is asserting on nothing',
        );
        self::assertSame(self::USER_ID_4242, $decoded['user_id'], 'while the declared field is untouched');

        // The hash is real — one level down, on the `user` (TL:89), where the
        // wire puts it and where TEST 1 asserts it.
        $userBytes = TLEncoder::encodeObject('user', ['id' => self::USER_ID_4242, 'access_hash' => self::USER_HASH_4242]);
        $userOffset = 0;
        $userDecoded = TLDecoder::decodeObject($userBytes, $userOffset);
        self::assertSame(
            self::USER_HASH_4242,
            $userDecoded['access_hash'],
            'on `user` the same key IS declared (flags.0?long, TL:89) and survives — which is why it lands in tf_users_access_hash',
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // TEST 4 — A MEASURED GAP, pinned so it cannot rot into a rumour.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * `messages.Dialogs` carries a `messages:Vector<Message>` (TL:279-280), and
     * MEASURED: a `message` with a real body on this page reaches the walker
     * and places NOTHING — `tf_messages` carries no `sources` key at all in
     * `mirror-catalog.json`, so no parent constructor declares a field of type
     * `Message`.
     *
     * This is asserted rather than left implicit because the honest thing
     * about a known loss is to make it visible and measured, and because a
     * `tf_messages` count of 0 on a dialogs page would otherwise read as
     * "this page carried no messages".
     *
     * It is NOT counted as a regression here, and this test does not claim it
     * should land: `MirrorResultIngester` — the route that would carry an RPC
     * result at all — documents itself as proven-but-not-wired
     * (src/Teleframe/Ingest/MirrorResultIngester.php:48-55), and `tf_messages`
     * is reached in production by a different seam (`MirrorChunkDecomposer`,
     * on update payloads). When the result route is wired, THIS assertion is
     * the one that goes red, which is exactly what a gap marker should do.
     */
    public function test_the_page_messages_vector_places_nothing_and_that_is_measured(): void
    {
        $args = $this->pageArgs();
        $args['messages'] = [[
            '_' => 'message',
            'id' => 901,
            'peer_id' => ['_' => 'peerUser', 'user_id' => self::USER_ID_4242],
            'from_id' => ['_' => 'peerUser', 'user_id' => self::USER_ID_4242],
            'date' => 1700000000,
            'message' => 'a synthetic body, so the loss below is a loss of TEXT',
        ]];
        $args = $this->pageArgsFor('messages.dialogsSlice', $args);

        $offset = 0;
        $payload = TLDecoder::decodeObject(TLEncoder::encodeObject('messages.dialogsSlice', $args), $offset);

        // The wire really did carry it: one message, with its body intact.
        self::assertCount(1, $payload['messages'], 'the page must carry the message the fixture encoded');
        self::assertSame('a synthetic body, so the loss below is a loss of TEXT', $payload['messages'][0]['message'], 'the message body must survive the encode/decode round trip intact');

        $facts = iterator_to_array((new MirrorNestedSourceWalker($this->shippedCatalog()))->collect($payload));
        self::assertSame(
            [],
            array_values(array_filter(array_map(fn ($f): string => $f['table'], $facts), static fn (string $t): bool => $t === 'tf_messages')),
            'MEASURED GAP: tf_messages declares no nested source, so a message on a dialogs page places nothing',
        );

        // The dialogs on the SAME page still land, so the gap above is a gap
        // and not a dead write path.
        self::assertNotSame([], array_values($facts), 'the page\'s dialogs vector still places — the gap is the messages vector alone');
    }

    // ─────────────────────────────────────────────────────────────────────
    // HARNESS — the real collaborators, the executed DDL, the encode/decode.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * A `dialog` (TL:212) with every REQUIRED field present, so the encoder
     * cannot reject it.
     *
     * Array UNION (`$extra + [...]`), not merge: the left operand wins, so a
     * per-dialog flag (`pinned`, `unread_mark`, `unread_count`) overrides the
     * base value without the base having to know about it. `notify_settings`
     * is required and single-ctor (`peerNotifySettings`, TL:245).
     *
     * @param  array<string, mixed>  $peer
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function dialog(array $peer, int $topMessage, array $extra = []): array
    {
        return $extra + [
            '_' => 'dialog',
            'peer' => $peer,
            'top_message' => $topMessage,
            'read_inbox_max_id' => $topMessage - 1,
            'read_outbox_max_id' => 0,
            'unread_count' => 0,
            'unread_mentions_count' => 0,
            'unread_reactions_count' => 0,
            'unread_poll_votes_count' => 0,
            'notify_settings' => ['_' => 'peerNotifySettings'],
        ];
    }

    /**
     * The page, as ENCODE ARGS — never as the payload the mirror reads. Every
     * key here is checked against the shipped TL signature by the encoder, and
     * anything undeclared is dropped before the mirror is asked to store it.
     *
     * Note what is NOT here: an `access_hash` inside a `peerUser` /`peerChannel`.
     * Neither peer carries one at layer 227 (TEST 3), so a hash written there
     * would be dropped in silence and the fixture would be asserting on
     * nothing. The hashes go where TL puts them — `user.access_hash`
     * (flags.0?long, TL:89) and `channel.access_hash` (flags.13?long, TL:104).
     *
     * @return array<string, mixed>
     */
    private function pageArgs(): array
    {
        return [
            // `count` is declared by the slice (TL:280) and NOT by the full
            // page (TL:279), so it is added by the caller, not here.
            'dialogs' => [
                $this->dialog(['_' => 'peerUser', 'user_id' => self::USER_ID_4242], 7001, ['pinned' => true, 'unread_count' => 3]),
                $this->dialog(['_' => 'peerUser', 'user_id' => self::USER_ID_4243], 7002),
                $this->dialog(['_' => 'peerChannel', 'channel_id' => self::CHANNEL_ID], 7003, ['unread_mark' => true]),
            ],
            'messages' => [],
            'chats' => [
                // `chat` (TL:102): id/title/photo/participants_count/date/version
                // are all required, and it carries NO access_hash.
                ['_' => 'chat', 'id' => self::CHAT_ID, 'title' => 'synthetic chat', 'photo' => ['_' => 'chatPhotoEmpty'], 'participants_count' => 2, 'date' => 1600000000, 'version' => 1],
                // `channel` (TL:104): id/title/photo/date required, access_hash
                // behind flags.13 — a different bit from the user's flags.0.
                ['_' => 'channel', 'id' => self::CHANNEL_ID, 'access_hash' => self::CHANNEL_HASH, 'title' => 'synthetic channel', 'photo' => ['_' => 'chatPhotoEmpty'], 'date' => 1600000001],
            ],
            'users' => [
                ['_' => 'user', 'id' => self::USER_ID_4242, 'access_hash' => self::USER_HASH_4242, 'first_name' => 'Syn', 'username' => 'syn4242'],
                ['_' => 'user', 'id' => self::USER_ID_4243, 'access_hash' => self::USER_HASH_4243, 'first_name' => 'Other'],
                // `userEmpty` (TL:88) carries an id and nothing else — the
                // negative case: a user with no hash must get no hash row.
                ['_' => 'userEmpty', 'id' => self::USER_ID_NO_HASH],
            ],
        ];
    }

    /**
     * The page args for a SPECIFIC page constructor, adding `count` where the
     * constructor declares it. `messages.dialogs` (TL:279) has no `count`;
     * `messages.dialogsSlice` (TL:280) requires one. Asserted from the shipped
     * signature rather than assumed, so a schema change cannot make this
     * fixture wrong in silence.
     *
     * @param  array<string, mixed>|null  $args  the page args to complete, or null for the fresh ones
     * @return array<string, mixed>
     */
    private function pageArgsFor(string $pageCtor, ?array $args = null): array
    {
        $args ??= $this->pageArgs();

        // MEASURED from the shipped signature, not assumed: `count` is declared
        // by the SLICE (TL:280) and NOT by the full page (TL:279). An undeclared
        // key would be dropped by the encoder in silence, so the fixture asks
        // the registry instead of guessing — and the assertion is the evidence.
        $declaresCount = in_array('count', $this->declaredFieldNames($pageCtor), true);
        self::assertSame(
            str_ends_with($pageCtor, 'Slice'),
            $declaresCount,
            "measured: `count` is declared by messages.dialogsSlice (TL:280) and NOT by messages.dialogs (TL:279), and `{$pageCtor}` disagrees with that",
        );

        return $declaresCount ? ['count' => count($args['dialogs'])] + $args : $args;
    }

    /**
     * The field names a constructor declares, read from the SHIPPED registry at
     * runtime — never from a copy of the `.tl` line, because a copy is a second
     * source of truth and this file exists to distrust second sources of truth.
     *
     * @return list<string>
     */
    private function declaredFieldNames(string $ctor): array
    {
        return array_map(
            static fn (array $field): string => $field['name'],
            TLRegistry::signatureOf($ctor)->fields,
        );
    }

    /**
     * A fresh in-memory connection whose schema is the EXECUTED shipped DDL.
     *
     * @return ConnectionInterface
     */
    private function shippedConnection()
    {
        return ShippedStoreSchema::capsuleFor(...self::MIGRATIONS)->getConnection();
    }

    private function shippedCatalog(): MirrorCatalog
    {
        return MirrorCatalog::load(self::CATALOG, TlParser::parseFile(self::TL));
    }

    /**
     * Run the REAL write path over a decoded payload: walk → decompose → write,
     * the three collaborators `MirrorResultIngester` holds, in its order.
     *
     * @param  ConnectionInterface  $db
     * @param  array<string, mixed>  $payload
     * @return array{inserted: int, clues: list<string>, fkClues: list<string>, unmappedClues: list<string>, valueClues: list<string>}
     */
    private function ingest($db, array $payload): array
    {
        $scheme = TlParser::parseFile(self::TL);
        $catalog = $this->shippedCatalog();
        $decomposer = new MirrorFactDecomposer(new MirrorTableResolver($catalog, $scheme), $catalog);
        $writer = new MirrorFactWriter;

        $inserted = 0;
        $clues = [];
        $fkClues = [];
        $unmapped = [];
        $values = [];

        foreach ((new MirrorNestedSourceWalker($catalog))->collect($payload) as $fact) {
            $decomposed = $decomposer->decompose($fact['table'], self::ACCOUNT, $fact['payload']);
            $clues = [...$clues, ...$decomposed['clues']];

            if ($decomposed['rows'] === []) {
                continue;
            }

            $written = $writer->write($db, $decomposed['rows']);
            $inserted += $written['inserted'];
            $fkClues = [...$fkClues, ...$written['fkClues']];
            $unmapped = [...$unmapped, ...$written['unmappedClues']];
            $values = [...$values, ...$written['valueClues']];
        }

        return [
            'inserted' => $inserted,
            'clues' => $clues,
            'fkClues' => $fkClues,
            'unmappedClues' => $unmapped,
            'valueClues' => $values,
        ];
    }

    /**
     * A TL id as the hex that appears in a little-endian buffer.
     *
     * MEASURED: a `messages.dialogsSlice#71e094f3` page encodes to a buffer
     * whose first four bytes read `f394e071`, and `messages.dialogs#15ba6c40`
     * appears as `406cba15` — so the codec writes the id low-byte-first, i.e.
     * the 8 hex digits in 2-character CHUNKS, reversed (not the characters
     * reversed: `strrev('15ba6c40')` is `046cab15`, which is wrong).
     *
     * Asserting on this is how the test reads the BYTES rather than the decoded
     * view: the byte order is the one thing a decoded array can never disagree
     * with, and a fixture typed in the catalog's spelling could not produce it.
     */
    private function littleEndianHex(int $id): string
    {
        $chunks = str_split(str_pad(dechex($id), 8, '0', STR_PAD_LEFT), 2);

        return implode('', array_reverse($chunks));
    }
}
