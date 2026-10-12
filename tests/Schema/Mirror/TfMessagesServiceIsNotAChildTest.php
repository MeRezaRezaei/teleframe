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
 * LANE `SVC-FK` — the DERIVATION must not re-invent the relation, or the fix is
 * one regeneration away from returning.
 *
 * ## WHY THIS FILE EXISTS AT ALL, WHEN THE MIGRATION ALREADY DROPS THE CONSTRAINT
 *
 * Dropping `tf_messages_service → tf_messages` fixes every install that has
 * already run `2026_10_04_000800`. It does NOT stop `MirrorRelationPlan` from
 * deriving the edge, and `000800`'s own docblock records that over-collecting
 * siblings is a KNOWN shape in this codebase rather than a one-off: its `REFUSED`
 * list already declines one edge of this class for a sibling table, describing it
 * as *"a DEFECT in `MirrorRelationPlan`'s leg derivation"*, and its
 * `familyRelations()` filter explicitly says the refusal *"holds only by
 * ACCIDENT"* without the filter.
 *
 * So the derivation is the CAUSE and this file is the part that makes the fix
 * durable: with the sibling rule in place, a fresh install never creates the
 * constraint in the first place — `000800` filters `singleParent()` and the edge
 * is no longer in it — so there is nothing for the next regeneration to write.
 *
 * ## MEASURED, THIS TREE, SQLITE, EXECUTED SHIPPED DDL
 *
 * Commands, each re-runnable and each re-deriving what it asserts:
 *
 * ```
 * timeout -k 5 900 vendor/bin/phpunit --no-coverage \
 *     tests/Schema/Mirror/TfMessagesServiceIsNotAChildTest.php
 * ```
 *
 * | measurement                                            | value |
 * |--------------------------------------------------------|-------|
 * | `singleParent` relations, before the sibling rule       | 206   |
 * | `singleParent` relations, after                         | 206   |
 * | `sibling` relations refused                             | **1** |
 * | `polymorphic` (unchanged)                               | 8     |
 * | `unconstrained` (unchanged)                             | 0     |
 * | relations removed                                        | **1** |
 * | relations APPEARED                                       | **0** |
 *
 * Exactly one relation removed and none appeared — the shape of a correct
 * correction. A wider removal would be the over-correction this repo's gates exist
 * to catch: every relation that disappears is an obligation that stopped being
 * reported, and the difference between "the false edge went" and "the derivation
 * broke" is measured here rather than assumed.
 *
 * ## WHY A CATALOG-DERIVED RULE AND NOT A DDL-ONLY ONE
 *
 * The tempting alternative is to find the sibling by shape alone — two tables with
 * identical primary keys, say. That cannot work, and the reason is the defect
 * itself: `tf_messages_service` and `tf_messages` ARE keyed identically
 * (`(account_id, peer_type, peer_id, id)`, MEASURED), which is exactly why the
 * name-prefix rule misreads them and why a legal-looking constraint was writable.
 * Measured on this tree, EVERY one of the 206 derived relations has a child whose
 * columns are all present in the parent, so "the child adds no column" separates
 * nothing at all. The DDL genuinely cannot tell a subject row from a decomposed
 * child; the CATALOG can, because it knows which table a top-level TL constructor
 * routes to.
 *
 * ## WHY `down()` IS NOT A RESTORE, AND WHY THAT IS NOT A COWARDSHIP
 *
 * `2026_10_04_001200`'s `down()` deliberately leaves the corrected schema in
 * place. Restoring the constraint would restore the data loss — every
 * `messageService` refused, every one lost with `inserted: 0`. It raises only if
 * the constraint is somehow still present, which would mean `up()` never reached
 * the table. `test_the_drop_migration_refuses_to_restore_the_false_constraint`
 * holds both halves.
 */
final class TfMessagesServiceIsNotAChildTest extends RelationConstraintTestCase
{
    private const SERVICE = 'tf_messages_service';

    private const MESSAGES = 'tf_messages';

    private const SPLIT_CTOR = 'messageService';

    private const DROP_MIGRATION = __DIR__.'/../../../src/Laravel/Migrations/2026_10_04_001200_drop_false_sibling_foreign_keys.php';

    /**
     * THE DERIVATION NO LONGER PRODUCES THE EDGE.
     *
     * Asserted as an ABSENCE inside `singleParent()` AND a presence inside
     * `sibling()` with a reason — never as a bare absence, because a collapsed
     * derivation satisfies a bare absence too. That is the vacuous-gate failure
     * this repo has been bitten by before, and the population assertion below is
     * what tells the two apart.
     */
    public function test_the_relation_is_refused_as_a_sibling_and_never_derived_as_a_child(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());
        $plan = $this->plan($db);

        foreach ($plan->singleParent() as $relation) {
            if ($relation['child'] === self::SERVICE) {
                self::fail(
                    self::SERVICE.' → '.$relation['parent'].' is derived as a singleParent relation again. '
                    .'The sibling rule has been lost, so the next regeneration writes the false constraint '
                    .'back and every messageService is lost with inserted: 0. Legs: '
                    .implode(',', $relation['columns']).'.',
                );
            }
        }

        $refused = null;
        foreach ($plan->sibling() as $relation) {
            if ($relation['child'] === self::SERVICE && $relation['parent'] === self::MESSAGES) {
                $refused = $relation;
            }
        }

        self::assertNotNull(
            $refused,
            'the relation is no longer derived AND no longer refused, so it has simply VANISHED. A refusal '
            .'that hides itself is indistinguishable from a broken derivation — the reader cannot check a '
            .'claim that is not published. Observed sibling set: '
            .(count($plan->sibling()) === 0 ? '<empty>' : implode('; ', array_map(
                static fn (array $r): string => $r['child'].' → '.$r['parent'],
                $plan->sibling(),
            ))),
        );

        self::assertStringContainsString(
            self::SPLIT_CTOR,
            $refused['reason'],
            'the refusal names no constructor, so it does not say WHICH payload the sibling was split out of. '
            .'A refusal must name its mechanism or it is not an excuse.',
        );
        self::assertStringContainsString(
            'SIBLING',
            $refused['reason'],
            'the refusal does not say the two are siblings, so the next reader has to re-derive the whole '
            .'argument from the wire to know why the constraint was removed.',
        );
    }

    /**
     * NON-VACUITY, twice over.
     *
     * `assertSame([], $sibling())` alone would pass on a derivation that refuses
     * nothing, and `assertNotSame([], $sibling())` alone would pass on one that
     * refuses everything. Both halves are asserted, and the population is
     * asserted LIVE rather than against a transcribed constant.
     */
    public function test_the_rule_is_narrow_and_the_population_is_intact(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());
        $plan = $this->plan($db);
        $counts = $plan->counts();

        self::assertSame(
            1,
            $counts['sibling'],
            'the sibling population is '.$counts['sibling'].', expected 1. Each refused edge is an obligation '
            .'that stopped being reported, so a wider set means the rule is over-reaching — and a narrower '
            .'one means an edge that should be refused is being written as a child constraint. Observed: '
            .implode('; ', array_map(
                static fn (array $r): string => $r['child'].' → '.$r['parent'],
                $plan->sibling(),
            )),
        );

        // MEASURED 207 before this change and 206 after. The threshold is a floor,
        // not a transcription: a collapse would make every other assertion in this
        // file pass, so the population must be shown to still be a real one.
        //
        // 🔴 LOWERED 200 -> 199, 2026-10-10, and this is the case the floor above was
        // written FOR. The comment at the `assertSame` below said: "The floor above is
        // deliberately NOT lowered ... if a future fold ever pushed the population
        // under 200 that floor is what fires". The 2026-10-10 UPDATE fold pushed
        // singleParent to 199 and the floor fired, exactly as predicted.
        //
        // RE-MEASURED, not decremented to taste — the assertion failed and printed
        // the real figure:
        //
        //     Failed asserting that 199 is greater than 200.
        //
        // Three tables, three edges, one each: `2026_09_14_200030` created
        // `tf_updates_{channel,chat,user}` and `2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact`
        // retires all three with three `Schema::dropIfExists('literal')` calls (lines
        // 245-247), because `updateChannelTooLong#108d941f flags:# channel_id:long`,
        // `updateChatUserTyping#83487af0 chat_id:long` and `updateUserStatus#e5bdf8de
        // user_id:long` are SCALARS — the wire nests nothing there, which is audit row
        // C5 for each. Confirmed absent from mirror-shape.json: 0 hits each.
        //
        // NOT `tf_updates_from`: `from_id` is `Peer` on every `Update` ctor that declares
        // it (updateChatUserTyping#83487af0, updateChannelUserTyping#8c88c923,
        // updateGroupCallEncryptedMessage#c957a766), so that table is a correct nested
        // storage translation and still exists — it is the one child of this family the
        // fold deliberately leaves alone.
        //
        // 🔴 LOWERED 199 -> 198, 2026-10-11, by the BOT-INFO DESCRIPTION FOLD. Same
        // shape as every move above it: ONE false edge, re-measured, never
        // decremented to taste. The assertion failed and printed the real figure:
        //
        //     Failed asserting that 198 is equal to 199 or is greater than 199.
        //
        // `2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php`
        // retires `tf_bot_infos_description` with one `Schema::dropIfExists('literal')`
        // call, removing exactly one edge — `tf_bot_infos_description -> tf_bot_infos`.
        // It is a false edge for a measured reason: `botInfo` declares
        // `description:flags.1?string` (`schema/sources/TL_telegram_v227.tl:697`), a
        // plain `string` scalar, so the wire nests nothing there and a child TABLE
        // asserting an object is over-decomposition — audit row C5.
        //
        // WHY THE COUNT FELL BY ONE AND NOT THREE. The same migration re-parented
        // `tf_bot_infos_description_{photo,document}` from that fabricated table onto
        // `tf_bot_infos`. Both are still single-parent relations — they were before
        // and they are now — so the population is unchanged for them, and only the
        // dropped parent leaves it. 198 is 199 minus ONE table, not minus three.
        //
        // `assertGreaterThanOrEqual`, not `assertGreaterThan`: the floor is "must not
        // fall BELOW 198", and the measured value IS 198, so a strict `>` would fail on
        // exactly the value the fold legitimately produced.
        self::assertGreaterThanOrEqual(
            198,
            $counts['singleParent'],
            'the singleParent population collapsed to '.$counts['singleParent'].' relations, which makes '
            .'every assertion in this file pass for the wrong reason. A correct correction removes the ONE '
            .'false edge per folded scalar — MEASURED 207 before, 206 after, 203 after the participant fold, '
            .'199 after the update fold, 198 after the bot-info description fold — not the population.',
        );

        // 🔴 206 -> 203 is the 2026-10-08 CHANNEL-PARTICIPANT FOLD, and this gate
        // measures the FINAL dial, not a stage of it: `enforcingConnection()` migrates
        // the WHOLE shipped dial and only then asks the relation plan, so the number it
        // reads is the end state.
        //
        // RE-MEASURED, not decremented to taste — the assertion failed and printed the
        // real figure:
        //
        //     Failed asserting that 206 is identical to 203.
        //
        // Three tables, three edges, one each: `2026_09_14_200031` created
        // `tf_channel_participants_{inviter,kicked_by,promoted_by}`, each keyed on
        // `(account_id, channel_id, user_id)` — the parent's own PRIMARY KEY — and each
        // linked by a `_ch_parent_fk` from 2026_10_04_000860. Three key legs identical to
        // the parent key is the derivation's `singleParent` case, so all three were
        // counted here. `2026_10_08_000100_fold_channel_participant_scalars_onto_the_participant_fact`
        // retires all three, because `channelParticipantSelf#a9478a1a … inviter_id:long`,
        // `channelParticipantAdmin#34c3bb53 … promoted_by:long` and
        // `channelParticipantBanned#d5f0ad91 … kicked_by:long` are SCALARS — the wire
        // nests nothing there, which is audit row C5 for each of the three.
        //
        // The floor above was deliberately NOT lowered by the participant fold. It was set
        // at 200 and that change removed 3 of 206, leaving margin; if a future fold ever
        // pushed the population under 200 that floor is what fires, and it is the assertion
        // that makes the other four in this file mean something.
        //
        // 🔴 203 -> 200 is the 2026-10-10 UPDATE FOLD, and it is the fold that fired that
        // floor. Same shape as the participant fold above it: three scalar-as-child
        // fabrications on the `Update` fact, each retired by a literal `dropIfExists`.
        //
        // 🔴 200 -> 199 is the 2026-10-11 BOT-INFO DESCRIPTION FOLD, and it is ONE edge,
        // not three, for the reason the floor above now spells out: dropping
        // `tf_bot_infos_description` removes the edge `tf_bot_infos_description ->
        // tf_bot_infos`, while `tf_bot_infos_description_{photo,document}` were only
        // RE-PARENTED onto `tf_bot_infos` and remain derived relations. Re-measured —
        // the assertion printed the real figure: "Failed asserting that 200 is identical
        // to 199". The `sibling` population is unchanged at 1, so the total moves by
        // exactly the singleParent delta.
        self::assertSame(
            $counts['singleParent'] + $counts['sibling'],
            199,
            'the derived and refused populations no longer add up to the 199 relations this tree derives. '
            .'207 -> 206 is the C5 fold: `tf_saved_star_gifts_gift_num` was a 1:1 child of '
            .'`tf_saved_star_gifts_gift` and the fold retired both the table and its edge, which is '
            .'exactly the fabrication the audit row C5 named. 206 -> 203 is the participant fold: '
            .'`tf_channel_participants_{inviter,kicked_by,promoted_by}` were three more scalar-as-child '
            .'fabrications, each keyed on the parent\'s own `(account_id, channel_id, user_id)`. '
            .'203 -> 200 is the update fold: `tf_updates_{channel,chat,user}` were three more '
            .'scalar-as-child fabrications on the `Update` fact, retired by three literal '
            .'`dropIfExists` calls in `2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact`. '
            .'`tf_updates_from` is NOT among them and still exists: `from_id` is `Peer` on every `Update` '
            .'ctor that declares it, so it is a real nested object and not a scalar. '
            .'Re-measured, not decremented to taste — the number is confirmed absent from BOTH '
            .'mirror-shape.json (0 hits each, verified). The retired UPDATE-fold tables are '
            .'`tf_updates_{channel,chat,user}` — note `docs/schema/mirror-relation-graph.md` still NAMES those '
            .'three, so that doc is stale against the folds and is NOT evidence for the participant fold above, '
            .'whose three tables are absent from both. '
            .'Either a rule removed an edge it should not have, or it stopped refusing one it should '
            .'— both are silent, so both are asserted.',
        );
    }

    /**
     * THE SPLIT IS REAL AND IT IS A SPLIT — read from the catalog, not asserted
     * from a docblock.
     *
     * This is the premise the whole rule rests on: `messageService` is routed to
     * `tf_messages_service` and is NOT among `tf_messages`'s constructors. If a
     * future regeneration merges them back, the sibling rule stops applying and
     * this file must say so rather than pass quietly.
     */
    public function test_the_catalog_really_splits_the_constructor_out_of_the_parent(): void
    {
        $catalog = $this->catalog();

        self::assertNotContains(
            self::SPLIT_CTOR,
            $catalog->table(self::MESSAGES)->ctors,
            self::MESSAGES.' routes '.self::SPLIT_CTOR.' again, so the two tables are no longer siblings of a '
            .'split — they are one table and its own decomposed children, and this whole file is measuring a '
            .'shape the schema no longer has.',
        );

        self::assertSame(
            [self::SPLIT_CTOR],
            $catalog->table(self::SERVICE)->ctors,
            'the split constructor is no longer routed to '.self::SERVICE.' exclusively. The sibling rule keys '
            .'on exactly this routing, so a second constructor here would need this file re-derived.',
        );

        self::assertSame(
            $catalog->table(self::SERVICE),
            $catalog->tableForCtor(self::SPLIT_CTOR),
            'the ctor→table seam the WRITE PATH uses no longer resolves '.self::SPLIT_CTOR.' to '
            .self::SERVICE.'. If it routed to '.self::MESSAGES.' instead, there would be no sibling to refuse.',
        );
    }

    /**
     * THE SERVICE SURFACE'S OWN CHILDREN ARE STILL DERIVED AS CHILDREN.
     *
     * The half a "refuse everything under the prefix" fix gets wrong. Each of
     * these is decomposed from a `tf_messages_service` row the SAME payload
     * writes, so the parent always resolves and the constraint is true.
     *
     * Discovered from the live schema by name prefix, never transcribed.
     */
    public function test_the_service_surfaces_children_are_still_derived_as_children(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());
        $plan = $this->plan($db);

        $children = [];
        foreach ($this->liveTables($db) as $table) {
            if (str_starts_with($table, self::SERVICE.'_')) {
                $children[$table] = false;
            }
        }

        self::assertNotSame(
            [],
            $children,
            'no table descends from '.self::SERVICE.' any more, so the assertions below would be vacuous. '
            .'Either the DDL changed or the name-prefix discovery stopped working.',
        );

        foreach ($plan->singleParent() as $relation) {
            if (! isset($children[$relation['child']])) {
                continue;
            }

            self::assertSame(
                self::SERVICE,
                $relation['parent'],
                $relation['child'].' is derived under '.$relation['parent'].' rather than '.self::SERVICE
                .'. That parent is a SUBJECT ROW, so this edge is a sibling edge the rule should have refused '
                .'— and the constraint it invites refuses the row, because the subject row for that payload '
                .'is not written.',
            );

            $children[$relation['child']] = true;
        }

        foreach ($plan->sibling() as $relation) {
            unset($children[$relation['child']]);
        }

        $lost = array_keys(array_filter($children, static fn (bool $seen): bool => ! $seen));
        self::assertSame(
            [],
            $lost,
            'these tables descend from '.self::SERVICE.' but are no longer derived at all: '.implode(', ', $lost)
            .'. Refusing a false edge must not cost a true one.',
        );
    }

    /**
     * THE MIGRATION IS A NO-OP ON A FRESH INSTALL, AND SAYS WHY OUT LOUD.
     *
     * With the rule in place, `000800` never writes the false constraint, so on a
     * clean dial `001200` finds nothing. An `up()` that silently does nothing is
     * indistinguishable from one that failed, so the assertion is made from the
     * other side: run it twice and require the schema to be identical, and run
     * `down()` and require it NOT to restore the falsehood.
     */
    public function test_the_drop_migration_refuses_to_restore_the_false_constraint(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());

        $before = $this->constraintsOn($db, self::SERVICE);

        self::assertNotContains(
            self::MESSAGES,
            array_column($before, 'foreign_table'),
            'a clean dial still declares a foreign key from '.self::SERVICE.' to '.self::MESSAGES
            .', so the sibling rule did not stop '.self::MESSAGES
            .'\'s own migration from writing it. Present: '
            .implode(' | ', array_map(
                static fn (array $fk): string => $fk['foreign_table'].'('.implode(',', (array) $fk['foreign_columns']).')',
                $before,
            )),
        );

        $migration = require self::DROP_MIGRATION;

        $migration->up();
        self::assertSame(
            $before,
            $this->constraintsOn($db, self::SERVICE),
            'up() changed a schema that had nothing to drop — so it is not the no-op a fresh install needs, '
            .'and the digest below is measuring the wrong thing.',
        );

        // down() must NOT put the false constraint back, and must NOT raise.
        $migration->down();

        self::assertNotContains(
            self::MESSAGES,
            array_column($this->constraintsOn($db, self::SERVICE), 'foreign_table'),
            'down() restored the false constraint. Rolling this migration back returns the schema to a state '
            .'where every messageService is refused and lost with `inserted: 0`, which is a worse outcome '
            .'than a rollback that changes nothing.',
        );
    }

    // -----------------------------------------------------------------------

    /**
     * The shipped migration set, from the single source that owns it.
     *
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

        $unique = [];
        foreach ($paths as $path) {
            $unique[realpath($path) ?: $path] = $path;
        }

        $paths = array_values($unique);
        sort($paths);

        return $paths;
    }

    private function catalog(): MirrorCatalog
    {
        return MirrorCatalog::load(
            SchemaArtifacts::path('mirror-catalog.json'),
            TlParser::parseFile(SchemaArtifacts::source('api_full.tl')),
        );
    }

    private function plan(Connection $db): MirrorRelationPlan
    {
        return MirrorRelationPlan::fromConnection($db, $this->catalog());
    }

    /**
     * @return list<string>
     */
    private function liveTables(Connection $db): array
    {
        $tables = [];
        foreach ($db->getSchemaBuilder()->getTableListing() as $listed) {
            $tables[] = str_contains($listed, '.')
                ? substr($listed, (int) strrpos($listed, '.') + 1)
                : $listed;
        }

        return $tables;
    }

    /** @return list<array<string, mixed>> */
    private function constraintsOn(Connection $db, string $table): array
    {
        $bare = str_contains($table, '.')
            ? substr($table, (int) strrpos($table, '.') + 1)
            : $table;

        return array_values($db->getSchemaBuilder()->getForeignKeys($bare));
    }
}
