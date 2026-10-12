<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasEndpointFixtures;
use PHPUnit\Framework\TestCase;

/**
 * THE COMPLETENESS GATE: every endpoint `EndpointCoverage::classify()`
 * classifies owes a fixture, and the shortfall is COUNTED.
 *
 * ## WHY THIS EXISTS ALONGSIDE `EveryClassifiedEndpointIngestsTest`
 *
 * That test answers "does this payload reach the fact log" — one DB ingestion
 * per endpoint it has a fixture for. It cannot answer "how many are missing",
 * because a missing fixture fails inside a per-endpoint data provider where the
 * number is spread across 171 rows. This test answers only the presence
 * question, with no database at all, and prints both sides of the ratio in the
 * failure message. So the backlog is a COUNT with a named list of the shortfall,
 * not a vague sense of untidiness.
 *
 * ## EVERYTHING HERE IS DERIVED, NEVER TRANSCRIBED
 *
 * `required` comes from `EndpointCoverage::classify()` over the layer-227
 * scheme — the same derivation Phase 2 gates use. `present` comes from the
 * fixtures actually assembled. A hand-written denominator would be a second copy
 * of the scheme: it drifts the moment the schema pipeline runs, and the
 * coverage claim becomes a lie that still reads as a safety net. That is the
 * same failure `EveryCataloguedTableIsReachableTest` is built to prevent.
 *
 * ## THIS GATE IS RED ON PURPOSE, AND THE RED IS THE POINT
 *
 * The missing endpoints are the project's real backlog. They are named and
 * counted, never papered over with invented payloads: an invented payload
 * passes against an invented expectation, so the gate would read as coverage of
 * 175 endpoints while covering four. The sibling lanes land one letter-group
 * file each under `Concerns/EndpointFixtures/`, and this gate turns green as
 * they land, with no edit here.
 *
 * ## WHAT IT DELIBERATELY DOES NOT CHECK
 *
 * Payload DEEP SHAPE is not fully proven here. `Loader` checks the two
 * falsifiable properties without a TL codec — the root node names itself, and
 * no constructor node wears a `#hex` id — but "every constructor node the codec
 * would decode carries a `_`" needs the codec to know which fields are
 * constructors, and a fixture missing one would ingest as a shallow row rather
 * than fail loudly. `EveryClassifiedEndpointIngestsTest` is what actually
 * observes the ingested depth; this gate only counts presence.
 */
final class EveryEndpointHasAFixtureTest extends TestCase
{
    use HasEndpointFixtures;

    /**
     * THE GATE. Every classified endpoint owes a fixture.
     *
     * @see EveryClassifiedEndpointIngestsTest
     *      for the payload-level obligation this does not duplicate.
     */
    public function test_every_classified_endpoint_has_a_fixture(): void
    {
        $coverage = self::fixtureCoverage();

        // Anti-vacuity: a classifier that returned nothing would make this gate
        // green while checking nothing, which is worse than red.
        self::assertGreaterThan(
            0,
            $coverage['required'],
            'EndpointCoverage::classify() returned no endpoints, so this gate would be vacuously green. '
            .'A gate that checks nothing is not a safety net.',
        );

        self::assertSame(
            [],
            $coverage['missing'],
            sprintf(
                'MEASURED: %d fixtures present, %d endpoints required, %d missing. Every classified '
                .'endpoint owes a fixture: land the letter-group file that owns it under '
                .'tests/Ingest/Concerns/EndpointFixtures/ (ownership is the character at index 6 of the '
                .'constructor name, uppercased). Missing: %s',
                $coverage['present'],
                $coverage['required'],
                count($coverage['missing']),
                implode(', ', $coverage['missing']),
            ),
        );
    }

    /**
     * No fixture may be offered for a constructor the classifier does not know.
     *
     * Such a fixture would read as coverage of an endpoint that does not exist,
     * inflating the numerator of the ratio above with fiction.
     */
    public function test_no_fixture_is_offered_for_an_unclassified_endpoint(): void
    {
        $coverage = self::fixtureCoverage();

        self::assertSame(
            [],
            $coverage['extra'],
            sprintf(
                'These %d fixtures name constructors EndpointCoverage::classify() does not classify: %s. '
                .'Either the classifier is behind the scheme, or a fixture was invented for an endpoint '
                .'that does not exist. Regenerate and re-derive rather than widening the classifier.',
                count($coverage['extra']),
                implode(', ', $coverage['extra']),
            ),
        );
    }

    /**
     * Two groups defining one constructor is a defect, not a merge.
     *
     * Ownership is derived from a letter rule, so a collision means one group
     * is claiming a letter it does not own. Asserted separately from the
     * coverage gate so the finding is attributable even when `endpointFixtures()`
     * has already raised on it.
     */
    public function test_no_two_groups_define_the_same_constructor(): void
    {
        $coverage = self::fixtureCoverage();

        $detail = [];
        foreach ($coverage['duplicates'] as $ctor => $groups) {
            $detail[] = $ctor.' claimed by '.implode(' and ', array_unique($groups));
        }

        self::assertSame(
            [],
            $coverage['duplicates'],
            'Duplicate fixture ownership: '.implode('; ', $detail)
            .'. Fix the group claiming a letter it does not own — do not let the merge pick a winner.',
        );
    }

    /**
     * A loader that matched nothing would guard nothing.
     *
     * Zero group files means every other method in this class is measuring the
     * four-entry base and calling it the fixture set.
     */
    public function test_the_loader_discovers_at_least_one_group_file(): void
    {
        $coverage = self::fixtureCoverage();

        self::assertNotEmpty(
            $coverage['groups'],
            'No EndpointFixtures/Group*.php was discovered, so the only fixtures in play are the four '
            .'curated base entries. The letter-group lanes have not landed yet.',
        );
    }

    /**
     * A group file that is present but unusable is reported, not swallowed.
     *
     * `Loader` tolerates a lane caught mid-write rather than fataling the suite,
     * which means the tolerance is only honest if the skip is VISIBLE. A skipped
     * group is a silent hole in the coverage ratio otherwise.
     */
    public function test_no_group_file_is_present_but_unusable(): void
    {
        $coverage = self::fixtureCoverage();

        $detail = [];
        foreach ($coverage['skipped'] as $skip) {
            $detail[] = $skip['group'].': '.$skip['reason'];
        }

        self::assertSame(
            [],
            $coverage['skipped'],
            'Group files present but not usable: '.implode('; ', $detail)
            .'. Each one is a hole in the coverage ratio; the loader tolerated it so the suite survives a '
            .'concurrent write, not so it could be forgotten.',
        );
    }
}
