<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Ingest\MirrorResultIngester;
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
 * THE LEDGER MUST NOT LIE, AND IT MUST NOT START LYING ONE LAYER UP.
 *
 * `MirrorFactWriterValueNotStorableDegradesTest` proves the three buckets are
 * classified correctly at `write()`. This proves they are still three buckets
 * AFTER the key has been threaded up through the layers that rebuild rather
 * than forward — which is a distinct failure mode, and an easy one to introduce
 * while fixing a different defect: a threading change that appends
 * `valueClues` into the `fkClues` accumulator "just so it is not lost" makes
 * every ledger green while destroying the only property the split exists for.
 *
 * So each of the three axes is injected in turn, through the REAL
 * `MirrorResultIngester` — walker, decomposer, writer, transaction, all
 * production code — and each must land in ITS OWN bucket and nowhere else.
 *
 * WHY THE FAULT IS A GENUINE REFUSAL, not a staged one: `MysqlFaultInjecting-
 * Connection` (tests/Ingest/) executes the statement and only rewrites the
 * failure it actually got. The refusal below is real — the fixture sends a TL
 * `long` field as an object, the decomposer omits the column, and
 * `tf_channel_participants_subscription_until_date.subscription_until_date` is
 * `NOT NULL`, so sqlite refuses it with
 * `SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed`.
 * Only the driver code and message are rewritten, to the MySQL codes the real
 * server sends for each axis.
 *
 * Provenance of the fixture: `updateChannelParticipant` (TL 403) carrying a
 * `channelParticipant` (TL 766) whose `subscription_until_date` is an object.
 * The declared nested source plus the enclosing `channel_id` context is what
 * makes the row placeable at all — see `MirrorNestedSourcePlacesRowTest`, which
 * is where that half of the path is proven.
 */
final class ValueClueStaysDistinctThroughTheConsumerLayerTest extends TestCase
{
    private const PARTICIPANTS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php';

    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    private const ACCOUNT = 7;

    private const CHANNEL = 1001234567890;

    /**
     * The child table the fault lands on. It is the CHILD rather than
     * `tf_channel_participants` itself on purpose: the parent always carries
     * `constructor` (the decomposer fills it from the ctor), so the parent can
     * never be genuinely refused for a missing value, while the child table is
     * field-gated and omits its column outright when the payload's value does
     * not decode as the declared type.
     */
    private const FAULT_TABLE = 'tf_channel_participants_subscription_until_date';

    /**
     * `updateChannelParticipant` (TL 403) with `subscription_until_date` carried
     * as an OBJECT rather than a `long`.
     *
     * @return array<string, mixed>
     */
    private static function update(): array
    {
        return [
            '_' => 'updateChannelParticipant',
            'via_chatlist' => false,
            'channel_id' => self::CHANNEL,
            'date' => 1700000000,
            'actor_id' => 42,
            'user_id' => 42,
            'new_participant' => [
                '_' => 'channelParticipant',
                'user_id' => 42,
                'date' => 1700000000,
                // An object where the wire type says `long`. THIS is the real
                // shape of the fault class: the value cannot fit its column.
                'subscription_until_date' => ['_' => 'intOverflow', 'n' => 5],
            ],
            'qts' => 5,
        ];
    }

    /**
     * The three axes, each with the driver code and message a real MySQL server
     * sends for it. Asserted as DATA rather than one example, because the whole
     * claim is that they stay separate and a single case per axis would let a
     * classification that happened to agree on one code slip through.
     *
     * @return array<string, array{int, string, string, string}>
     */
    public static function axes(): array
    {
        return [
            'value — 1366 incorrect string value' => [
                1366,
                "Incorrect string value '\\xCE\\xC8' for column 'subscription_until_date' at row 1",
                'HY000',
                'valueClues',
            ],
            'value — 1265 data truncated' => [
                1265,
                "Data truncated for column 'subscription_until_date' at row 1",
                'HY000',
                'valueClues',
            ],
            'value — 1406 data too long' => [
                1406,
                "Data too long for column 'subscription_until_date' at row 1",
                'HY000',
                'valueClues',
            ],
            'value — 1364 no default' => [
                1364,
                "Field 'constructor' doesn't have a default value",
                'HY000',
                'valueClues',
            ],
            'relationship — 1452 foreign key' => [
                1452,
                'FOREIGN KEY constraint failed (`tf_channel_participants_subscription_until_date`.`account_id`, '
                    .'CONSTRAINT `fk_x` FOREIGN KEY (`account_id`) REFERENCES `telegram_accounts` (`id`))',
                'HY000',
                'fkClues',
            ],
            'modelling — 1054 unknown column' => [
                1054,
                "Unknown column 'nope' in 'field list'",
                '42S22',
                'unmappedClues',
            ],
        ];
    }

    /**
     * THE PROPERTY. Each fault lands in its own bucket, and in NO other.
     *
     * The exclusivity is the assertion that matters; the presence assertion
     * alone would be satisfied by a ledger that copied every clue into all
     * three buckets and thereby "lost" nothing.
     */
    #[DataProvider('axes')]
    public function test_each_axis_lands_in_its_own_bucket_and_nowhere_else(
        int $code,
        string $driverMessage,
        string $sqlState,
        string $ownBucket,
    ): void {
        $previous = Container::getInstance();

        try {
            $capsule = ShippedStoreSchema::capsuleFor(self::PARTICIPANTS_MIGRATION);
            $db = new MysqlFaultInjectingConnection(
                $capsule->getConnection()->getPdo(),
                self::FAULT_TABLE,
                $code,
                $driverMessage,
                $sqlState,
            );

            $report = $this->route()->ingest($db, self::ACCOUNT, self::update());

            // The three buckets must all EXIST on the consumer report. A missing
            // key here is the original defect in its purest form: the writer
            // produced the clue and this layer dropped it before anyone could
            // read it, which is a warning on one branch and a silent absence on
            // the other.
            foreach (['fkClues', 'unmappedClues', 'valueClues'] as $bucket) {
                self::assertArrayHasKey(
                    $bucket,
                    $report,
                    "the consumer report must carry {$bucket} — a rebuilt array that omits the key loses the bucket silently",
                );
            }

            self::assertSame(
                1,
                $db->injectedFaults(),
                'the insert must really have been attempted and really refused by the database',
            );

            self::assertCount(
                1,
                $report[$ownBucket],
                "driver code {$code} is the {$ownBucket} axis and must produce exactly one clue there",
            );

            foreach (['fkClues', 'unmappedClues', 'valueClues'] as $bucket) {
                if ($bucket === $ownBucket) {
                    continue;
                }

                self::assertSame(
                    [],
                    $report[$bucket],
                    "driver code {$code} is the {$ownBucket} axis and must not leak into {$bucket} — "
                    .'folding them is what sends the next reader to the wrong place',
                );
            }

            // And the honest accounting: a row the mirror does not hold is never
            // counted as placed. The sibling participant fact on the same
            // payload still lands.
            self::assertSame(
                1,
                $report['inserted'],
                'inserted counts rows PLACED — the refused row is not one, and the sibling row is',
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * THE COMMON PATH, at this layer: no faults, and all three buckets come back
     * empty — so a threaded key cannot make a healthy ingest LOOK degraded.
     */
    public function test_a_clean_payload_reports_three_empty_buckets(): void
    {
        $previous = Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(self::PARTICIPANTS_MIGRATION)->getConnection();

            $report = $this->route()->ingest($db, self::ACCOUNT, [
                '_' => 'updateChannelParticipant',
                'via_chatlist' => false,
                'channel_id' => self::CHANNEL,
                'date' => 1700000000,
                'actor_id' => 42,
                'user_id' => 42,
                'new_participant' => [
                    '_' => 'channelParticipant',
                    'user_id' => 42,
                    'date' => 1700000000,
                    'subscription_until_date' => 1800000000,
                ],
                'qts' => 5,
            ]);

            self::assertSame([], $report['fkClues']);
            self::assertSame([], $report['unmappedClues']);
            self::assertSame(
                [],
                $report['valueClues'],
                'a well-formed payload reports no value fault — the new bucket must be silent, not noisy',
            );
            self::assertSame(2, $report['inserted'], 'the parent and its child both place');
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * The production `MirrorResultIngester`, built from the COMMITTED artifacts
     * exactly as `MirrorIngesterFactory::buildResultRoute()` builds it — real
     * walker, real decomposer, real writer, real catalog. Only the connection
     * differs between the cases above.
     */
    private function route(): MirrorResultIngester
    {
        $scheme = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);

        return new MirrorResultIngester(
            new MirrorNestedSourceWalker($catalog),
            new MirrorFactDecomposer(new MirrorTableResolver($catalog, $scheme), $catalog),
            new MirrorFactWriter,
        );
    }
}
