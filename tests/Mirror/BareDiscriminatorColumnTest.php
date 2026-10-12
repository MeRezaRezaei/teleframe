<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTable;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * A row for a table whose shipped DDL declares a BARE, defaultless
 * `constructor` column must CARRY that column.
 *
 * ## The live failure this pins
 *
 * Discovery routed into the mirror, the bytes defect was fixed, and the next
 * page died — reported rather than silent this time:
 *
 *     mirror: messages.getDialogs page NOT stored for account=1724372757
 *       (SQLSTATE[HY000]: General error: 1364 Field 'constructor' doesn't have
 *        a default value … SQL: insert into `tf_chats_default_banned_rights`
 *        (`account_id`, `id`, `view_messages`, `send_message…
 *
 * `tf_chats_default_banned_rights.constructor` is declared
 * `$table->text('constructor')` — NOT NULL, no default — at
 * `src/Laravel/Migrations/2026_09_14_200002_create_tf_chats_tables.php:125`.
 * And because the mirror write is wrapped in `$db->transaction(...)`
 * (`MirrorIngesterFactory::buildResultRoute()`), the 1364 rolled the WHOLE page
 * back: `tf_users` (whose entry does carry a discriminator) landed, `tf_chats`
 * stayed at 0.
 *
 * ## Why the old gate was the wrong axis
 *
 * `MirrorTable::$constructor` is set by the resolver only when the ctor set can
 * VARY (`count($ctors) > 1`), because for a UNION table the discriminator is the
 * only thing that says which member is stored. `ChatBannedRights` is a SINGLE
 * ctor, so the resolver says "no discriminator column" — and the shipped DDL,
 * hand-authored per domain, disagrees: it declares one anyway. "The ctor cannot
 * vary" and "the column does not exist" are different questions, and the gate
 * answered the first one.
 *
 * ## Why the fix is a CURATED list and not a rule
 *
 * The obvious rule — "always write the parent's ctor" — was measured and
 * rejected, and the measurement is the reason this file can be trusted:
 *
 *  - The shipped mirror has 264 `tf_*` tables and only 84 declare a
 *    `constructor` column at all. Of those 84, twelve declare it WITH a
 *    `->default('')` (so they cannot raise 1364) and 72 declare it bare.
 *  - `childRow()` walks the DIRECT children of a resolved parent. Of those,
 *    exactly EIGHT tables ship a bare discriminator the resolver produces no
 *    value for — the set pinned below. Everything else either has a default, or
 *    has no column, or is a union the resolver already fills.
 *  - Every STRUCTURAL alternative was tried against the real resolver and none
 *    separates those eight from the 180 tables that must NOT gain a column:
 *    "is the child field an object?" admits 22 object-shaped children with no
 *    column at all (`tf_messages_replies`, `tf_users_restriction_reason`,
 *    `tf_todo_lists_title`, …); "does the child carry no column named after its
 *    own wire field?" admits the same 22; "does the payload item carry `_`?"
 *    admits the same 22 again.
 *
 * So the only exact gate is the shipped DDL, and the shipped DDL is hand-authored
 * — `test_the_curated_list_is_exactly_the_set_the_shipped_ddl_demands` recomputes
 * the set from the migration files and the real resolver on every run, so the
 * list cannot silently drift: a new bare column, or a table whose DDL gains a
 * default, is a RED test rather than a stale constant.
 *
 * ## Why the assertion is on what the row CARRIES
 *
 * MEASURED on sqlite 3.46.1, against the shipped
 * `tf_chats_default_banned_rights`, by inserting through the same
 * `ShippedStoreSchema::capsuleFor()` connection these tests use:
 *
 *  - the PRE-FIX row — the `constructor` key simply ABSENT — is **REJECTED**:
 *    `23000` / 19, "NOT NULL constraint failed: …constructor". sqlite DOES enforce
 *    `NOT NULL`, and reports the same state/code MySQL's 1364 is a different code
 *    for. So the pre-fix row is a red test on sqlite too; nothing is papered over.
 *  - the same row with `constructor => ''` is **ACCEPTED**, because `''` is a
 *    value, not a null.
 *
 * What sqlite is lax about is TYPE, not NOT NULL, and that is the fact these
 * assertions exist against: `''`, `0`, `'abc'` and `1.5` all landed in an
 * `INTEGER NOT NULL` column (`typeof()` reported text, integer, text, real), and
 * a 400-char value landed in a `varchar(8)`. A fill-the-column implementation that
 * wrote `''` would therefore sail through sqlite's NOT NULL check carrying a
 * fabricated discriminator, and no exception — raised or awaited — can see that.
 *
 * So every assertion here is `array_key_exists('constructor', $row)` plus the
 * VALUE: the shape that reaches MySQL, where the absent key is 1364. A test that
 * only AWAITED a `QueryException` would be the opposite hazard, not the one named
 * above: the post-fix row raises nothing, so such a test would wait on an
 * exception that never comes and fail forever — and it would still pass on a row
 * carrying `''`. The write-through below is a second opinion on top of both.
 *
 * ## What the stored value means
 *
 * A TL ctor name, and nothing else — the same shape `tf_chats` already stores
 * (`chat` / `channel`) and `tf_saved_dialogs` stores (`savedDialog`). For a
 * child the child's OWN ctor is preferred (`chatBannedRights`), because that is
 * the discriminator's job: it says which union member produced the row. The
 * parent's ctor is the fallback, and it is never empty — `decompose()` has
 * already checked the payload's ctor against the catalog's before it gets
 * here. An empty string is never written: if neither ctor is nameable the column
 * is left off, which is the same "never fabricate" choice `placeRow()` makes
 * for a key it cannot cover.
 */
final class BareDiscriminatorColumnTest extends TestCase
{
    private const CHATS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200002_create_tf_chats_tables.php';

    private const DIALOGS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php';

    private const USERS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200001_create_tf_users_tables.php';

    private const MESSAGES_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php';

    private const STICKER_SETS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200023_create_tf_sticker_sets_tables.php';

    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    private const ACCOUNT = 7;

    /**
     * A `chat` carrying both single-ctor `Chat*Rights` children and one union
     * child (`ChatPhoto`), so the same page covers the bare columns and a
     * discriminator the resolver already fills.
     *
     * @return array<string, mixed>
     */
    private function chatPayload(): array
    {
        return [
            '_' => 'chat',
            'id' => -1001234,
            'title' => 'A basic group',
            'participants_count' => 5,
            'date' => 1_500_000_000,
            'photo' => ['_' => 'chatPhoto', 'photo_id' => 555, 'dc_id' => 2],
            'admin_rights' => ['_' => 'chatAdminRights', 'flags' => 0],
            'default_banned_rights' => [
                '_' => 'chatBannedRights',
                'flags' => 1,
                'view_messages' => true,
                'until_date' => 1_700_000_000,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function userPayload(): array
    {
        return [
            '_' => 'user',
            'id' => 8329480050,
            'access_hash' => '5512378901644',
            // A SCALAR child whose shipped table has NO discriminator column.
            'first_name' => 'Someone',
            // An OBJECT child whose shipped table has NO discriminator
            // column. This is the case an "is the child field an object?" rule
            // gets wrong: the payload element carries its own `_`, and the row
            // must still stay clean. Carried as a vector, because that is how
            // the wire carries `Vector<RestrictionReason>` on a `user`.
            'restriction_reason' => [
                ['_' => 'restrictionReason', 'platform' => 'ios', 'reason' => 'banned', 'text' => ''],
            ],
            // Two OBJECT children whose shipped tables DO declare one, bare.
            'stories_max_id' => ['_' => 'recentStory', 'flags' => 0, 'max_id' => 91],
            'usernames' => [
                ['_' => 'username', 'editable' => true, 'active' => true, 'username' => 'someone'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function dialogPayload(): array
    {
        return [
            '_' => 'dialog',
            'peer' => ['_' => 'peerChat', 'chat_id' => 444_000_555],
            'top_message' => 7,
            'date' => 1_712_345_400,
            'notify_settings' => [
                '_' => 'notifySettings',
                'flags' => 0,
                'show_previews' => true,
                'mute_until' => 0,
            ],
        ];
    }

    /**
     * THE DEFECT, pinned on the exact table the live run died on.
     *
     * RED before the fix: `tf_chats_default_banned_rights` ships
     * `$table->text('constructor')` (bare, no default) and the row carries no
     * `constructor` key at all — which MySQL strict answers with 1364, taking
     * the whole page's transaction with it.
     *
     * Both halves are asserted. `array_key_exists` is the half that reproduces
     * the failure (an absent key is what MySQL rejects with 1364, and what
     * sqlite rejects too — measured, `23000`/19 "NOT NULL constraint failed" —
     * while an empty string is accepted by BOTH, so the key's absence is the
     * defect and its VALUE is what no database error can report), and the VALUE
     * is the half that makes the fix honest rather than merely insert-shaped.
     */
    public function test_a_child_row_for_a_table_that_ships_a_bare_discriminator_carries_it(): void
    {
        $previous = Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(self::CHATS_MIGRATION)->getConnection();

            // Precondition, asserted rather than assumed: the shipped DDL really
            // does declare the column, and really does declare it bare. If a
            // future migration gives it a default, this file is vacuous and must
            // be re-derived.
            $listing = $db->getSchemaBuilder()->getColumnListing('tf_chats_default_banned_rights');
            self::assertContains('constructor', $listing, 'the shipped table must still declare the discriminator');
            self::assertSame(
                'TEXT',
                (string) ($db->selectOne("select type from pragma_table_info('tf_chats_default_banned_rights') where name = 'constructor'")->type ?? ''),
                'the shipped column is TEXT — a value-length change would be a different migration story',
            );

            $fact = $this->decompose('tf_chats', $this->chatPayload());

            $row = $this->rowFor($fact, 'tf_chats_default_banned_rights');
            self::assertIsArray($row, 'the decomposer must emit the banned-rights row');
            self::assertArrayHasKey(
                'constructor',
                $row,
                'a row for a table with a BARE discriminator column must carry it — MySQL strict rejects the insert with 1364 otherwise, and the whole page transaction rolls back',
            );
            self::assertSame(
                'chatBannedRights',
                (string) $row['constructor'],
                "the child's OWN ctor is the honest value: it names the union member that produced the row",
            );

            $admin = $this->rowFor($fact, 'tf_chats_admin_rights');
            self::assertIsArray($admin, 'the admin-rights sibling must be emitted too');
            self::assertSame(
                'chatAdminRights',
                (string) ($admin['constructor'] ?? ''),
                'the sibling bare-discriminator child names its own ctor the same way',
            );

            // The write-through is the second opinion: it proves the row is
            // now complete enough to land, and nothing more — measured, sqlite
            // would also have rejected the pre-fix row (23000/19 NOT NULL), so
            // it never was a witness for the fix.
            $write = (new MirrorFactWriter)->write($db, $fact['rows']);
            self::assertSame([], $write['fkClues'], 'a complete row must not produce a write clue');
            self::assertSame(
                'chatBannedRights',
                (string) $db->table('tf_chats_default_banned_rights')->value('constructor'),
                'the stored discriminator must be the ctor name, not an empty string',
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * The same property on a POSITIONED (1:N) bare-discriminator child and on a
     * parent-sourced one, so the fix is not accidentally special-cased to the
     * single 1:1 child the live run happened to die on.
     */
    public function test_positioned_and_parent_sourced_children_also_carry_the_discriminator(): void
    {
        $previous = Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(self::USERS_MIGRATION, self::DIALOGS_MIGRATION)->getConnection();

            $users = $this->decompose('tf_users', $this->userPayload());

            $usernames = $this->rowFor($users, 'tf_users_usernames');
            self::assertIsArray($usernames, 'the positioned usernames row must be emitted');
            self::assertSame(
                'username',
                (string) ($usernames['constructor'] ?? ''),
                'a positioned vector child names the union member of its ELEMENT',
            );

            $stories = $this->rowFor($users, 'tf_users_stories_max_id');
            self::assertIsArray($stories, 'the stories_max_id row must be emitted');
            self::assertSame(
                'recentStory',
                (string) ($stories['constructor'] ?? ''),
                'a single-ctor object child names its own ctor, not the parent user ctor',
            );

            $dialogs = $this->decompose('tf_dialogs', $this->dialogPayload());
            $notify = $this->rowFor($dialogs, 'tf_dialogs_notify_settings');
            self::assertIsArray($notify, 'the notify_settings row must be emitted');
            self::assertSame(
                'notifySettings',
                (string) ($notify['constructor'] ?? ''),
                'the notify_settings child names its own ctor',
            );

            $write = (new MirrorFactWriter)->write($db, [...$users['rows'], ...$dialogs['rows']]);
            self::assertSame([], $write['fkClues'], 'every bare-discriminator row must place without a clue');
            self::assertSame(
                'username',
                (string) $db->table('tf_users_usernames')->value('constructor'),
                'the positioned row lands its discriminator',
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * NO COLLATERAL: a table whose shipped DDL has no `constructor` column must
     * not gain one in the row.
     *
     * This is the arm that makes the curated list a list rather than a blanket.
     * The writer DOES drop a column the DDL lacks, so the insert would still
     * succeed — but every such row would emit an "is not in the shipped DDL"
     * unmapped clue, and that ledger is what the operator reads to find what the
     * mirror is not modelling. Forty measured candidates (`tf_users_first_name`,
     * `tf_messages_replies`, `tf_todo_lists_title`, …) would have flooded it.
     *
     * The three below are deliberately different shapes: a scalar store, an
     * object child with no column, and a union parent that already has one.
     */
    public function test_a_table_without_the_discriminator_column_does_not_gain_one(): void
    {
        $fact = $this->decompose('tf_users', $this->userPayload());

        // A scalar child: the row's payload column is the wire field itself, and
        // the shipped tf_users_first_name declares no discriminator.
        $firstName = $this->rowFor($fact, 'tf_users_first_name');
        self::assertIsArray($firstName, 'the scalar child row must still be emitted');
        self::assertSame('Someone', (string) ($firstName['first_name'] ?? ''), 'the scalar value must land untouched');
        self::assertArrayNotHasKey(
            'constructor',
            $firstName,
            'a table with no shipped discriminator column must not gain one — the writer would drop it and report it as an unmodelled field',
        );

        // An OBJECT child whose table has no discriminator column. This is the
        // case a "is the child field an object?" rule gets wrong: the payload
        // item carries its own `_`, and the row must still stay clean.
        $restriction = $this->rowFor($fact, 'tf_users_restriction_reason');
        self::assertIsArray($restriction, 'the object child row must still be emitted');
        self::assertSame('ios', (string) ($restriction['platform'] ?? ''), 'the object child still places its own facts');
        self::assertArrayNotHasKey(
            'constructor',
            $restriction,
            'an object-shaped child with no shipped discriminator column must not gain one',
        );
    }

    /**
     * The union discriminators that already work are UNCHANGED.
     *
     * `tf_chats` records `chat` / `channel` and `tf_saved_dialogs` records
     * `savedDialog` — the same string shape this fix stores, produced by the
     * other branch of the same gate. If the new branch could reach them it
     * would overwrite a correct discriminator with a parent's, which is the
     * classic way a "harmless" fix silently changes existing rows. Asserted
     * here so that cannot happen quietly; `BackfillCollectionArgsTest` and
     * `DiscoveryScope` pin the same values through the real write path.
     */
    public function test_an_existing_union_discriminator_is_unchanged(): void
    {
        $chat = $this->decompose('tf_chats', $this->chatPayload());
        $chatRow = $this->rowFor($chat, 'tf_chats');
        self::assertIsArray($chatRow);
        self::assertSame('chat', (string) ($chatRow['constructor'] ?? ''), 'tf_chats keeps the ctor the resolver produced');

        // And the union CHILD of the same page: already carrying `constructor`
        // before this fix, and it must still carry its own ctor.
        $photo = $this->rowFor($chat, 'tf_chats_photo');
        self::assertIsArray($photo);
        self::assertSame(
            'chatPhoto',
            (string) ($photo['constructor'] ?? ''),
            'a union child the resolver already discriminated keeps its own ctor',
        );

        $saved = $this->decompose('tf_saved_dialogs', [
            '_' => 'savedDialog',
            'peer' => ['_' => 'peerChat', 'chat_id' => 444_000_555],
            'top_message' => 9002,
        ]);
        $savedRow = $this->rowFor($saved, 'tf_saved_dialogs');
        self::assertIsArray($savedRow);
        self::assertSame(
            'savedDialog',
            (string) ($savedRow['constructor'] ?? ''),
            'tf_saved_dialogs keeps savedDialog — the value BackfillCollectionArgsTest asserts',
        );
    }

    /**
     * THE GATE IS EXACT: the curated list is precisely the set of tables whose
     * shipped DDL declares a BARE discriminator the resolver cannot fill.
     *
     * Computed from two independent authorities on every run — the migration
     * files themselves (scanned with plain string functions, no regex) and the
     * REAL resolver over the REAL catalog — so this test fails in both
     * directions:
     *
     *  - a table the list names but the DDL no longer demands (a default was
     *    added, or the column was dropped) — the list would then write a column
     *    nothing needs;
     *  - a table the DDL demands but the list omits — the 1364 this file exists
     *    to prevent, one table further out.
     */
    public function test_the_curated_list_is_exactly_the_set_the_shipped_ddl_demands(): void
    {
        $scheme = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        $demanded = [];
        $seen = [];
        $walk = static function (MirrorTable $table) use (&$walk, &$demanded, &$seen): void {
            // The graph is a DAG with shared nodes (`tf_chats` is reachable from
            // more than one parent, and a cycle guard drops a branch on the way
            // back), so a name can be VISITED more than once. Keyed, not
            // appended: the set is what the curated list must equal, and a second
            // visit is not a second table.
            $seen[$table->tfName] = true;
            if ($table->constructor === '' && self::shipsBareDiscriminator($table->tfName)) {
                $demanded[$table->tfName] = true;
            }
            foreach ($table->children as $child) {
                $walk($child);
            }
        };
        foreach ($resolver->resolveAll($catalog->tableNames()) as $parent) {
            $walk($parent);
        }
        $demanded = array_keys($demanded);
        sort($demanded);

        self::assertNotEmpty($demanded, 'the scan found no bare-discriminator table — the migration scanner moved');

        $curated = $this->curatedList();
        sort($curated);

        self::assertSame(
            $demanded,
            $curated,
            "the decomposer's curated bare-discriminator list drifted from the shipped DDL.\n"
            .'  the shipped DDL demands: '.implode(', ', $demanded)."\n"
            .'  the decomposer knows:    '.implode(', ', $curated)."\n"
            .'A table demanded and not listed is a 1364 on the first page that carries it; '
            .'a table listed and not demanded is a column written for nothing.',
        );

        // The count is pinned too, so the set cannot quietly grow table by table
        // across runs — a growth here is a new bare column in a hand-authored
        // migration and wants a human, not a constant edit.
        self::assertCount(13, $curated, 'the bare-discriminator gap changed size — re-derive it from the shipped migrations');
    }

    /**
     * The decomposer's curated list, read without widening its public surface.
     *
     * A private constant stays private in production; the drift guard above is
     * a TEST, and a test is allowed to look. If this ever stops resolving, the
     * assertion fails loudly rather than comparing an empty list to an empty
     * set and passing.
     *
     * @return list<string>
     */
    private function curatedList(): array
    {
        $constant = (new \ReflectionClass(MirrorFactDecomposer::class))->getReflectionConstant('BARE_DISCRIMINATOR_TABLES');
        self::assertNotFalse($constant, 'MirrorFactDecomposer::BARE_DISCRIMINATOR_TABLES no longer exists — this file is measuring nothing');

        return array_values($constant->getValue());
    }

    /**
     * Does the shipped migration set declare `constructor` on this table with
     * NO default?
     *
     * Plain string functions throughout, matching the zero-regex discipline the
     * mirror path itself holds. Zero-regex is allow-listed in `src/Schema/`
     * and this is a test, but a scanner that reaches for `preg_*` to read a
     * Blueprint is a scanner nobody can re-derive by eye.
     */
    private static function shipsBareDiscriminator(string $tfName): bool
    {
        foreach (self::migrationFiles() as $file) {
            $source = (string) file_get_contents($file);
            $offset = 0;
            while (($start = strpos($source, "Schema::create('", $offset)) !== false) {
                $nameStart = $start + strlen("Schema::create('");
                $nameEnd = strpos($source, "'", $nameStart);
                if ($nameEnd === false) {
                    break;
                }
                if (substr($source, $nameStart, $nameEnd - $nameStart) === $tfName) {
                    return self::declaresDiscriminatorWithoutDefault(self::blockStartingAt($source, $nameEnd));
                }
                $offset = $nameEnd;
            }
        }

        return false;
    }

    /** @return list<string> */
    private static function migrationFiles(): array
    {
        $files = glob(__DIR__.'/../../src/Laravel/Migrations/*.php') ?: [];
        sort($files);

        return $files;
    }

    /** The `{ … }` block that opens after the given offset, brace-matched. */
    private static function blockStartingAt(string $source, int $offset): string
    {
        $open = strpos($source, '{', $offset);
        if ($open === false) {
            return '';
        }

        $depth = 0;
        for ($i = $open, $length = strlen($source); $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $open, $i - $open + 1);
                }
            }
        }

        return substr($source, $open);
    }

    /**
     * A `constructor` column declaration that carries no `->default(…)`.
     *
     * Scanned per STATEMENT rather than per block: the check is "does this very
     * declaration have a default", and a neighbouring statement's `->default`
     * must not be able to answer for it.
     */
    private static function declaresDiscriminatorWithoutDefault(string $block): bool
    {
        $needle = "'constructor'";
        $offset = 0;
        while (($position = strpos($block, $needle, $offset)) !== false) {
            $end = strpos($block, ';', $position);
            $statement = $end === false
                ? substr($block, $position)
                : substr($block, $position, $end - $position);

            if (! str_contains($statement, '->default(')) {
                return true;
            }

            $offset = $position + strlen($needle);
        }

        return false;
    }

    /**
     * The SAME defect one level up, on the two CATALOG PARENTS whose shipped DDL
     * declares a bare discriminator the catalog's ctor count does not explain.
     *
     * `MessageService` and `StickerSet` are each a single-ctor TL type, so
     * `count($entry->ctors) > 1` is false and the parent gate said "no
     * discriminator" — while both tables ship `$table->text('constructor')` /
     * `$table->string('constructor', 64)` with no default. A `messageService`
     * update or a `set` vector would have died on 1364 exactly as the child row
     * did, and the transaction would have taken the page with it.
     *
     * The value is the payload's OWN ctor, which `decompose()` has already
     * checked against the catalog's ctor list by the time the row is built, so
     * it is `messageService` / `stickerSet` — the same string shape `tf_chats`
     * and `tf_saved_dialogs` already store.
     */
    public function test_a_single_ctor_parent_whose_ddl_declares_a_bare_discriminator_carries_it(): void
    {
        $previous = Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(self::MESSAGES_MIGRATION, self::STICKER_SETS_MIGRATION)->getConnection();

            $service = $this->decompose('tf_messages_service', [
                '_' => 'messageService',
                'id' => 4242,
                'peer' => ['_' => 'peerChat', 'chat_id' => 444_000_555],
                'date' => 1_712_345_400,
                'flags' => 0,
            ]);
            $serviceRow = $this->rowFor($service, 'tf_messages_service');
            self::assertIsArray($serviceRow, 'the service row must be emitted');
            self::assertSame(
                'messageService',
                (string) ($serviceRow['constructor'] ?? ''),
                'a single-ctor parent with a bare shipped discriminator records the ctor that produced it',
            );

            $stickerSet = $this->decompose('tf_sticker_sets', [
                '_' => 'stickerSet',
                'id' => 1815679850,
                'access_hash' => '9172635481029',
                'title' => 'A sticker set',
                'short_name' => 'set_by_bot',
                'count' => 12,
                'hash' => 0,
            ]);
            $setRow = $this->rowFor($stickerSet, 'tf_sticker_sets');
            self::assertIsArray($setRow, 'the sticker-set row must be emitted');
            self::assertSame(
                'stickerSet',
                (string) ($setRow['constructor'] ?? ''),
                'and so does tf_sticker_sets',
            );

            // The sibling scalar stores declare the SAME column but WITH a
            // default, which is why they are deliberately absent from the curated
            // list: they cannot raise 1364, and filling them would be writing a
            // discriminator the resolver never modelled.
            $write = (new MirrorFactWriter)->write($db, [...$service['rows'], ...$stickerSet['rows']]);
            self::assertSame([], $write['fkClues'], 'both parent rows must place without a clue');
            self::assertSame('messageService', (string) $db->table('tf_messages_service')->value('constructor'));
            self::assertSame('stickerSet', (string) $db->table('tf_sticker_sets')->value('constructor'));
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * @param  array{rows: list<array{table: string, row: array<string, int|string|null>}>, clues: list<string>}  $fact
     * @return array<string, int|string|null>|null
     */
    private function rowFor(array $fact, string $tfName): ?array
    {
        foreach ($fact['rows'] as $candidate) {
            if ($candidate['table'] === $tfName) {
                return $candidate['row'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{rows: list<array{table: string, row: array<string, int|string|null>}>, clues: list<string>}
     */
    private function decompose(string $tfName, array $payload): array
    {
        $scheme = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);

        return (new MirrorFactDecomposer(new MirrorTableResolver($catalog, $scheme), $catalog))
            ->decompose($tfName, self::ACCOUNT, $payload);
    }
}
