<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Concerns;

use MeRezaRezaei\Teleframe\Schema\Mirror\EndpointCoverage;
use PHPUnit\Framework\AssertionFailedError;

/**
 * ONE copy of the per-kind endpoint obligation, shared by the engine lanes.
 *
 * ## THE DRIFT THIS EXISTS TO PREVENT
 *
 * "Every classified endpoint must reach storage" is not one obligation, it is
 * three, and which one applies is a property of the constructor's DECLARED SHAPE
 * (`EndpointCoverage::classify()`), not of the endpoint's name. Before this trait
 * the branch that chose between them lived inside
 * `tests/Ingest/EveryClassifiedEndpointIngestsTest.php` alone, so the MySQL twin
 * had one assertion for all three obligations and was wrong for two of them:
 * measured on this tree with
 *
 * ```
 * TELEFRAME_MYSQL=1 TELEFRAME_MYSQL_DATABASE=teleframe_carrier \
 *   vendor/bin/phpunit --no-coverage tests/MySql/EveryClassifiedEndpointIngestsOnMysqlTest.php
 * → Tests: 4, Assertions: 180, Failures: 2
 * ```
 *
 * and the two failures were exactly the twelve endpoints whose obligation is not
 * "a row naming my own constructor". Copying that branch into the MySQL file
 * would have made it a SECOND copy of engine-independent logic — the one kind of
 * duplication that rots silently, because both copies keep passing while they
 * disagree with each other. So the branch is here instead, and both lanes
 * parameterise only the two engine-specific primitives below.
 *
 * ## THE THREE OBLIGATIONS, DERIVED NOT TRANSCRIBED
 *
 * | obligation         | which kind                        | what must be true after `ingest()`                                     |
 * |--------------------|-----------------------------------|--------------------------------------------------------------------------|
 * | `own-row`          | root-fact / nested-source / log-only / unmodelled | a `tf_updates` row carries THIS constructor      |
 * | `unwrap-vector`    | `EndpointCoverage::KIND_UPDATE_CARRIER`          | EVERY member of the carried `Update` lands under its OWN constructor, and NO row names the carrier |
 * | `no-row`           | `EndpointCoverage::KIND_RPC_RESULT`             | ZERO rows, and the endpoint is still classified so its absence is visible |
 *
 * The map is total by construction: `obligationOf()` reads the kind out of the
 * derived classification and FAILS LOUDLY on a kind it does not know, exactly as
 * `EndpointCoverage::build()` does. A constructor with no obligation is a bug in
 * this trait, not a silent pass.
 *
 * ## WHY `unwrap-vector` IS STRONGER, NOT WEAKER
 *
 * The assertion it replaces asked a carrier for a row naming itself. That row is
 * unobtainable by design — `updates#74ae4240` is the reply to
 * `updates.getDifference`, and a row naming it would record a fact that never
 * happened as though it were an update — while `updates.difference` DID satisfy
 * it, by storing the container and dropping the `updateChannel` its
 * `other_updates` had fetched. So the old gate was simultaneously unmeetable by
 * the carriers and satisfied by one that loses live backfill data. This trait
 * asks each carrier for N of N members, which is a strictly larger obligation.
 *
 * ## THE MEMBER LIST IS READ SHAPEWISE, NOT TRANSCRIBED
 *
 * `carrierMembersOf()` walks the fixture's OWN top-level fields and takes every
 * node whose `_` starts with `update`. Both carrier shapes on this tree are
 * covered, and they are not the same shape:
 *
 * - a `Vector<Update>` member: `updates` calls its field `updates` and
 *   `updatesCombined` calls its field `updates`, but `updates.difference`,
 *   `updates.differenceSlice` and `updates.channelDifference` all call theirs
 *   `other_updates` — so ANY transcribed field name is wrong for at least one
 *   carrier;
 * - a SINGULAR `update:Update` ref: `updateShort` carries `update`, a MAP, not
 *   a list.
 *
 * A list-only walk therefore reports ZERO members for `updateShort`, which is
 * why `carrierMembersOf()` handles both. MEASURED 2026-10-05 on this tree by
 * `php /tmp/cfcarrier/fx.php`, over `HasEndpointFixtures::endpointFixtures()`:
 *
 * ```
 * updateShort                       -> ["updatePtsChanged"]
 * updates                           -> ["updateChannelTooLong"]
 * updatesCombined                   -> ["updatePtsChanged"]
 * updates.difference                -> ["updateChannel"]
 * updates.differenceSlice           -> ["updateChannelTooLong"]
 * updates.channelDifference         -> ["updatePinnedDialogs"]
 * ```
 *
 * and the same probe shows the list-only walk returning `[]` for `updateShort`.
 * `assertEndpointObligationIsDischarged()` asserts the member list is NON-EMPTY
 * before counting anything, so a carrier whose member shape this walk cannot see
 * goes RED naming the carrier instead of passing vacuously.
 *
 * ## THE TWO PRIMITIVES AN ENGINE LANE MUST SUPPLY
 *
 * The obligation logic touches the database exactly twice — "does this account
 * hold a row naming this constructor" and "how many rows does this account hold"
 * — and both reads are the same query on both engines. Everything ABOVE those two
 * calls is engine-independent, which is why it lives here.
 *
 * A lane supplies them by implementing the two abstract methods. The host class
 * must extend `PHPUnit\Framework\TestCase` (or `Assert`), because this trait
 * asserts through `self::`.
 *
 * ## NO CREDENTIALS
 *
 * Every value this trait touches is a constructor name read from the parsed
 * scheme, or a payload read from the fixture files. No session string, no real
 * `access_hash`, no database password.
 */
trait AssertsEndpointInsertObligations
{
    /** The endpoint's own `tf_updates` row must carry its own constructor. */
    protected const OBLIGATION_OWN_ROW = 'own-row';

    /** Every carried `Update` must land under its own constructor; the carrier mints nothing. */
    protected const OBLIGATION_UNWRAP_VECTOR = 'unwrap-vector';

    /** Nothing may be placed at all, and the endpoint must still be classified. */
    protected const OBLIGATION_NO_ROW = 'no-row';

    /** Every obligation this trait knows, so a sweep can assert its branch is non-vacuous. */
    protected const ENDPOINT_OBLIGATIONS = [
        self::OBLIGATION_OWN_ROW,
        self::OBLIGATION_UNWRAP_VECTOR,
        self::OBLIGATION_NO_ROW,
    ];

    /** Does `account_id` hold a `tf_updates` row whose `constructor` is exactly `$ctor`? */
    abstract protected function hasEndpointFactRow(int $accountId, string $ctor): bool;

    /** How many `tf_updates` rows does `account_id` hold in total? */
    abstract protected function countEndpointFactRows(int $accountId): int;

    /**
     * What ONE endpoint owes storage, decided from the DERIVED classification.
     *
     * Never a name list: `EndpointCoverage::classify()` decides from the parsed
     * constructor's declared result type and its `Update`-typed parameters, and
     * this method only maps that kind onto the obligation. An unknown kind is a
     * bug in the classification or in the map below, so it fails loudly instead
     * of falling back to `own-row` — a silent fallback would turn a carrier into
     * an endpoint asked for a row it must never mint.
     *
     * @throws AssertionFailedError when `$ctor` is unclassified
     *                              or carries an unknown kind
     */
    final protected static function obligationOf(string $ctor): string
    {
        $kind = self::kindOf($ctor);

        return match ($kind) {
            EndpointCoverage::KIND_UPDATE_CARRIER => self::OBLIGATION_UNWRAP_VECTOR,
            EndpointCoverage::KIND_RPC_RESULT => self::OBLIGATION_NO_ROW,
            EndpointCoverage::KIND_ROOT_FACT,
            EndpointCoverage::KIND_NESTED_SOURCE,
            EndpointCoverage::KIND_LOG_ONLY,
            EndpointCoverage::KIND_UNMODELLED => self::OBLIGATION_OWN_ROW,
            default => self::fail(sprintf(
                'EndpointCoverage classified %s as the unknown kind "%s". The kind set is closed, so this '
                .'is a bug in the classification or in AssertsEndpointInsertObligations::obligationOf() — '
                .'and it must fail here rather than fall back to an obligation, because a wrong fallback '
                .'asks a carrier for a row it must never mint.',
                $ctor,
                $kind,
            )),
        };
    }

    /**
     * Every classified endpoint's kind, as one map, read from the derivation.
     *
     * @return array<string,string> ctor => kind
     */
    final protected static function endpointKinds(): array
    {
        $kinds = [];
        foreach (self::classifiedEndpoints() as $endpoint) {
            $kinds[$endpoint['ctor']] = $endpoint['kind'];
        }

        return $kinds;
    }

    /**
     * The kind of one endpoint, or a loud failure.
     *
     * @return string one of `EndpointCoverage::KIND_*`
     */
    final protected static function kindOf(string $ctor): string
    {
        $kind = self::endpointKinds()[$ctor] ?? null;

        self::assertNotNull(
            $kind,
            "{$ctor} is not classified at all. Every endpoint's obligation is decided by the derived "
            .'classification, so an unclassified constructor cannot be given one — re-derive '
            .'EndpointCoverage::classify() rather than naming an obligation here.',
        );

        return $kind;
    }

    /**
     * How many endpoints of each kind the derivation currently reports.
     *
     * The receipt a lane publishes next to its per-kind assertions, and the
     * non-vacuity guard: a classification that silently moved every constructor
     * into one kind would leave the other branches unexercised while the sweep
     * stayed green, so a lane asserts the branches it exercises are NON-EMPTY.
     *
     * @return array<string,int>
     */
    final protected static function endpointKindCounts(): array
    {
        $counts = [];
        foreach (EndpointCoverage::KINDS as $kind) {
            $counts[$kind] = 0;
        }

        foreach (self::endpointKinds() as $kind) {
            $counts[$kind]++;
        }

        return $counts;
    }

    /**
     * The update constructors a carrier's payload actually carries, at any of the
     * TWO shapes a carrier can use.
     *
     * Read shape-wise from the fixture and restricted to the carrier's OWN
     * top-level fields — which is where a `Vector<Update>` or an `update:Update`
     * ref necessarily hangs, since a carried update is a direct member, not a
     * nested object three levels down.
     *
     * ## WHY A NAME PREFIX ALONE IS NOT ENOUGH — MEASURED, AND IT BIT
     *
     * The obvious filter is `str_starts_with($name, 'update')`, and it is wrong.
     * `updates.state` STARTS WITH `update`, so the `state` field of the
     * `updates.difference` and `updates.differenceSlice` payloads — which is an
     * `updates.State` RPC RESULT, not a carried update — passes it. A first
     * version of this walk did exactly that, and the MySQL sweep reported it
     * faithfully:
     *
     * ```
     * updates.difference is an update-carrier whose carried vector held
     * updates.state, but no tf_updates row carries that member's constructor.
     * ```
     *
     * which is a red assertion blaming the ingestor for a member the fixture never
     * carried. So a candidate must ALSO be a constructor the classification calls
     * an actual `Update` — that is, classified and NOT one of the two RPC-result
     * kinds. The rule is read out of `EndpointCoverage::classify()` rather than
     * transcribed, and it holds for every name shape in the scheme: a carried
     * update is always a constructor of the `Update` type, and every one of those
     * is in the classified universe by construction.
     *
     * @param  array<string,mixed>  $payload
     * @return list<string> the inner constructors, in payload order
     */
    final protected static function carrierMembersOf(array $payload): array
    {
        $members = [];

        foreach ($payload as $value) {
            if (! is_array($value)) {
                continue;
            }

            // Shape 1 — a `Vector<Update>`: a list whose items are updates.
            if (array_is_list($value)) {
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $members[] = $item;
                    }
                }

                continue;
            }

            // Shape 2 — a singular `update:Update` ref: a map that IS an update.
            // `updateShort` uses this, so a list-only walk reports it as carrying
            // nothing — see the trait docblock.
            $members[] = $value;
        }

        $names = [];
        foreach ($members as $node) {
            $name = self::carriedUpdateName($node);
            if ($name !== null) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * The carried-update constructor name of one node, or NULL when the node is
     * not a carried `Update`.
     *
     * @param  array<string,mixed>  $node
     */
    private static function carriedUpdateName(array $node): ?string
    {
        $name = $node['_'] ?? null;

        if (! is_string($name) || ! str_starts_with($name, 'update')) {
            return null;
        }

        $kind = self::endpointKinds()[$name] ?? null;

        if ($kind === null) {
            return null; // not a constructor the scheme declares
        }

        if ($kind === EndpointCoverage::KIND_UPDATE_CARRIER || $kind === EndpointCoverage::KIND_RPC_RESULT) {
            return null; // an RPC RESULT that merely shares the `update` name prefix
        }

        return $name;
    }

    /**
     * THE ASSERTION. After `ingest()` has run for `$ctor` under `$accountId`,
     * prove this endpoint discharged the obligation its kind actually carries.
     *
     * One method, one branch per kind, shared by every engine lane. The host
     * calls it after ingesting and lets whatever it raises stand — a caller that
     * wants to COLLECT failures across 175 endpoints catches
     * `AssertionFailedError` per endpoint and reports them together, and that is
     * strictly more informative than aborting on the first.
     *
     * @param  array<string,mixed>  $payload  the fixture that was ingested, read shape-wise
     */
    final protected function assertEndpointObligationIsDischarged(
        string $ctor,
        array $payload,
        int $accountId,
    ): void {
        $obligation = self::obligationOf($ctor);

        if ($obligation === self::OBLIGATION_UNWRAP_VECTOR) {
            $this->assertCarrierUnwrapped($ctor, $payload, $accountId);

            return;
        }

        if ($obligation === self::OBLIGATION_NO_ROW) {
            $this->assertRpcResultOwesNoRow($ctor, $accountId);

            return;
        }

        self::assertTrue(
            $this->hasEndpointFactRow($accountId, $ctor),
            "{$ctor} ingested without raising, but wrote no tf_updates row carrying its own constructor "
            ."(obligation: {$obligation}). Every such endpoint must reach the fact log; one that reaches "
            .'nothing is a SILENT DROP, which is worse than a refusal because nothing reports it.',
        );
    }

    /**
     * A CARRIER places EVERY member of the update vector it carries, each under
     * its OWN constructor, and must not mint a row for itself.
     *
     * @param  array<string,mixed>  $payload
     */
    private function assertCarrierUnwrapped(string $ctor, array $payload, int $accountId): void
    {
        $members = self::carrierMembersOf($payload);

        // NON-VACUITY, asserted BEFORE anything is counted. A carrier fixture
        // whose member list this walk cannot see would make every assertion below
        // pass for the wrong reason, so the gate is red until the shape is
        // understood rather than green because it measured nothing.
        self::assertNotEmpty(
            $members,
            "{$ctor} is classified update-carrier, so it owes an unwrap, but no update member was found "
            .'in its payload\'s top-level fields. Either the fixture\'s carried vector is empty, or the '
            .'carrier uses a member shape AssertsEndpointInsertObligations::carrierMembersOf() cannot see — '
            .'a carrier with no visible member proves nothing. Fix the walk or the fixture; do NOT relax '
            .'this assertion.',
        );

        foreach ($members as $member) {
            self::assertTrue(
                $this->hasEndpointFactRow($accountId, $member),
                "{$ctor} is an update-carrier whose carried vector held {$member}, but no tf_updates row "
                .'carries that member\'s constructor. The carrier was STORED instead of being unwrapped, so '
                .'the real updates it fetched were dropped — which is exactly what happens on the live '
                .'getDifference / getChannelDifference backfill path.',
            );
        }

        self::assertSame(
            count($members),
            $this->countEndpointFactRows($accountId),
            "{$ctor} is an update-carrier, placed its ".count($members).' member(s), but the account holds a '
            .'different number of update rows. The carrier itself must not mint one: a row naming a carrier '
            .'records a thing that never happened as though it were an update.',
        );

        self::assertFalse(
            $this->hasEndpointFactRow($accountId, $ctor),
            "{$ctor} is an update-carrier and wrote a tf_updates row naming ITSELF. A carrier is an RPC "
            .'result, not an update, so that row is a fact that did not happen.',
        );
    }

    /**
     * An RPC RESULT owes NO row — and is still a classified endpoint, so the
     * gate PROVES its absence rather than merely permitting it.
     *
     * The second half is what keeps this from being a quiet deletion. Reclassifying
     * a constructor as `rpc-result` removes a `tf_updates` row obligation from it,
     * and an obligation nothing asserts is an endpoint nothing notices — so the
     * endpoint is asserted present in the DERIVED classification, which is the
     * only thing that makes "no row owed" a statement rather than a hole.
     */
    private function assertRpcResultOwesNoRow(string $ctor, int $accountId): void
    {
        self::assertArrayHasKey(
            $ctor,
            self::endpointKinds(),
            "{$ctor} owes no update row, so nothing asserts it exists — which makes its absence from the "
            .'classification undetectable. It must still be classified.',
        );

        self::assertSame(
            0,
            $this->countEndpointFactRows($accountId),
            "{$ctor} is classified rpc-result — it constructs no Update and declares no Update-typed field, "
            .'so it carries none — yet it placed rows in the UPDATE fact log. Those rows are in an update '
            .'fact log but hold no update; the fact belongs to the state / difference tables that do model '
            .'it. An endpoint owing nothing must place nothing AT ALL, not a mixture.',
        );

        self::assertFalse(
            $this->hasEndpointFactRow($accountId, $ctor),
            "{$ctor} is classified rpc-result yet a tf_updates row names it. That row is a fact that did "
            .'not happen.',
        );
    }
}
