<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;

/**
 * A positioned child's OWN index column is not its parent's key — MEASURED on
 * `tf_messages_reply_markup_rows_buttons`.
 *
 * ## WHY THIS FILE EXISTS — A FALSE POSITIVE IS WORSE THAN SILENCE
 *
 * `docs/superpowers/plans/2026-10-04-fk-family-worklist.md`, in its section
 * "THE 129th RELATION — DEFERRED, AND IT IS A DEFECT IN THE PLAN", records the
 * defect this file closes. `MirrorRelationPlan` derives a child's FK legs by
 * intersecting the PARENT'S KEY with the child's real columns. The markup-rows
 * parent is keyed `(account_id, peer_type, peer_id, id, position)` and the
 * button child carries BOTH `position` and `parent_position`, so the
 * intersection picked the button's `position` — its index WITHIN the row —
 * rather than the row it hangs under.
 *
 * That produced a gate entry reading
 * `tf_messages_reply_markup_rows_buttons(account_id,peer_type,peer_id,id,position) → tf_messages_reply_markup_rows`,
 * i.e. the gate reported a MISSING CONSTRAINT on a relation that
 * `2026_09_14_299999_create_tf_foreign_keys.php` already constrains correctly,
 * as `tf_reply_markup_buttons_row_fk` over
 * `(account_id,peer_type,peer_id,id,parent_position) → (…,position)`. That
 * migration's own comment says the button's `position` "must never be joined
 * here". `2026_10_04_000800_add_tf_messages_family_foreign_keys.php` therefore
 * REFUSED the relation rather than write a second constraint on the wrong
 * column — and a report that is a false positive is worse than silence,
 * because it teaches you to ignore the gate.
 *
 * ## WHAT IS ASSERTED, AND WHY EACH ONE CAN FAIL
 *
 *  - `test_the_legs_prefer_parent_position_over_the_buttons_own_position` — the
 *    derivation itself. Fails while the intersection prefers the local index.
 *  - `test_the_relation_is_no_longer_a_false_positive` — the EFFECT: the derived
 *    legs now match the constraint the dial already ships, so the gate stops
 *    reporting it as missing. This is the assertion the false positive actually
 *    broke.
 *  - `test_the_relation_is_still_classified_and_not_hidden` — the fix must not
 *    silence the relation by hiding it. It stays `singleParent`, and it does not
 *    migrate to `unconstrained` either (which would be a different falsehood:
 *    a KEYING gap, where the real situation is a fully keyed relation).
 *  - `test_the_gate_still_reports_the_relations_that_really_are_missing` — the
 *    NON-VACUITY half. Without it this file would pass if the derivation
 *    collapsed and reported nothing at all, which is the same silent-gate
 *    failure one level up.
 *
 * Every number below is produced by the commands in each method's docblock.
 */
final class MirrorRelationPlanPrefersParentPositionTest extends RelationConstraintTestCase
{
    private const BUTTON_CHILD = 'tf_messages_reply_markup_rows_buttons';

    private const ROW_PARENT = 'tf_messages_reply_markup_rows';

    /**
     * The parent key with the child's local index column replaced by the
     * sibling that names the parent's index. This is the leg set
     * `tf_reply_markup_buttons_row_fk` already ships.
     *
     * @var list<string>
     */
    private const EXPECTED_LEGS = ['account_id', 'peer_type', 'peer_id', 'id', 'parent_position'];

    public function test_the_legs_prefer_parent_position_over_the_buttons_own_position(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());
        $plan = $this->plan($db);

        $relation = $this->relationFor($plan, self::BUTTON_CHILD);

        self::assertSame(
            self::EXPECTED_LEGS,
            $relation['columns'],
            self::BUTTON_CHILD.' → '.self::ROW_PARENT.' is derived with legs '.implode(',', $relation['columns'])
            .', but the parent\'s `position` is the ROW\'s position and this child reaches it through its '
            .'`parent_position` sibling. Its own `position` is only its index WITHIN the row — '
            .'2026_09_14_299999 says in its own comment that the button\'s `position` "must never be joined '
            .'here", and an ordinary one-button-per-row keyboard puts every button at position 0 under rows '
            .'at positions 0,1,2,…, so the `position` leg would refuse legitimate Telegram data. Expected '
            .'legs '.implode(',', self::EXPECTED_LEGS).'.',
        );

        self::assertNotContains(
            'position',
            $relation['columns'],
            self::BUTTON_CHILD.' derived its leg set straight from its own index column. A FOREIGN KEY over '
            .'`position` here is a second constraint from the same child to the same parent on the wrong '
            .'column, and it collides with the shipped `tf_reply_markup_buttons_row_fk`.',
        );
    }

    /**
     * The EFFECT the false positive broke: the derived legs and the shipped
     * constraint now agree, so the relation is not reported as missing.
     *
     * MEASURED (red step, before the fix):
     *
     *   timeout -k 5 300 vendor/bin/phpunit --no-coverage \
     *       --filter test_the_relation_is_no_longer_a_false_positive \
     *       tests/Schema/Mirror/MirrorRelationPlanPrefersParentPositionTest.php
     */
    public function test_the_relation_is_no_longer_a_false_positive(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());
        $plan = $this->plan($db);

        $relation = $this->relationFor($plan, self::BUTTON_CHILD);

        self::assertTrue(
            $this->hasForeignKey($db, self::BUTTON_CHILD, $relation['columns'], self::ROW_PARENT),
            'No constraint on '.self::BUTTON_CHILD.' references '.self::ROW_PARENT.' over the DERIVED legs '
            .implode(',', $relation['columns']).', so the gate still reports this relation as missing. But '
            .'`2026_09_14_299999_create_tf_foreign_keys.php` already ships `tf_reply_markup_buttons_row_fk` over '
            .'`(account_id,peer_type,peer_id,id,parent_position) → (…,position)` — so this entry is a FALSE '
            .'POSITIVE, which is worse than silence because it teaches you to ignore the gate. The derived '
            .'legs must match the shipped constraint.',
        );
    }

    /**
     * The fix must correct the legs, not silence the relation.
     *
     * Hiding it would trade one falsehood for another: reclassifying it as
     * `unconstrained` claims the parent's key does not survive against the
     * child's columns (a KEYING gap), which is not the case here — the child
     * carries every leg.
     */
    public function test_the_relation_is_still_classified_and_not_hidden(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());
        $plan = $this->plan($db);

        foreach ($plan->singleParent() as $relation) {
            if ($relation['child'] === self::BUTTON_CHILD) {
                self::assertSame(self::ROW_PARENT, $relation['parent']);
                self::assertNotSame('', $relation['reason']);

                return;
            }
        }

        self::fail(
            self::BUTTON_CHILD.' is no longer reported as a `singleParent` relation at all. Correcting a leg '
            .'set must not remove a relation from the population: a narrower population is a SMALLER '
            .'obligation, which is the failure mode this gate exists to prevent.',
        );
    }

    /**
     * NON-VACUITY. The corrections above would all pass if the derivation simply
     * reported nothing, so this asserts the population is still a real one.
     *
     * MEASURED, this tree, SQLite, shipped DDL (before the fix):
     *
     *   wc -l /tmp/mrp-before.txt   → 129 single-parent relations carrying no constraint
     *
     * Nine sibling FK-family lanes are landing real migrations concurrently, so
     * the absolute count moves; the requirement is only that it stays NON-ZERO
     * and that this child stays out of it.
     */
    public function test_the_gate_still_reports_the_relations_that_really_are_missing(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());
        $plan = $this->plan($db);

        self::assertGreaterThan(
            100,
            count($plan->singleParent()),
            'The single-parent population collapsed to '.count($plan->singleParent()).' relations. A derivation '
            .'that reports nothing makes every assertion in this file pass, which is the silent-gate failure this '
            .'method exists to catch. MEASURED 207 when re-baselined on 2026-10-05, so a large fall means '
            .'relations are no longer being DERIVED — not that constraints landed.',
        );

        $missing = [];
        foreach ($plan->singleParent() as $relation) {
            if ($this->hasForeignKey($db, $relation['child'], $relation['columns'], $relation['parent'])) {
                continue;
            }

            $missing[] = $relation['child'].'('.implode(',', $relation['columns']).') → '.$relation['parent'];
        }

        // RE-BASELINED 2026-10-05: was assertNotEmpty, now assertSame([]).
        //
        // This half originally required an OUTSTANDING OBLIGATION to exist. Its own
        // failure message anticipated exactly what happened: "Either Task 4.2 landed
        // the last of them on this tree — in which case this file's population is
        // finished — or the derivation stopped reporting obligations." It was the
        // first, and the requirement genuinely changed.
        //
        // But the conclusion is NOT that the check is finished. The original
        // assertion CONFLATED two different things:
        //   1. THE DERIVATION IS ALIVE — singleParent() reports a real population of
        //      mechanically FK-able relations. MEASURED: 207.
        //   2. THERE ARE OUTSTANDING OBLIGATIONS — some of it carries no constraint.
        //      MEASURED: 0.
        // Requiring (2) to be non-zero was a proxy for (1), and a bad one: it made
        // the check pass for the WRONG reason while work remained, and would have
        // made it fail for the RIGHT reason once the work landed. An outstanding
        // obligation is now a defect, so the assertion inverts.
        //
        // assertEmpty($missing) ALONE would be the weakening this file exists to
        // prevent, because a collapsed derivation satisfies it just as well. The
        // population assertion above is what tells the two apart, which is why both
        // halves are kept.
        self::assertSame(
            [],
            $missing,
            'these single-parent relations are mechanically FK-able but carry no constraint: '
            .implode('; ', $missing)
            .'. Every one was constrained by the 2026_10_04 FK-family migrations, so a reappearance here is a '
            .'REGRESSION — a constraint was dropped, or the derivation began inventing obligations the schema '
            .'does not support.',
        );

        foreach ($missing as $entry) {
            self::assertStringStartsNotWith(
                self::BUTTON_CHILD.'(',
                $entry,
                'The false positive is BACK: '.$entry.' is reported as carrying no constraint, but '
                .'`tf_reply_markup_buttons_row_fk` has shipped since 2026_09_14.',
            );
        }
    }

    /**
     * @param  list<array{child:string,parent:string,columns:list<string>,reason:string}>  $relations
     * @return array{child:string,parent:string,columns:list<string>,reason:string}
     */
    private function relationFor(MirrorRelationPlan $plan, string $child): array
    {
        foreach ($plan->singleParent() as $relation) {
            if ($relation['child'] === $child) {
                return $relation;
            }
        }

        foreach ($plan->unconstrained() as $relation) {
            if ($relation['child'] === $child) {
                self::fail(
                    $child.' is classified `unconstrained` — '.$relation['reason'].' The child does carry '
                    .'every leg of the parent key, so this is a KEYING-gap claim that is not true.',
                );
            }
        }

        foreach ($plan->polymorphic() as $relation) {
            if ($relation['child'] === $child) {
                self::fail(
                    $child.' is classified `polymorphic` — '.$relation['reason'].' Its parent is one '
                    .'concrete table and its leg set carries no `{base}_type` discriminator, so this is a '
                    .'misclassification that would hide a real single-parent obligation.',
                );
            }
        }

        self::fail(
            $child.' appears in NO population of the plan (singleParent, polymorphic or unconstrained). '
            .'The parent rule in `reflect()` stops deriving it, so the gate can no longer see it.',
        );
    }

    /**
     * Does a FOREIGN KEY on `$child` reference exactly `$parent` over exactly
     * `$columns`? Order-insensitive on both sides, because SQLite emits
     * `PRAGMA foreign_key_list` rows in reverse key order for a composite
     * constraint. Same predicate as
     * `EveryFkableRelationHasAConstraintTest::hasForeignKey()`, restated here
     * because that file's copy is private to it.
     *
     * @param  list<string>  $columns
     */
    private function hasForeignKey(Connection $db, string $child, array $columns, string $parent): bool
    {
        $want = $columns;
        sort($want);

        $byId = [];
        foreach ($db->select('PRAGMA foreign_key_list('.$child.')') as $row) {
            $row = (array) $row;
            $id = (int) $row['id'];

            $byId[$id]['table'] = (string) $row['table'];
            $byId[$id]['from'][] = (string) $row['from'];
        }

        foreach ($byId as $fk) {
            if ($fk['table'] !== $parent) {
                continue;
            }

            $legs = $fk['from'];
            sort($legs);

            if ($legs === $want) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function shippedMigrations(): array
    {
        $paths = [];
        foreach (UpdateIngestor::migrationPaths() as $path) {
            if (! is_dir($path)) {
                $paths[] = $path;

                continue;
            }

            foreach ((array) glob($path.'/*.php') as $file) {
                $paths[] = (string) $file;
            }
        }

        // `migrationPaths()` is the curated dial plus `entityMigrationPaths()`, and
        // some entity files live INSIDE the dial directory — running one twice
        // raises `table tf_updates already exists`. De-duplicate by real path.
        $unique = [];
        foreach ($paths as $path) {
            $unique[realpath($path) ?: $path] = $path;
        }

        $paths = array_values($unique);
        sort($paths);

        return $paths;
    }

    private function plan(Connection $db): MirrorRelationPlan
    {
        return MirrorRelationPlan::fromConnection(
            $db,
            MirrorCatalog::load(
                SchemaArtifacts::path('mirror-catalog.json'),
                TlParser::parseFile(SchemaArtifacts::source('api_full.tl')),
            ),
        );
    }
}
