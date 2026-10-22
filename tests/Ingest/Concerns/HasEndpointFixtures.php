<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns;

use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\EndpointCoverage;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures\Loader;
use RuntimeException;

/**
 * One realistic decoded payload per classified MTProto endpoint.
 *
 * ## WHY THE LIST IS DERIVED AND THE VALUES ARE HAND-WRITTEN
 *
 * The endpoint SET comes from `EndpointCoverage::classify()` — the same
 * derivation Phase 2 gates. A transcribed list of constructor names is a second
 * copy of the scheme: it drifts the moment the schema pipeline runs, and the
 * coverage claim becomes a lie that still reads as a safety net.
 *
 * The PAYLOAD VALUES are hand-written, because a payload is an observation of
 * what the wire produces and there is nowhere to derive it from. They are
 * shaped as the wire DECODES them — `_` set on every constructor node, vectors
 * as lists, peers as `peerChannel#channel_id` objects — because a fixture built
 * from a convenient shape proves nothing about the shape that arrives. This is
 * the same requirement `EveryCataloguedTableIsReachableTest` states, for the
 * same reason.
 *
 * The shape convention is `HasNestedUpdateFixtures`': `_` on every constructor
 * node, set-flag booleans arriving as EXPLICIT keys, vectors as lists, peers as
 * objects. That trait is deliberately NOT refactored here — another lane
 * depends on it — so this is a sibling that follows the convention rather than
 * an extraction of it.
 *
 * ## THE ACCOUNT
 *
 * One synthetic tenant for every endpoint, so an FK failure names the ENDPOINT
 * rather than a tenant mismatch. Since `7dd07b4a` landed 265 tenant foreign
 * keys, a `tf_*` row carrying an `account_id` is refused outright unless a
 * parent `telegram_accounts` row exists — and the key is `user_id`, not `id`.
 * Each consuming test seeds it; `test_the_worked_example_endpoint_lands_its_row_and_its_nested_rows`
 * proves the seeding is real by inserting rows that need it.
 *
 * ## NO CREDENTIALS
 *
 * Every value here is synthetic: no session string, no real `access_hash` from a
 * live account, no database password. The `access_hash` below is an integer
 * chosen to be obviously fake.
 */
trait HasEndpointFixtures
{
    /** The tenant every endpoint fixture is ingested under. */
    protected static function endpointFixtureAccount(): int
    {
        return 5150;
    }

    /** Message id used by the worked example, distinct per endpoint to avoid key collisions. */
    protected const ENDPOINT_MESSAGE_ID = 770001;

    protected const ENDPOINT_MESSAGE_TEXT = 'endpoint fixture message';

    protected const ENDPOINT_MESSAGE_DATE = 1726000000;

    /** The peer the worked-example message lives in — a USER dialog, via `peerUser`. */
    protected const ENDPOINT_PEER_ID = 501558149;

    /**
     * The `tf_messages.peer_type` the wire's `peerUser` maps to. Written as a
     * literal because it is a CLAIM about the mapping, not a transcription of a
     * column list: if the writer derives a different discriminator for
     * `peerUser`, the worked-example lookup finds nothing and says so.
     */
    protected const ENDPOINT_PEER_TYPE = 1;

    /** A synthetic user, distinct per role so a fabricated key is visible. */
    protected const ENDPOINT_USER_ID = 501558149;

    /**
     * The derived classification, as a plain list of rows.
     *
     * Memoised per process: `TlParser::parseFile()` re-reads and re-parses the
     * whole layer-227 scheme, and a data provider over 175 endpoints would
     * otherwise pay that 175 times.
     *
     * @return list<array{ctor:string,kind:string,tables:list<string>,reason:string}>
     */
    protected static function classifiedEndpoints(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $scheme = TlParser::parseFile(dirname(__DIR__, 3).'/schema/sources/TL_telegram_v227.tl');

        return $cache = EndpointCoverage::classify(
            $scheme,
            MirrorCatalog::load(SchemaArtifacts::catalog(), $scheme),
        )->endpoints();
    }

    /**
     * Endpoints Phase 3 requires a fixture for: every classified kind.
     *
     * @return list<string>
     */
    protected static function insertableEndpoints(): array
    {
        return array_map(
            static fn (array $e): string => $e['ctor'],
            self::classifiedEndpoints(),
        );
    }

    /**
     * THE CURATED BASE — the four worked-example payloads.
     *
     * Not 175. That is deliberate and not an oversight to be tidied in one
     * commit: `test_every_classified_endpoint_reaches_the_fact_log` names every
     * missing one, in the failure message, as the project's real backlog made
     * executable. Filling them with invented payloads would prove nothing — an
     * invented payload passes against an invented expectation, and the gate
     * would read as a safety net over 175 endpoints while actually covering
     * four.
     *
     * MEASURED in the task that wrote this: 175 classified endpoints, 171 with
     * no fixture, from
     * `vendor/bin/phpunit --no-coverage --filter test_every_classified_endpoint_reaches_the_fact_log tests/Ingest/EveryClassifiedEndpointIngestsTest.php`
     * → `Tests: 175, Assertions: 179, Failures: 171`.
     *
     * ## WHY THE BASE IS SEPARATE FROM THE GROUP FILES
     *
     * These four are the WORKED EXAMPLE: the peer/flag/media shapes that
     * `EveryClassifiedEndpointIngestsTest`'s nested value assertions are written
     * against. They stay hand-written and authoritative so a sibling lane
     * cannot redefine what `peerUser` looks like between an assertion and the
     * payload it inspects.
     *
     * They also fall INSIDE the sibling letter-groups, because the group rule is
     * the character at index 6 of the constructor name (after the `update`
     * prefix): `updateNewMessage` → `N`, `updateDeleteMessages` → `D`,
     * `updateUserStatus` → `U`, `updateReadHistoryInbox` → `R`. So the merge
     * below is explicitly BASE-WINS, not last-writer-wins — otherwise four of
     * the sibling lanes would collide with the base on every single run.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function baseEndpointFixtures(): array
    {
        return [
            // ---- root-fact, with nested sources beneath it ----
            'updateNewMessage' => [
                '_' => 'updateNewMessage',
                'message' => self::messagePayload(self::ENDPOINT_MESSAGE_ID),
                'pts' => 101,
                'pts_count' => 1,
            ],

            'updateDeleteMessages' => [
                '_' => 'updateDeleteMessages',
                'messages' => [self::ENDPOINT_MESSAGE_ID, self::ENDPOINT_MESSAGE_ID + 1],
                'pts' => 102,
                'pts_count' => 2,
            ],

            'updateUserStatus' => [
                '_' => 'updateUserStatus',
                'user_id' => self::ENDPOINT_USER_ID,
                'status' => ['_' => 'userStatusOnline', 'expires' => 1700000600],
            ],

            // ---- the generic fact log is the whole surface ----
            'updateReadHistoryInbox' => [
                '_' => 'updateReadHistoryInbox',
                'folder_id' => 1,
                'peer' => ['_' => 'peerUser', 'user_id' => self::ENDPOINT_USER_ID],
                'max_id' => self::ENDPOINT_MESSAGE_ID,
                'still_unread_count' => 0,
                'pts' => 103,
                'pts_count' => 1,
            ],
        ];
    }

    /**
     * ctor => a decoded payload shaped as the wire produces: the curated base
     * merged with every discoverable group file's `payloads()`.
     *
     * ## WHY A LOADER AND NOT A BIGGER ARRAY
     *
     * 175 hand-written payloads in one array is one file nobody reviews and
     * nobody can extend in parallel. The group files under `EndpointFixtures/`
     * are one file per letter-group, so seven lanes can each land a slice
     * concurrently, and a group landing later needs no edit here at all.
     *
     * ## DUPLICATES ARE A DEFECT, NOT A MERGE
     *
     * Two groups claiming one constructor means one of them is wrong about
     * ownership. `array_merge` would let the last writer win and hide it, so the
     * collision is raised with BOTH group names instead.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function endpointFixtures(): array
    {
        $collected = Loader::collect();

        if ($collected['duplicates'] !== []) {
            $detail = [];
            foreach ($collected['duplicates'] as $ctor => $groups) {
                $detail[] = sprintf('%s claimed by %s', $ctor, implode(' and ', array_unique($groups)));
            }

            throw new RuntimeException(
                'Two endpoint-fixture groups define the same constructor: '.implode('; ', $detail)
                .'. Ownership is derived from the character at index 6 of the constructor name, so a '
                .'collision means one group is claiming a letter it does not own. Fix the owning group; '
                .'do NOT let a merge silently pick a winner.',
            );
        }

        $base = self::baseEndpointFixtures();
        $groups = $collected['payloads'];

        // BASE WINS, deliberately: the four worked-example payloads are what the
        // value assertions are written against. See baseEndpointFixtures().
        foreach (array_keys($base) as $ctor) {
            unset($groups[$ctor]);
        }

        return array_merge($base, $groups);
    }

    /**
     * The completeness measurement, derived — never a transcribed list.
     *
     * Everything here is a set operation over `EndpointCoverage::classify()` and
     * the fixtures actually present, so the counts cannot drift from reality
     * the way a hand-written denominator does.
     *
     * @return array{
     *     required: int,
     *     present: int,
     *     missing: list<string>,
     *     extra: list<string>,
     *     groups: list<string>,
     *     skipped: list<array{group: string, reason: string}>,
     *     duplicates: array<string, list<string>>,
     * }
     */
    protected static function fixtureCoverage(): array
    {
        $collected = Loader::collect();
        $fixtures = self::endpointFixtures();

        $required = self::insertableEndpoints();
        $present = array_keys($fixtures);

        $missing = array_values(array_diff($required, $present));
        sort($missing);

        // A fixture for something the classifier does not know is a fabricated
        // endpoint: it would read as coverage of a ctor that does not exist.
        $extra = array_values(array_diff($present, $required));
        sort($extra);

        return [
            'required' => count($required),
            'present' => count($present),
            'missing' => $missing,
            'extra' => $extra,
            'groups' => $collected['groups'],
            'skipped' => $collected['skipped'],
            'duplicates' => $collected['duplicates'],
        ];
    }

    /**
     * A `message#7600b9d3` shaped as the wire decodes it: `_` set, flag-gated
     * fields present as explicit keys, a peer object, and a media object.
     *
     * The `peerChannel#channel_id` convention is documented on
     * `HasNestedUpdateFixtures`; this uses `peerUser` deliberately, so the two
     * fixtures between them cover both peer shapes rather than one twice.
     *
     * @return array<string, mixed>
     */
    protected static function messagePayload(int $id, int $peerId = self::ENDPOINT_PEER_ID): array
    {
        return [
            '_' => 'message',
            'flags' => (1 << 9), // has media
            'id' => $id,
            'from_id' => ['_' => 'peerUser', 'user_id' => self::ENDPOINT_USER_ID],
            'peer_id' => ['_' => 'peerUser', 'user_id' => $peerId],
            'date' => self::ENDPOINT_MESSAGE_DATE,
            'message' => self::ENDPOINT_MESSAGE_TEXT,
            'media' => [
                '_' => 'messageMediaDocument',
                'document' => [
                    '_' => 'document',
                    'id' => 5150012345678901234,
                    // Synthetic, and obviously so.
                    'access_hash' => -7779317524312221622,
                    'date' => self::ENDPOINT_MESSAGE_DATE,
                    'mime_type' => 'application/pdf',
                    'size' => 4096,
                    'dc_id' => 2,
                ],
            ],
        ];
    }
}
