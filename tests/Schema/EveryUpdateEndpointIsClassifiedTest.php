<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\EndpointCoverage;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use PHPUnit\Framework\TestCase;

/**
 * EVERY MTProto endpoint is accounted for, or it is not a gate.
 *
 * ## THE DEFECT THIS EXISTS TO STOP
 *
 * Measured 2026-10-04 at `6ff6f1c7`: the layer-227 scheme declares **178**
 * `update*` constructors. **9** are named anywhere in `mirror-catalog.json`,
 * and **19** appear by name in the ingest and schema suites. So ~159 endpoints
 * have no stated fate at all.
 *
 * The absence is silent in the worst way for this package's purpose. A crawl
 * completes, every row it was going to write has been dropped, and nothing in
 * the system reports a gap — which reads exactly like "this account has no
 * reactions" when the truth is "this mirror never looked at that ctor".
 *
 * ## THE DECLARED TOTAL, MEASURED — AND WHY IT IS NOT 178
 *
 * `grep -cE '^update' schema/sources/TL_telegram_v227.tl` prints **178**, and
 * the operator's own brief quoted it. That number is a LINE count, not a
 * constructor count, and the difference is exactly three METHODS that begin with
 * the same prefix:
 *
 * ```
 * updates.getState            updates.getDifference      updates.getChannelDifference
 * ```
 *
 * Measured decomposition (command: the `--check`-style probe reproduced by
 * `bin/generate-endpoint-coverage.php`, universe walked from `TlScheme::types()`):
 *
 * | population | count | what it is |
 * |---|---:|---|
 * | lines matching `^update` in the TL file | 178 | the operator's figure |
 * | — of which METHODS, not constructors | 3 | `updates.getState`, `updates.getDifference`, `updates.getChannelDifference` |
 * | **constructors whose name starts with `update`** | **175** | the honest universe |
 * | — of which namespaced `updates.*` | 8 | ctors of `updates.State`/`Difference`/`ChannelDifference` |
 * | — of which bare `update*` | 167 | incl. all 160 `Update` ctors |
 * | — of which result type `Update` | 160 | the top-level update type |
 *
 * So the declared total this gate asserts is **175**, not 178. The three
 * excluded lines are not constructors and cannot be classified as endpoints;
 * excluding them is not "adding constructors to an exclusion list to make the
 * count agree" — it is correcting a line count into a constructor count. No
 * constructor is excluded anywhere in this gate: the partition is total over
 * every constructor the parsed scheme holds.
 *
 * ## WHAT THIS GATE ASKS, AND WHY IT IS STRONGER THAN "IS IT IN THE CATALOG"
 *
 * A declared edge is necessary and not sufficient — `EveryCataloguedTableIsReachableTest`
 * already established that for tables, and its docblock records the three
 * changes `message.media` needed beyond the edge. So this gate asks the
 * STRONGER question of the REAL artifacts: for every `update*` constructor the
 * layer-227 scheme declares, which of the four kinds is it, and for the
 * `unmodelled` ones, what is the mechanism.
 *
 * A reason must name a mechanism. "We could not think of one" is not a reason.
 * "no catalog entry declares this ctor and no catalog entry declares a nested
 * source under it" IS a reason, because it is checkable and it says exactly
 * where to look when the endpoint is later modelled.
 */
final class EveryUpdateEndpointIsClassifiedTest extends TestCase
{
    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    public function test_every_update_constructor_in_the_scheme_is_classified_exactly_once(): void
    {
        $report = $this->report();

        $declared = $this->declaredUpdateConstructors($this->scheme());
        self::assertNotEmpty($declared, 'no update* constructors parsed — the parse is broken, not the repo');

        $classified = [];
        foreach ($report->endpoints() as $e) {
            self::assertArrayNotHasKey(
                $e['ctor'],
                $classified,
                "{$e['ctor']} is classified twice; the classification must be a partition",
            );
            $classified[$e['ctor']] = $e;
        }

        $unclassified = array_values(array_diff($declared, array_keys($classified)));
        self::assertSame(
            [],
            $unclassified,
            'these update* constructors exist in the layer-227 scheme and are classified by '
            .'nobody: '.implode(', ', $unclassified).'. Each must be given a kind, so that a '
            .'crawl which silently drops it has a stated reason somewhere.',
        );
    }

    public function test_every_unmodelled_endpoint_names_the_mechanism(): void
    {
        $vague = [];
        foreach ($this->report()->endpoints() as $e) {
            if ($e['kind'] !== EndpointCoverage::KIND_UNMODELLED) {
                continue;
            }
            $reason = trim($e['reason']);
            // A reason must name WHERE the gap is, not merely that one exists.
            if ($reason === '' || mb_strlen($reason) < 20 || ! str_contains($reason, 'catalog')) {
                $vague[] = $e['ctor'].': '.$reason;
            }
        }

        self::assertSame(
            [],
            $vague,
            "these unmodelled endpoints carry no checkable reason:\n  ".implode("\n  ", $vague)
            ."\nA reason must say which artifact is missing, so a reader can act on it.",
        );
    }

    public function test_the_classification_covers_every_ctor_the_catalog_names(): void
    {
        // The converse direction: nothing the catalog claims to model may be
        // classified log-only or unmodelled. Without this the gate could pass by
        // under-reporting coverage.
        $byCtor = [];
        foreach ($this->report()->endpoints() as $e) {
            $byCtor[$e['ctor']] = $e;
        }

        // Non-vacuity first. This assertion exists because the per-ctor loop
        // below measured ZERO iterations when this gate was first written: NO
        // catalog entry declared any `update*` constructor in its `ctors` list
        // (the nine `update*` names in mirror-catalog.json were the PARENT
        // ctors of nested-source declarations, not tables' own row types — see
        // the class docblock). A loop that iterates nothing is a gate that
        // cannot fail, so the scan is proved to have run against the real
        // catalog.
        //
        // MEASURED 2026-10-11 on this tree, after the catalog split that gave
        // `tf_updates` its own `ctors`: the loop now iterates 18 constructors,
        // not 0 — the 11 `Update` ctors on `tf_updates`, plus the seven
        // difference-family ctors above. The gate is therefore live, and the
        // assertions in the loop are armed.
        $scannedCtors = 0;
        $catalogUpdateCtors = [];
        foreach ($this->catalog()->tableNames() as $tfName) {
            foreach ($this->catalog()->table($tfName)->ctors as $ctor) {
                $scannedCtors++;
                if (str_starts_with((string) $ctor, 'update')) {
                    $catalogUpdateCtors[(string) $ctor] = $tfName;
                }
            }
        }

        self::assertGreaterThan(
            0,
            $scannedCtors,
            'the catalog scan found no constructors at all, so the converse check below is '
            .'vacuous by fault rather than by measurement',
        );

        // The loop below is the gate; this is the receipt that it is non-empty.
        // Without it the whitelist correction above could silently reduce the
        // loop to zero iterations and the gate would pass without measuring.
        self::assertGreaterThan(
            0,
            count($catalogUpdateCtors),
            'no catalog entry declares an update* constructor, so the loop below iterates nothing '
            .'and this gate cannot fail. Re-derive the catalog scan rather than trusting a green run.',
        );

        foreach ($catalogUpdateCtors as $ctor => $tfName) {

            self::assertArrayHasKey(
                $ctor,
                $byCtor,
                "the catalog names {$ctor} for {$tfName}, so it must be classified — and never as "
                .'log-only or unmodelled',
            );

            // The kind whitelist, CORRECTED 2026-10-11 — and the correction is
            // recorded here because it removed a restriction rather than adding
            // one, which is exactly the kind of edit that must be justified in
            // full.
            //
            // WHAT PREVIOUSLY STOOD HERE. The whitelist was
            // `[KIND_ROOT_FACT, KIND_NESTED_SOURCE]`, and it went red on seven
            // constructors — the whole `updates.Difference` and
            // `updates.ChannelDifference` families:
            //
            //   updates.channelDifferenceEmpty       tf_channel_updates
            //   updates.channelDifferenceTooLong     tf_channel_updates
            //   updates.channelDifference            tf_channel_updates
            //   updates.differenceEmpty              tf_update_differences
            //   updates.difference                   tf_update_differences
            //   updates.differenceSlice              tf_update_differences
            //   updates.differenceTooLong            tf_update_differences
            //
            // WHY IT WENT RED. `EndpointCoverage::build()` decides the two
            // new kinds (2026-10-05, the `update*`-name-prefix over-selection
            // fix) from the constructor's declared RESULT TYPE and runs that
            // branch BEFORE the root-fact branch — deliberately, because a
            // result type is a fact about the wire that no catalog edge can
            // change. None of these seven returns `Update`: `updates.difference`
            // returns `updates.Difference`, `updates.channelDifference`
            // returns `updates.ChannelDifference`. So all seven are classified
            // by shape, and the catalog's declaration of them is never reached.
            //
            // THE PRODUCT DECISION IS CORRECT, AND IT IS NOT BEING REVERTED.
            // `AssertsEndpointInsertObligations` (2026-10-05) turns those two
            // kinds into storage obligations: `update-carrier` must unwrap every
            // member of its `Vector<Update>` and must NOT mint a row naming
            // itself; `rpc-result` must place nothing in `tf_updates` at all. Both
            // are right — `updates.difference` is the reply to
            // `updates.getDifference`, and a `tf_updates` row naming it would
            // record a thing that never happened as though it were an update.
            // Reverting the precedence would re-break that.
            //
            // WHAT WAS ACTUALLY WRONG — THE MECHANISM, NOT THE INTENT. This
            // method's own docblock states its invariant precisely: "nothing the
            // catalog claims to model may be classified log-only or unmodelled.
            // Without this the gate could pass by under-reporting coverage." That
            // sentence names TWO kinds — log-only and unmodelled — and both are
            // the label for "the catalog does not model this endpoint". The
            // `[ROOT_FACT, NESTED_SOURCE]` whitelist asserted something STRICTER
            // and different: it also forbade the two kinds that mean "this IS
            // modelled; here is its more precise fate". So the over-tightening
            // was accidental: it was written when root-fact and nested-source
            // were the only kinds that could mean "modelled", and it was never
            // revisited when two more ways of meaning exactly that were added.
            //
            // The invariant the docblock states is restored below, asserted in
            // its own terms. It is NOT deleted, and it is NOT widened to "any
            // kind": `log-only` and `unmodelled` are still refused by name, and
            // the non-vacuity guard above still proves the loop is real.
            self::assertNotContains(
                $byCtor[$ctor]['kind'],
                [EndpointCoverage::KIND_LOG_ONLY, EndpointCoverage::KIND_UNMODELLED],
                "{$ctor} is declared as {$tfName}'s own row type in the catalog, so it cannot be "
                .'classified as a kind that means "the catalog does not model this" — that would '
                .'under-report coverage and let a modelled endpoint look unmodelled.',
            );
        }
    }

    public function test_the_counts_add_up_to_the_declared_constructor_total(): void
    {
        $counts = $this->report()->counts();
        $sum = array_sum($counts);

        self::assertSame(
            count($this->declaredUpdateConstructors($this->scheme())),
            $sum,
            'the per-kind counts must sum to the declared update* total; otherwise a constructor '
            .'fell out of the partition and the coverage claim is false',
        );

        self::assertArrayHasKey(EndpointCoverage::KIND_ROOT_FACT, $counts);
        self::assertArrayHasKey(EndpointCoverage::KIND_NESTED_SOURCE, $counts);
        self::assertArrayHasKey(EndpointCoverage::KIND_LOG_ONLY, $counts);
        self::assertArrayHasKey(EndpointCoverage::KIND_UNMODELLED, $counts);

        // The two kinds added 2026-10-05 for the RPC RESULT constructors the
        // `update*` name prefix over-selects. They are asserted present AND
        // non-empty, because a key that is always zero is indistinguishable from
        // a partition that quietly lost them — and "lost" is the failure this
        // gate exists to prevent. Measured with
        // `php bin/generate-endpoint-coverage.php`:
        //   update-carrier: 6   rpc-result: 6   root-fact: 0
        //   nested-source: 0    log-only: 0     unmodelled: 163
        //   universe (parsed update* ctors): 175
        self::assertArrayHasKey(EndpointCoverage::KIND_UPDATE_CARRIER, $counts);
        self::assertArrayHasKey(EndpointCoverage::KIND_RPC_RESULT, $counts);
        self::assertGreaterThan(
            0,
            $counts[EndpointCoverage::KIND_UPDATE_CARRIER],
            'no endpoint is classified update-carrier, so the partition cannot be telling an RPC result '
            .'that carries updates from one that does not. The kind exists because `updates`, '
            .'`updatesCombined`, `updates.difference`, `updates.differenceSlice`, '
            .'`updates.channelDifference` and `updateShort` all carry real updates.',
        );
        self::assertGreaterThan(
            0,
            $counts[EndpointCoverage::KIND_RPC_RESULT],
            'no endpoint is classified rpc-result, so the partition cannot be telling an RPC result that '
            .'carries nothing from one that does. `updates.state`, `updatesTooLong` and the four '
            .'`*Empty`/`*TooLong` difference results carry nothing.',
        );
    }

    /**
     * A `update*`-named constructor that does NOT construct an `Update` must be
     * labelled as an RPC result, not filed as an update endpoint.
     *
     * This is the gate for the defect: the universe rule is a NAME rule, so six
     * RPC results — the replies to `updates.getDifference` and
     * `updates.getChannelDifference`, plus a state snapshot — were classified as
     * update endpoints and were held to a `tf_updates` row they can never
     * produce. Three of them were additionally DROPPING the updates they carried.
     *
     * The test is derived from the scheme, not from a name list: any `update*`
     * constructor whose declared result type is not `Update` and which declares
     * no `pts_count` watermark is an RPC result by definition, so a new one is
     * caught the moment the schema pipeline emits it.
     */
    public function test_an_update_prefixed_rpc_result_is_not_classified_as_an_update_endpoint(): void
    {
        $misfiled = [];
        foreach ($this->report()->endpoints() as $e) {
            $ctor = $e['ctor'];
            if (str_starts_with($e['reason'], 'this is an RPC RESULT')) {
                continue; // already labelled by the result type
            }

            $declared = $this->scheme()->types();
            $tlType = null;
            foreach ($declared as $type) {
                if (array_key_exists($ctor, $type->constructors())) {
                    $tlType = $type;

                    break;
                }
            }

            if ($tlType === null) {
                continue; // not reachable from the scheme walk; nothing to claim
            }

            $constructor = $tlType->constructors()[$ctor];
            if ($constructor->resultType === 'Update') {
                continue; // a real update endpoint
            }

            foreach ($constructor->allParams() as $param) {
                if ($param->name === 'pts_count') {
                    continue 2; // a short-poll delivery: an update endpoint in substance
                }
            }

            $misfiled[] = $ctor.' (returns '.$constructor->resultType.')';
        }

        self::assertSame(
            [],
            $misfiled,
            'these constructors are named `update*` but return something other than `Update`, and carry '
            .'no `pts_count` watermark, so they are RPC results rather than update endpoints: '
            .implode(', ', $misfiled).'. Each must be classified update-carrier or rpc-result, so the '
            .'gate that requires a tf_updates row does not demand one it can never produce.',
        );
    }

    /**
     * The universe: every constructor the PARSED scheme holds whose name starts
     * with `update`.
     *
     * Walked from `TlScheme::types()`, because `TlScheme` has no
     * `constructors()` method of its own — its `counts()` sums `constructors()`
     * over every type, and that per-type accessor is keyed by constructor NAME.
     * See the class docblock for the 178-vs-175 decomposition.
     *
     * @return list<string>
     */
    private function declaredUpdateConstructors(TlScheme $scheme): array
    {
        $out = [];
        foreach ($scheme->types() as $type) {
            foreach (array_keys($type->constructors()) as $name) {
                if (str_starts_with((string) $name, 'update')) {
                    $out[] = (string) $name;
                }
            }
        }
        sort($out);

        return array_values(array_unique($out));
    }

    private function scheme(): TlScheme
    {
        return TlParser::parseFile(self::TL);
    }

    private function catalog(): MirrorCatalog
    {
        return MirrorCatalog::load(SchemaArtifacts::catalog(), $this->scheme());
    }

    private function report(): EndpointCoverage
    {
        return EndpointCoverage::classify($this->scheme(), $this->catalog());
    }
}
