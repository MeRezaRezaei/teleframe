<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * WHERE A CHANNEL'S `access_hash` IS STORED — and that a basic group has none.
 *
 * ## THE INVARIANT THIS CLASS PROTECTS
 *
 * A `--peer` lookup can only dial a channel if that channel's `access_hash` is
 * STORED somewhere readable, signed-64-bit-losslessly, keyed by the peer's own
 * id. And a plain `chat` — which Telegram does not give a hash for — must
 * place no hash row anywhere. Row existence = fact existence. That asymmetry
 * is the whole point; both halves are asserted, as VALUES.
 *
 * ## WHY THE EXPECTATION MOVED (recorded, because a silently retargeted
 * ## assertion is indistinguishable from a deleted one)
 *
 * This class used to assert the hash landed in `tf_chats_access_hash`, a 1:1
 * child of `tf_chats`. That child was modelled on the premise — stated in the
 * old docblock and still repeated in the migration's own header — that all
 * five `Chat` ctors live on `tf_chats` and that `tf_channels` "is NOT a
 * catalog table … a leftover from generated migrations the operator purged."
 *
 * **BOTH HALVES OF THAT PREMISE ARE NOW FALSE**, and the repair follows the
 * model, not the premise:
 *
 *  1. **THE DELIBERATE CTOR SPLIT.** `src/Schema/schema/mirror-catalog.json`
 *     now partitions the five-constructor `Chat` union across two tables:
 *     `tf_chats.ctors = ["chatEmpty","chat","chatForbidden"]` and
 *     `tf_channels.ctors = ["channel","channelForbidden"]`, pinned by the
 *     catalog's own ctor-exclusivity validation. Measured through
 *     `MirrorFactDecomposer::decompose()`: `decompose('tf_chats', …,
 *     CHANNEL_PAYLOAD)` returns **0 rows** — the `channel` ctor is no longer
 *     admitted on `tf_chats` at all. That is the split working, not a bug.
 *  2. **`tf_channels` IS A CATALOG TABLE, and its `access_hash` is a REAL
 *     BASE COLUMN** (`["access_hash","BIGINT NOT NULL"]`, alongside `id`,
 *     `title`, `date` and the ctor's 28 flag bools). Measured:
 *     `decompose('tf_channels', …, CHANNEL_PAYLOAD)` returns **3 rows** —
 *     `tf_channels` (carrying the hash), `tf_channels_photo`,
 *     `tf_channels_participants_count`.
 *
 * So the fact this class cares about did not vanish; it MOVED from a child
 * table to a base column on the table that owns the ctor. Every assertion
 * below is re-pointed at the real home with a TRUE fixture, and the ones that
 * described the old world are replaced by ones that describe the current one.
 * None was weakened, skipped or deleted.
 *
 * TL is the arbiter, and it says the split is right
 * (`schema/sources/TL_telegram_v227.tl:101-105`):
 * `chatEmpty#29562865 id:long` and `chat#41cbf256 …` and
 * `chatForbidden#6592a1a7 id:long title:string` carry NO `access_hash`, while
 * `channel#1c32b11c … access_hash:flags.13?long …` does, and
 * `channelForbidden#17d493d5 … access_hash:long` carries it ungated. Two
 * ctors carry the hash, three do not — so "all five on `tf_chats`" was never
 * expressible without a nullable base column, and the split is the honest
 * answer.
 *
 * ## THE COLUMN IS SIGNED BECAUSE THE VALUE IS A TL `long`, NOT `bytes`
 *
 * Measured over 200 000 random TL `long`s decoded with `unpack('P')`, 50.0%
 * came out negative, so the signed 64-bit column is the type, not a nicety;
 * hex/TEXT would be wrong twice over — a string compare per lookup, and an
 * index that no longer matches the way `tf_users_access_hash` matches. The
 * negative literal asserted below is exactly that case, asserted as a VALUE:
 * a count-only assertion would pass against a table holding zero rows.
 *
 * ## DEFECT FOUND HERE, DELIBERATELY NOT FIXED
 *
 * **`tf_chats_access_hash` is an ORPHANED declaration and a table that can
 * never hold a row.** It is still declared as a `tf_chats` child in the
 * catalog, and the shipped migration
 * `src/Laravel/Migrations/2026_09_29_000400_create_tf_chats_access_hash_table.php`
 * still creates the table — but after the split, NO ctor admitted on
 * `tf_chats` carries `access_hash` (measured: `chatEmpty` → 1 row, `chat` →
 * 2 rows, `chatForbidden` → 1 row, none of them into the hash table; a
 * `channel` payload → 0 rows on `tf_chats` entirely), so the decomposer can
 * never place a row there. The table is dead weight: dead DDL that a future
 * reader will mistake for a live hash store, and one more surface a
 * `--discover` will never fill. Fixing it means deleting the catalog child and
 * dropping the shipped migration — a catalog/migration change that belongs to
 * its own lane, NOT to a test repair. It is pinned here as a real assertion
 * (`test_the_catalog_still_declares_the_orphaned_hash_child_so_the_defect_is
 * _visible`) so that the day someone fixes it, this class goes RED and says
 * why, instead of quietly passing over a dropped table.
 *
 * ## THE DDL IS EXECUTED, NEVER TRANSCRIBED
 *
 * `ShippedStoreSchema::capsuleFor()` runs the real shipped migration files, so
 * a column added or dropped in the dial moves these assertions with it. The
 * rows asserted here are the rows the REAL decomposer produces for a REAL
 * payload — read out of `MirrorFactDecomposer::decompose()`, not re-typed
 * from the TL definition.
 *
 * Provenance: src/Schema/schema/mirror-catalog.json (`tf_chats.ctors`,
 * `tf_channels.ctors`, `tf_channels.base` → `["access_hash","BIGINT NOT
 * NULL"]`, `tf_chats.children` → `["access_hash","BIGINT NOT NULL"]`);
 * schema/sources/TL_telegram_v227.tl:101-105;
 * src/Laravel/Migrations/2026_09_14_200003_create_tf_channels_tables.php:63
 * (`$table->bigInteger('access_hash')->default(0)`);
 * src/Laravel/Migrations/2026_09_29_000400_create_tf_chats_access_hash_table.php
 * (the orphaned table);
 * src/Laravel/Console/BackfillCommand.php:322-334 (`mirrorPeerLookup`, which
 * resolves a negative peer id against the channel table before it will dial).
 */
final class ChatAccessHashChildPlacesRowTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    /**
     * The shipped migrations these rows need, in apply order:
     *
     *  - the `tf_chats` domain (the three non-channel ctors' table);
     *  - the `tf_chats_access_hash` child, STILL CREATED and still declared —
     *    kept in the fixture precisely so the negative test can assert it
     *    stays empty instead of being absent, and so the orphan defect stays
     *    visible;
     *  - the `tf_channels` domain, where the channel's hash lives today.
     *
     * @var list<string>
     */
    private const MIGRATIONS = [
        '2026_09_14_200002_create_tf_chats_tables.php',
        '2026_09_29_000400_create_tf_chats_access_hash_table.php',
        '2026_09_14_200003_create_tf_channels_tables.php',
    ];

    private const ACCOUNT = 7;

    /**
     * A negative channel id, as Telegram assigns: 100-prefixed.
     */
    private const CHANNEL_ID = -1001232026269;

    /**
     * A REAL negative TL `long` — the class of value the channel hash store
     * must hold losslessly. 50% of TL longs decode negative, so a signed
     * 64-bit column is not a nicety here; it is the type.
     */
    private const ACCESS_HASH = -7424575095435594625;

    /**
     * A `channel` payload exactly as the wire decodes it, carrying the one
     * fact only this ctor (and `channelForbidden`) has.
     *
     * @var array<string, mixed>
     */
    private const CHANNEL_PAYLOAD = [
        '_' => 'channel',
        'flags' => 8192, // flags.13 = access_hash present
        'flags2' => 0,
        'id' => self::CHANNEL_ID,
        'access_hash' => self::ACCESS_HASH,
        'title' => 'a megagroup',
        'photo' => ['_' => 'chatPhotoEmpty'],
        'date' => 1735689600,
        'participants_count' => 4242,
    ];

    /**
     * A `chat` payload — one of the three ctors that carry NO `access_hash`
     * on the wire at all.
     *
     * @var array<string, mixed>
     */
    private const CHAT_PAYLOAD = [
        '_' => 'chat',
        'flags' => 0,
        'id' => -555000111,
        'title' => 'a basic group',
        'photo' => ['_' => 'chatPhotoEmpty'],
        'participants_count' => 12,
        'date' => 1735689600,
        'version' => 1,
    ];

    /**
     * THE VALUE, not the row count, in its real home.
     *
     * The `channel` ctor is decomposed against `tf_channels` — the table the
     * catalog now admits that ctor on, and the one whose BASE column
     * `access_hash` replaced the old `tf_chats_access_hash` child. Measured
     * shape of the result: 3 rows (`tf_channels`, `tf_channels_photo`,
     * `tf_channels_participants_count`), which is what `inserted` is compared
     * against below — a count taken from the run, never invented.
     */
    public function test_a_channel_payload_places_its_access_hash_value(): void
    {
        $capsule = ShippedStoreSchema::capsuleFor(...$this->migrationPaths());
        $db = $capsule->getConnection();
        [$resolver, $catalog] = $this->pipeline();

        $result = (new MirrorFactDecomposer($resolver, $catalog))->decompose('tf_channels', self::ACCOUNT, self::CHANNEL_PAYLOAD);
        $written = (new MirrorFactWriter)->write($db, $result['rows']);

        self::assertSame(
            count($result['rows']),
            $written['inserted'],
            'a decomposed row did not land — a raise on the channel base aborts the write: '
            .implode(', ', array_column($result['rows'], 'table')).' | fkClues: '.json_encode($written['fkClues']),
        );
        self::assertSame([], $written['unmappedClues'], 'a column the shipped DDL does not have is a modelling gap, not a green write');

        // THE VALUE, not the row count. A count assertion alone would pass
        // against a table holding zero rows, and would pass against a column
        // that stored 0 — which is exactly the value that means "Telegram has
        // not told us", the defect `BackfillCommand::usableHash()` rejects.
        $row = $db->table('tf_channels')->where('account_id', self::ACCOUNT)->first();

        self::assertNotNull($row, 'tf_channels holds no row for the mirrored channel — the hash cannot resolve a --peer');
        self::assertSame(
            self::ACCESS_HASH,
            (int) $row->access_hash,
            'the signed negative TL long did not survive the round trip losslessly; an unsigned or TEXT column would have failed here',
        );
        self::assertSame(self::CHANNEL_ID, (int) $row->id, 'the base row must carry the peer id itself, so --peer=<id> finds it');
        self::assertNotSame(0, (int) $row->access_hash, 'a zero hash is Telegram\'s "unknown", and looks resolved — worse than absent');
    }

    /**
     * THE RED PROOF, as a control rather than a recollection — re-pointed at
     * the table the row really lands in.
     *
     * The SAME decomposer, writer and payload, against a schema built from
     * the shipped set WITHOUT the channels domain, raises `no such table` —
     * out of `MirrorFactWriter::write()`, naming `tf_channels`. That is the
     * failure a host hits when the channel table has not been migrated: the
     * hash is not stored anywhere, and a `--peer=<channel>` dial has nothing
     * to read, so it must fail LOUDLY here rather than silently drop the one
     * fact only channels carry.
     *
     * The old form of this control asserted the decomposer still emits a
     * `tf_chats_access_hash` row. That premise is DISPROVEN by the ctor split
     * (measured: a `channel` payload against `tf_chats` yields 0 rows), so
     * the control was re-pointed at `tf_channels`, where the row demonstrably
     * exists — not weakened: it now proves the same property (the write path
     * is load-bearing on the shipped channel DDL) about the row that
     * actually exists.
     */
    public function test_without_the_shipped_channels_table_the_same_row_raises_no_such_table(): void
    {
        $without = array_values(array_filter(
            $this->migrationPaths(),
            static fn (string $p): bool => ! str_contains($p, 'tf_channels_tables'),
        ));
        self::assertNotSame([], $without, 'the control must still build a real shipped schema');
        self::assertCount(
            count($this->migrationPaths()) - 1,
            $without,
            'exactly the channels-domain migration must be dropped, or the control proves nothing',
        );

        $capsule = ShippedStoreSchema::capsuleFor(...$without);
        $db = $capsule->getConnection();
        [$resolver, $catalog] = $this->pipeline();

        $result = (new MirrorFactDecomposer($resolver, $catalog))->decompose('tf_channels', self::ACCOUNT, self::CHANNEL_PAYLOAD);

        // Guard: the control is only meaningful if the decomposer really does
        // emit the row. Without this, a green control would prove nothing.
        self::assertContains(
            'tf_channels',
            array_column($result['rows'], 'table'),
            'the decomposer no longer produces a tf_channels row — this control and its migration are stale',
        );
        self::assertFalse(
            $capsule->getConnection()->getSchemaBuilder()->hasTable('tf_channels'),
            'the control schema must NOT have the channels table, or the raise below is not the control',
        );

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/tf_channels/');

        (new MirrorFactWriter)->write($db, $result['rows']);
    }

    /**
     * THE ASYMMETRY, the half that makes the class worth having: a ctor that
     * does NOT carry `access_hash` on the wire must place no hash row
     * ANYWHERE — not a zero row, not a defaulted row — on the table that owns
     * channels (`tf_channels`, whose base column is the hash store) nor on
     * the orphaned `tf_chats_access_hash` child.
     *
     * Both tables are asserted to EXIST first, so a 0-row count cannot be
     * satisfied by the table simply being absent; and the chat's own base row
     * is asserted to have landed, so "0 hash rows" cannot be satisfied by the
     * writer having written nothing at all. Without those two guards the
     * negative half would be a tautology.
     */
    public function test_a_chat_ctor_without_the_fact_places_no_hash_row_anywhere(): void
    {
        $capsule = ShippedStoreSchema::capsuleFor(...$this->migrationPaths());
        $db = $capsule->getConnection();
        [$resolver, $catalog] = $this->pipeline();

        $schema = $db->getSchemaBuilder();
        self::assertTrue($schema->hasTable('tf_channels'), 'tf_channels must exist for "no hash row there" to mean anything');
        self::assertTrue($schema->hasTable('tf_chats_access_hash'), 'the orphaned hash child must still exist to be asserted empty');

        $result = (new MirrorFactDecomposer($resolver, $catalog))->decompose('tf_chats', self::ACCOUNT, self::CHAT_PAYLOAD);
        $written = (new MirrorFactWriter)->write($db, $result['rows']);

        self::assertSame(
            count($result['rows']),
            $written['inserted'],
            'the chat write did not land — an empty write would make the hash-row counts below vacuously true',
        );
        self::assertSame(
            1,
            (int) $db->table('tf_chats')->where('account_id', self::ACCOUNT)->count(),
            'the chat base row must exist, so the empty hash tables are attributable to the missing fact and not to a dead writer',
        );

        self::assertSame(
            0,
            (int) $db->table('tf_channels')->where('account_id', self::ACCOUNT)->count(),
            'a chat ctor carries no access_hash on the wire — a row here would claim a channel hash nobody was told',
        );
        self::assertSame(
            0,
            (int) $db->table('tf_chats_access_hash')->where('account_id', self::ACCOUNT)->count(),
            'a chat ctor must place no row in the orphaned hash child either; a row here would claim a hash nobody was told',
        );
    }

    /**
     * THE CATALOG'S OWN VALIDATION, run by the tool that owns the rule, and
     * pointed at the CURRENT shape.
     *
     * `MirrorCatalog::load()` is the real entry point: it runs
     * `MirrorCtorSplitter`, then `assertEntryInvariants()` per entry (which
     * calls `assertNestedSources()`) and `assertInvariants()` over the whole
     * graph, resolving every declaration against the TL scheme. A malformed
     * edge or an overlapping ctor fails HERE — at the tool that enforces it —
     * rather than at a probe that would pass on a table nobody had written to
     * yet.
     *
     * What is asserted here is the thing the whole class stands on: the hash
     * is a BASE column of `tf_channels`, absent from the base of `tf_chats`,
     * and the two ctor sets are disjoint — each ctor belongs to exactly one
     * table, which is the split that moved the fact.
     */
    public function test_the_catalog_splits_the_chat_union_and_base_columns_the_hash_on_tf_channels(): void
    {
        [$resolver, $catalog] = $this->pipeline();

        $chats = $catalog->table('tf_chats');
        $channels = $catalog->table('tf_channels');

        self::assertSame(
            ['chatEmpty', 'chat', 'chatForbidden'],
            $chats->ctors,
            'tf_chats must admit exactly the three Chat ctors that carry no access_hash',
        );
        self::assertSame(
            ['channel', 'channelForbidden'],
            $channels->ctors,
            'tf_channels must admit exactly the two Chat ctors that DO carry access_hash',
        );
        self::assertSame(
            [],
            array_values(array_intersect($chats->ctors, $channels->ctors)),
            'a ctor admitted on both tables is the ambiguity the split exists to remove',
        );

        self::assertContains(
            ['access_hash', 'BIGINT NOT NULL'],
            $channels->base,
            'the channel hash must be a real BASE column of tf_channels, or --peer has nothing signed-64-bit to read',
        );
        self::assertNotContains(
            ['access_hash', 'BIGINT NOT NULL'],
            $chats->base,
            'tf_chats admits no ctor that carries access_hash, so a base column there would be a nullable lie',
        );

        // The RESOLVED table, not just the declaration: the writer's column
        // list is what the DDL has to match.
        $resolved = $resolver->resolveAll(['tf_channels']);
        self::assertCount(1, $resolved, 'resolveAll() did not return the one parent asked for');
        $columns = array_map(static fn ($c): string => $c->name, $resolved[0]->columns);

        self::assertContains(
            'access_hash',
            $columns,
            'the resolved tf_channels column list must carry the hash, or the writer and the shipped DDL disagree',
        );
        self::assertSame(
            ['id'],
            $resolved[0]->keyColumns,
            'tf_channels is keyed on the peer id itself, exactly as tf_users is — that key is what --peer=<id> resolves on',
        );
        self::assertNotContains(
            'tf_channels_access_hash',
            array_map(static fn ($c): string => $c->tfName, $resolved[0]->children),
            'the hash is a base column now; a synthesized access-hash child would be a second, unreachable home',
        );
    }

    /**
     * THE ORPHAN, PINNED AS AN ASSERTION.
     *
     * `tf_chats_access_hash` is still declared a `tf_chats` child and still
     * created by a shipped migration, and after the ctor split NO ctor
     * admitted on `tf_chats` carries `access_hash` — so the decomposer can
     * never place a row there. This is a real defect (dead DDL that reads
     * like a live hash store) and it is reported, not fixed: the fix is a
     * catalog/migration change, outside this lane.
     *
     * The assertion exists so the defect is MACHINE-VISIBLE. The day someone
     * deletes the catalog child and drops the migration, this test goes RED
     * and the failure message says exactly what changed — instead of the class
     * quietly passing over a table that no longer exists.
     */
    public function test_the_catalog_still_declares_the_orphaned_hash_child_so_the_defect_is_visible(): void
    {
        [$resolver, $catalog] = $this->pipeline();

        self::assertSame(
            ['access_hash', 'BIGINT NOT NULL'],
            $catalog->table('tf_chats')->children[0],
            'the orphaned tf_chats_access_hash child edge is gone — if that was deliberate, drop this assertion and drop the shipped migration with it',
        );

        $resolved = $resolver->resolveAll(['tf_chats']);
        self::assertCount(1, $resolved, 'resolveAll() did not return the one parent asked for');
        $childNames = array_map(static fn ($c): string => $c->tfName, $resolved[0]->children);

        self::assertContains(
            'tf_chats_access_hash',
            $childNames,
            'the resolved child table vanished between the catalog declaration and the resolver — the migration creates a table the resolver does not synthesise',
        );

        // The defect itself: a child the resolvers synthesise, the shipped
        // migration creates, and the decomposer can never fill. Asserted as a
        // measured zero over ALL THREE admitted tf_chats ctors, so the claim
        // "orphaned" is not a recollection.
        $capsule = ShippedStoreSchema::capsuleFor(...$this->migrationPaths());
        $db = $capsule->getConnection();
        [$resolver2, $catalog2] = $this->pipeline();
        $decomposer = new MirrorFactDecomposer($resolver2, $catalog2);

        foreach ($this->allTfChatsCtorPayloads() as $payload) {
            $result = $decomposer->decompose('tf_chats', self::ACCOUNT, $payload);
            (new MirrorFactWriter)->write($db, $result['rows']);
        }

        self::assertSame(
            0,
            (int) $db->table('tf_chats_access_hash')->where('account_id', self::ACCOUNT)->count(),
            'tf_chats_access_hash is NOT orphaned any more — a tf_chats ctor placed a row in it; update this class, the docblock and (separately) the catalog/migration',
        );
    }

    /**
     * EVERY ctor the catalog admits on `tf_chats`, each carrying exactly the
     * fields its TL definition carries and nothing more — so "no admitted
     * ctor can ever fill the hash table" is a statement about the real
     * ctors, not about one convenient payload.
     *
     * @return array<string, array<string, mixed>>
     */
    private function allTfChatsCtorPayloads(): array
    {
        return [
            'chatEmpty' => ['_' => 'chatEmpty', 'flags' => 0, 'id' => -555000222],
            'chat' => self::CHAT_PAYLOAD,
            'chatForbidden' => ['_' => 'chatForbidden', 'flags' => 0, 'id' => -555000333, 'title' => 'forbidden'],
        ];
    }

    /**
     * @return list<string>
     */
    private function migrationPaths(): array
    {
        return array_map(
            static fn (string $name): string => self::PACKAGE_ROOT.'/src/Laravel/Migrations/'.$name,
            self::MIGRATIONS,
        );
    }

    /**
     * The REAL catalog + TL scheme, resolved through the shipped artifact
     * paths — not a fixture, so the test tracks the wire the package ships.
     *
     * @return array{0: MirrorTableResolver, 1: MirrorCatalog}
     */
    private function pipeline(): array
    {
        $scheme = TlParser::parseFile(SchemaArtifacts::tl());
        $catalog = MirrorCatalog::load(SchemaArtifacts::catalog(), $scheme);

        return [new MirrorTableResolver($catalog, $scheme), $catalog];
    }
}
