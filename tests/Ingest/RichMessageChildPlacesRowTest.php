<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

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
 * `tf_messages_rich_message` is the one DIRECT child of a catalog parent that
 * `MirrorFactDecomposer::decomposeChildren()` walks and no shipped migration
 * ever created.
 *
 * `mirror-catalog.json` declares it on `tf_messages` as a child fact
 * (`["rich_message", "FK→RichMessage"]`). `MirrorTableResolver` materialises
 * that entry as a child table NAMED `{parent}_{field}` — `tf_messages_rich_message`
 * — and `MirrorFactDecomposer::decomposeChildren()` walks every direct child of
 * a resolved parent, reading `$payload['rich_message']` straight off the wire.
 * `message#7600b9d3` carries `rich_message:flags2.13?RichMessage`
 * (schema/sources/TL_telegram_v227.tl:121) and `message` is a top-level Update
 * constructor (`updateNewMessage`, `updateMessageID`, …), so the first live
 * update carrying one produced a row and inserted it into a table that no
 * migration created: `no such table: tf_messages_rich_message`.
 *
 * `MirrorFactWriter` deliberately does NOT filter this away. Its column-listing
 * pre-filter treats an EMPTY listing as "the table itself is missing" and lets
 * the insert raise, because a missing fact TABLE is a far bigger defect than an
 * unmodelled field. That line is correct and is what makes this loud rather
 * than silent — which is exactly why the fix has to be a real migration.
 *
 * The DDL is EXECUTED, never transcribed: `ShippedStoreSchema::capsuleFor()`
 * runs the real shipped migration files, so a column added or dropped in the
 * dial moves this assertion with it. The row asserted here is the row the REAL
 * decomposer produces for a REAL `message` payload — read out of
 * `MirrorFactDecomposer::decompose()`, not re-typed from the TL definition.
 *
 * Provenance: src/Schema/schema/mirror-catalog.json (tf_messages children →
 * `["rich_message","FK→RichMessage"]`); src/Schema/Mirror/MirrorTableResolver.php:255
 * (`{$parentTf}_{$fieldName}`); src/Schema/Mirror/MirrorFactDecomposer.php:169
 * (the direct-children walk); src/Schema/Mirror/MirrorFactWriter.php:116 (empty
 * listing ⇒ the insert raises); schema/sources/TL_telegram_v227.tl:121
 * (`message#7600b9d3 … rich_message:flags2.13?RichMessage`).
 */
final class RichMessageChildPlacesRowTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    /**
     * The shipped migrations needed for this row to place: the message subject
     * table, its message-scoped child tables, and the rich-message table added
     * for this defect.
     */
    private const MIGRATIONS = [
        '2026_09_14_200010_create_tf_messages_tables.php',
        '2026_09_14_200011_create_tf_message_entities_tables.php',
        '2026_09_14_200012_create_tf_message_medias_tables.php',
        '2026_09_29_000200_create_tf_messages_rich_message_table.php',
    ];

    private const ACCOUNT = 7;

    /**
     * A `message` carrying a `richMessage`, exactly as the wire decodes it.
     *
     * `rich_message` is flags2.13-gated and absent from virtually every
     * message, which is why the defect survived: row existence = fact
     * existence, so nothing placed until a rich message actually arrived.
     *
     * @var array<string, mixed>
     */
    private const PAYLOAD = [
        '_' => 'message',
        'id' => 4242,
        'peer_id' => ['_' => 'peerUser', 'user_id' => 77],
        'date' => 1735689600,
        'message' => 'the body is a plain string; the blocks live in rich_message',
        'rich_message' => [
            '_' => 'richMessage',
            'rtl' => true,
            'part' => false,
            'blocks' => [
                ['_' => 'pageBlockHeader', 'text' => ['_' => 'textPlain', 'text' => 'Heading']],
            ],
        ],
    ];

    /**
     * The row the real decomposer builds, in the real column order it emits.
     *
     * Derived by RUNNING `MirrorFactDecomposer::decompose()`, not by reading
     * the TL line: `account_id` is the scoping key, `id` is the PARENT key
     * travelling in as the child FK, and `rtl` / `part` are boolean columns —
     * `childRow()` writes those unconditionally as `(int) (bool)`, so both are
     * present on every rich-message row even when the payload omits them.
     *
     * @var array<string, int>
     */
    private const EXPECTED_CHILD_ROW = [
        'account_id' => self::ACCOUNT,
        'id' => 4242,
        // FIXTURE CORRECTION (2026-10-03, Task 2.2). The rich-message row is a
        // CHILD of the message, and a child cannot exist without its parent, so
        // its key must be at least as specific as the parent's — and the
        // parent's is (account_id, peer_type, peer_id, id), because Telegram
        // numbers messages PER CHAT. The peer legs are not decoration the
        // decomposer invented: PAYLOAD carries a real `peer_id`, `fillPeerHalf()`
        // resolves both halves from it, and `decomposeChildren()` copies them
        // into the child row. Before this change the child row was keyed on
        // (account_id, id) alone and would have collapsed across dialogs. The
        // assertion is unchanged; the expectation now matches what the
        // decomposer really produces.
        'peer_type' => 1,
        'peer_id' => 77,
        'rtl' => 1,
        'part' => 0,
    ];

    public function test_a_message_carrying_a_rich_message_places_the_child_row(): void
    {
        $capsule = ShippedStoreSchema::capsuleFor(...$this->migrationPaths());
        $db = $capsule->getConnection();
        [$resolver, $catalog] = $this->pipeline();

        $result = (new MirrorFactDecomposer($resolver, $catalog))->decompose('tf_messages', self::ACCOUNT, self::PAYLOAD);
        $written = (new MirrorFactWriter)->write($db, $result['rows']);

        self::assertSame(
            count($result['rows']),
            $written['inserted'],
            'a decomposed row did not land — a raise on the rich-message child aborts the write: '
            .$this->describe($result['rows']).' | fkClues: '.json_encode($written['fkClues']),
        );
        self::assertSame([], $written['unmappedClues'], 'a column the shipped DDL does not have is a modelling gap, not a green write');

        $row = $db->table('tf_messages_rich_message')->where('account_id', self::ACCOUNT)->first();
        self::assertNotNull($row, 'tf_messages_rich_message holds no row for the mirrored message');
        $landed = array_map(static fn ($v): int => (int) $v, (array) $row);
        self::assertSame(
            self::EXPECTED_CHILD_ROW,
            $landed,
            'the landed rich-message row is not the row MirrorFactDecomposer produced',
        );
    }

    /**
     * The defect as it presented, and the proof the fix is what changed.
     *
     * The SAME decomposer + writer + payload, against a schema built from the
     * shipped migration set WITHOUT the rich-message table, raises
     * `no such table` — out of `MirrorFactWriter::write()`. This is what the
     * ingest path did before the migration existed, on a host whose
     * `php artisan migrate` had returned green.
     */
    public function test_without_the_shipped_table_the_same_row_raises_no_such_table(): void
    {
        $without = array_values(array_filter(
            $this->migrationPaths(),
            static fn (string $p): bool => ! str_contains($p, 'rich_message'),
        ));
        self::assertNotSame([], $without, 'the control must still build a real shipped schema');

        $capsule = ShippedStoreSchema::capsuleFor(...$without);
        $db = $capsule->getConnection();
        [$resolver, $catalog] = $this->pipeline();

        $result = (new MirrorFactDecomposer($resolver, $catalog))->decompose('tf_messages', self::ACCOUNT, self::PAYLOAD);

        // Guard: the control is only meaningful if the decomposer really does
        // emit the row. Without this, a green control would prove nothing.
        self::assertContains(
            'tf_messages_rich_message',
            array_column($result['rows'], 'table'),
            'the decomposer no longer produces a tf_messages_rich_message row — this control and its migration are stale',
        );

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/tf_messages_rich_message/');

        (new MirrorFactWriter)->write($db, $result['rows']);
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

    /**
     * @param  list<array{table: string, row: array<string, int|string|null>}>  $rows
     */
    private function describe(array $rows): string
    {
        $tables = array_column($rows, 'table');

        return 'decomposed tables: '.implode(', ', $tables);
    }
}
