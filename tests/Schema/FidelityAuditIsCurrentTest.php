<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * THE FIDELITY GATE — the shipped relational schema against Telegram's own data
 * model, with no unexplained divergence.
 *
 * ## WHY THIS TEST EXISTS, AND WHY EVERY OTHER SCHEMA GATE IS NOT A SUBSTITUTE
 *
 * Every schema gate in this repository measures OUR schema against ITSELF.
 * `MirrorRelationPlan` compares tables to tables. `EveryFkableRelationHasAConstraintTest`
 * asks whether a declared relation HAS a constraint, never whether the relation is
 * what `schema/sources/TL_telegram_v227.tl` actually says. The anti-rot gate is
 * green at 0 gaps, and that number says NOTHING about fidelity to Telegram.
 *
 * That blind spot produced three real defects in one session, and none of the
 * three is a database-design opinion — all three are infidelity to the source:
 *
 *   1. `tf_messages_service -> tf_messages`, a foreign key invented by a
 *      NAME-PREFIX match between two tables Telegram stores as SIBLINGS. The
 *      catalog's ctor->table routing states the truth; the heuristic did not
 *      read it.
 *   2. `tf_wallpapers.id` `integer` against `tf_wallpapers_settings.id`
 *      `bigInteger()` — fourteen lines apart in one shipped file, both legs
 *      signed. MySQL refused with 3780 and the whole MySQL track stopped.
 *   3. `updates.difference` / `updates.channelDifference` fetching a
 *      `Vector<Update>` and DISCARDING it, because a hardcoded payload read did
 *      not match the difference family's `other_updates`.
 *
 * A gate that cannot see any of those is not a weak gate; it is measuring the
 * wrong thing. This one reads BOTH sides from their own authority — the parsed
 * TL on one side, the EXECUTED schema on the other — and refuses to call a
 * divergence explained unless a real database-design fact is named for it.
 *
 * ## WHAT MAWS THIS A WITNESS AND NOT A SECOND COPY
 *
 * Like `RelationGraphIsCurrentTest`, this file never calls into the auditor's
 * own tally to decide what the answer is: it runs `bin/audit-fidelity.php` as a
 * subprocess, parses its machine output, and asserts the PROPERTIES that output
 * must have. A tool that returned zeros everywhere would pass assertion 1 and
 * fail assertion 2, because a report with no provenance is not a report.
 *
 * ## WHAT IS ASSERTED
 *
 *  1. THE TOOL RUNS and its counts are internally consistent — every comparison
 *     it claims to have made is accounted for by exactly one verdict, and no
 *     verdict exists outside the three the doctrine allows. "acceptable" and
 *     "minor" are not verdicts here, by construction.
 *  2. EVERY UNEXPLAINED ROW IS ACTIONABLE — a table, a subject, what Telegram
 *     declares, what we do, and the minimal move toward the source. A gap the
 *     reader cannot act on is a gap the tool failed to describe.
 *  3. BOTH EXIT CODES ARE PROVEN, on the same code path and the same engine, in
 *     one run: a scope with no unexplained divergence exits 0, and the SAME
 *     scope with one divergence injected exits 1. Without this pair the exit
 *     code is folklore.
 *  4. KNOWN-TRUE FINDINGS ARE PINNED, and the two column checks are asserted to
 *     be COMPARING rather than merely reporting — see
 *     `test_the_known_true_findings_are_still_reported_and_the_column_checks_still_compare`,
 *     which also records why the two `varchar`-for-numeric-id fixtures it used to
 *     pin were investigated, dismissed, and replaced rather than deleted.
 *  5. THE COMMITTED REPORT IS THE TOOL'S OUTPUT — the machine block inside
 *     `docs/schema/fidelity-audit.md` is compared field by field against a fresh
 *     run, a digest over EVERY finding (including the storage translations
 *     that are not listed in full) catches the finding SET changing, and a
 *     per-finding row check over the rendered markdown catches a listed row's
 *     text being edited by hand. The two cover different things: the digest is
 *     over the tool's findings and cannot see the document, the row check reads
 *     the document and is what makes the digest's blind half safe.
 *
 *     ## WHY THAT ONE IS ENGINE-SCOPED, AND WHY IT WAS STRUCTURALLY RED
 *
 *     Assertion 5 ran the tool with no `--engine`, so it measured SQLITE, while
 *     the committed report has been a MySQL artifact since `ed3ecc6f`. The two
 *     can never agree: Laravel's `SQLiteGrammar` maps `typeInteger()` and
 *     `typeBigInteger()` to the same word, so SQLite cannot judge integer width
 *     or signedness at all — C1 publishes `measurable: false` there, and SQLite
 *     yields FEWER comparisons than MySQL.
 *
 *     RE-MEASURED 2026-10-11 on this tree: SQLite yields 35 where MySQL yields
 *     36. These figures were 49 and 50 when the paragraph above was written
 *     (measured on a pristine `git archive HEAD` checkout); both moved DOWN by
 *     14 when `mirror-catalog.json` corrected the `key` of `tf_updates` and
 *     `tf_channel_updates` to include `position`, which reconciled the resolver
 *     with the shipped `primary(['account_id','seq','position'])` and so removed
 *     14 C7 `STORAGE-TRANSLATION` rows. The STRUCTURAL claim is unchanged and is
 *     what this paragraph exists to record: the gap is exactly ONE row, and it is
 *     the single C3 `DELIBERATE` row on `tf_dialog_filters.column:id` that only
 *     the MySQL engine can judge — DELIBERATE is 8 on SQLite and 9 on MySQL,
 *     every other verdict tally is identical across the two engines. The absolute
 *     numbers are re-measured per regeneration and will move again; the one-row
 *     gap is the invariant.
 *
 *     So the engine is now READ from the report's own Headline table and the run
 *     is made on THAT engine. Where the environment can reach it, the comparison
 *     is unchanged and like-for-like. Where it cannot — the sharded suite has no
 *     MySQL service and no `pdo_mysql` — the test does NOT skip. It asserts the
 *     engine-independent measurement still matches, and that a named CI job owns
 *     the cross-engine `--check`. See `driftIsOwnedByAGate()`.
 *
 *     ## AND THE PROBE THAT FOUND THAT OUT WAS WRONG UNTIL 2026-10-13
 *
 *     `auditOn()` classified "this engine is not reachable from here" by testing
 *     whether the tool had produced NO output — and that probe fails in exactly
 *     the environment the paragraph above is about. `regen-gates.yml` builds no
 *     `pdo_mysql`, so `bootDialect()` throws; but WHERE PHP prints an uncaught
 *     fatal depends on `display_errors`, and on the runner it prints to
 *     **STDOUT**. So `trim($stdout) === ''` was false, the connection-level
 *     branch was never entered, `decode()` was handed a stack trace,
 *     `json_decode` returned null, and the shard died with "Failed asserting that
 *     null is of type array" at :990, called from :858, called from :665. The
 *     failure message carries the captured stdout, which is why the CI log shows
 *     the fatal text immediately above the failure.
 *
 *     The tool emits nothing on stdout before its JSON — `$out`
 *     (bin/audit-fidelity.php:2142) returns early in `json` format, so a
 *     successful run's stdout IS the JSON document and nothing else. That makes
 *     both halves decidable: a run that produced no JSON at all is classified by
 *     its DIAGNOSTICS rather than by the absence of output, and a run that DID
 *     start emitting JSON is never mistaken for an unreachable engine. That is
 *     `classifyRun()`, pinned against the exact bytes the failing job produced by
 *     `test_a_missing_pdo_driver_is_an_unreachable_engine_and_not_a_broken_tool`.
 *
 *     THE ESCALATION, and the reason there is still no `markTestSkipped()`. A
 *     FORCED lane — `TELEFRAME_MYSQL=1`, the flag
 *     `RunsMysqlMigrations::mysqlTrackEnabled()` honours — must never delegate: it
 *     fails with the tool's real stderr, which is the same philosophy as that
 *     method returning `true` BEFORE it opens a socket. An UNFORCED lane that
 *     cannot reach the committed report's engine does not skip either; it answers
 *     through `driftIsOwnedByAGate()`, which returns a verdict rather than
 *     declining to. A skip would delete that verdict from the CI rollup, which is
 *     the whole failure mode this file exists to prevent.
 */
final class FidelityAuditIsCurrentTest extends TestCase
{
    private const ROOT = __DIR__.'/../..';

    private const TOOL = 'bin/audit-fidelity.php';

    private const REPORT = 'docs/schema/fidelity-audit.md';

    /** Verdict vocabulary. Three, and there is no fourth. */
    private const VERDICTS = ['COPYING-GAP', 'STORAGE-TRANSLATION', 'DELIBERATE', 'UNEXPLAINED'];

    /** What a subprocess run turned out to be. Three, and there is no fourth. */
    private const RUN_OK = 'ok';

    private const RUN_UNREACHABLE = 'unreachable';

    private const RUN_BROKEN = 'broken';

    /** What an unreachable engine does next. Forced fails; unforced delegates. */
    private const RUN_ESCALATE = 'escalate';

    private const RUN_DELEGATE = 'delegate';

    /**
     * Memoised audit runs, keyed by the argv that produced them.
     *
     * Keyed rather than one `$full` because the drift comparison runs on the
     * engine the COMMITTED report records, which is not always the engine this
     * process defaults to, and because memoising that probe and that comparison
     * under one key is what keeps the engine contract affordable.
     *
     * @var array<string, array<string, mixed>|null>
     */
    private static array $runs = [];

    public function test_the_auditor_runs_and_every_comparison_carries_exactly_one_verdict(): void
    {
        $report = $this->audit(['--format=json', '--no-write']);

        self::assertArrayHasKey('counts', $report);
        self::assertArrayHasKey('findings', $report);

        $counts = $report['counts'];
        self::assertIsArray($counts);

        // The three allowed verdicts, plus the bucket that means "not yet
        // explained" — which is the ONLY thing that may make the tool exit 1.
        foreach (self::VERDICTS as $verdict) {
            self::assertArrayHasKey($verdict, $counts, "counts must publish a {$verdict} tally");
        }

        $verdicted = 0;
        foreach (self::VERDICTS as $verdict) {
            $verdicted += (int) $counts[$verdict];
        }
        self::assertSame(
            (int) $counts['comparisons'],
            $verdicted,
            'every comparison must be accounted for by exactly one verdict — a comparison that '
            .'is silently dropped is indistinguishable from one that was never looked for',
        );

        foreach ($report['findings'] as $index => $finding) {
            self::assertIsArray($finding, "finding #{$index} must be an object");
            self::assertContains(
                $finding['verdict'] ?? null,
                self::VERDICTS,
                "finding #{$index} carries a verdict outside the three the doctrine allows",
            );
            self::assertNotSame('', (string) ($finding['table'] ?? ''), "finding #{$index} names no table");
        }
    }

    public function test_every_unexplained_divergence_names_the_minimal_move_toward_the_source(): void
    {
        $report = $this->audit(['--format=json', '--no-write']);

        $unexplained = array_values(array_filter(
            $report['findings'],
            static fn (array $f): bool => $f['verdict'] === 'UNEXPLAINED',
        ));

        foreach ($unexplained as $finding) {
            $where = ($finding['table'] ?? '?').' / '.($finding['subject'] ?? '?');
            foreach (['telegram', 'ours', 'minimal_move'] as $field) {
                self::assertNotSame(
                    '',
                    trim((string) ($finding[$field] ?? '')),
                    "an UNEXPLAINED divergence at {$where} does not say what to do about '{$field}' — "
                    .'a gap the reader cannot act on is a gap the tool failed to describe',
                );
            }
        }

        // The tool is allowed to find nothing. It is NOT allowed to find nothing
        // while claiming it looked at nothing either — so the measurement itself
        // must be non-trivial, or this whole gate is theatre.
        self::assertGreaterThan(
            0,
            (int) $report['measured']['columns'],
            'the audit read zero columns — it cannot be measuring anything',
        );
        self::assertGreaterThan(
            0,
            (int) $report['measured']['foreign_keys'],
            'the audit read zero foreign keys — the relation checks cannot have run',
        );
    }

    /**
     * THE PRIMARY KEY MUST ACTUALLY BE READ, ON WHATEVER ENGINE IS RUNNING.
     *
     * This gate exists because of a defect that no assertion in this file could
     * see. `reflectSchema()` read the primary key by looking for an index NAMED
     * `primary`. SQLite never names a PRIMARY KEY index that way on an ordinary
     * rowid table — it names it `sqlite_autoindex_<table>_<n>` and reports
     * `origin='pk'`. Measured on the shipped dial: 260 tables carry a `pk` index
     * and 0 of them are named `primary`, so the key map came back EMPTY for every
     * table in the database.
     *
     * An empty key map is not an error. It is a silent refusal to judge, and that
     * is the worst shape a gate can take: the key check reported 194 tables it had
     * never read a key for, and every one of those rows carried a recommendation
     * to REBUILD the key. The reader saw 194 findings and no hint that not one of
     * them came from a key.
     *
     * So the gate asserts the measurement is non-trivial. A tool that reads no
     * primary keys cannot judge key shape, and must be unable to pass this file.
     */
    public function test_the_primary_key_is_actually_read_on_this_engine(): void
    {
        $report = $this->audit(['--format=json', '--no-write']);

        self::assertArrayHasKey(
            'tables_with_primary_key',
            $report['measured'],
            'the audit does not publish how many primary keys it read, so a run that read NONE is '
                .'indistinguishable from a clean run',
        );

        $tables = (int) $report['measured']['tables'];
        $withKey = (int) $report['measured']['tables_with_primary_key'];

        self::assertGreaterThan(
            0,
            $withKey,
            'the audit read ZERO primary keys. On SQLite a PRIMARY KEY index is named '
                .'`sqlite_autoindex_<table>_<n>` with `origin=\'pk\'`, never `primary` — reading only the '
                .'NAME silently empties the key map, and every key verdict then rests on nothing while '
                .'still being reported.',
        );

        // Not "all of them": a table may legitimately have no key, and a gate that
        // demanded 100% would be asserting a fact about the schema rather than
        // about the reader. The point is that the reader is reading.
        self::assertGreaterThan(
            (int) ($tables / 2),
            $withKey,
            "only {$withKey} of {$tables} tables yielded a primary key, which is not a credible read rate — "
                .'either the engine is being misread or most of the schema genuinely has no key',
        );
    }

    /**
     * NO UNEXPLAINED ROW MAY RECOMMEND A DESTRUCTIVE ACTION.
     *
     * This is the rule that governs the whole audit, and it is stated here as an
     * executable assertion because prose did not stop it happening. The pre-repair
     * run emitted 255 UNEXPLAINED rows whose `minimal_move` read, in full, for
     * every one of them:
     *
     *     "either declare the descent in the catalog, or drop the constraint"
     *
     * Every one of those 255 constraints was a faithful copy of Telegram. The
     * rows were wrong; the recommendation was destructive; and the combination is
     * what turns an audit into a deletion script. An UNEXPLAINED row is a
     * statement that the auditor does not know, so it may report a gap and it may
     * decline to act — but it may not tell a reader to destroy a constraint on
     * the strength of its own ignorance.
     *
     * This is deliberately NOT "no UNEXPLAINED row mentions dropping". A row may
     * legitimately say what would have to become true for a drop to be correct.
     * What it may not do is RECOMMEND one.
     */
    public function test_no_unexplained_row_recommends_a_destructive_action(): void
    {
        $report = $this->audit(['--format=json', '--no-write']);

        $offenders = [];
        foreach ($report['findings'] as $finding) {
            if (($finding['verdict'] ?? '') !== 'UNEXPLAINED') {
                continue;
            }
            $move = strtolower(trim((string) ($finding['minimal_move'] ?? '')));
            // An UNEXPLAINED row must not put the reader in the position of
            // destroying something on the strength of an unexplained gap. Phrases
            // that RECOMMEND removal are the failure; phrases that CONDITION one
            // ("if X is true, then dropping would be correct") are not, and the
            // distinction is why this scans a move list instead of banning a word.
            foreach (['or drop the constraint', 'drop the constraint.', 'delete the constraint'] as $forbidden) {
                if (str_contains($move, $forbidden)) {
                    $offenders[] = ($finding['table'] ?? '?').' / '.($finding['subject'] ?? '?')
                        .' → "'.trim((string) $finding['minimal_move']).'"';
                }
            }
        }

        self::assertSame(
            [],
            $offenders,
            'an UNEXPLAINED row must not recommend a destructive action — an unexplained row is a '
            .'confession of ignorance, and a confession that says "drop it" is a deletion script. '
            .count($offenders)." offending row(s):\n  ".implode("\n  ", $offenders),
        );
    }

    /**
     * THE AUDIT'S OWN BLINDNESS MUST BE PUBLISHED, AND MUST SUM TO THE TOTAL.
     *
     * "255 UNEXPLAINED" was one number for two completely different claims, and a
     * reader cannot tell them apart: one claim is about Telegram, the other is
     * about this tool. Before the repair, all 255 were the second kind — the tool
     * could not see the parent's TL type and reported that blindness as though the
     * specification were silent.
     *
     * The split has to be published AND has to add up, because a split that does
     * not reconcile is worse than no split: it looks like an accounting of the
     * residual while quietly leaving part of it in an unnamed bucket.
     */
    public function test_the_blind_and_read_residual_are_published_and_reconcile(): void
    {
        $report = $this->audit(['--format=json', '--no-write']);

        $coverage = $report['authority_coverage'];
        self::assertArrayHasKey(
            'unexplained_audit_blind',
            $coverage,
            'the audit does not publish how much of its residual is its OWN blindness, so a reader '
                .'cannot distinguish "Telegram does not declare this" from "this tool cannot see"',
        );

        $blind = (int) $coverage['unexplained_audit_blind'];
        $silent = (int) $coverage['unexplained_authorities_read'];
        $total = (int) $report['counts']['UNEXPLAINED'];

        self::assertSame(
            $total,
            $blind + $silent,
            "the residual does not reconcile: UNEXPLAINED={$total} but blind={$blind} + read={$silent}. "
                .'A split that does not add up is an accounting that hides part of the residual.',
        );

        // Reconciliation alone is NOT enough, and proving that was the point of
        // building the negative control for this gate: suppressing the split
        // entirely still reconciles, because every blind row simply gets filed
        // under "read". So the gate must also pin WHICH rows are blind, and the
        // only non-circular way to do that is to require each row to SAY so in
        // its own text. A tool that files a blind row as "read" is then lying in
        // its report, and the report is the thing being audited.
        $saysBlind = 0;
        $saysRead = 0;
        foreach ($report['findings'] as $finding) {
            if (($finding['verdict'] ?? '') !== 'UNEXPLAINED') {
                continue;
            }
            if (str_contains((string) ($finding['telegram'] ?? ''), 'holds NO TL type')) {
                $saysBlind++;
            } else {
                $saysRead++;
            }
        }

        self::assertSame(
            $blind,
            $saysBlind,
            "the audit counts {$blind} rows as audit-blind, but {$saysBlind} of its UNEXPLAINED rows actually "
                .'confess to the blindness. The two must be the same set: a blind row that files itself as '
                .'"the authorities were silent" turns a confession into an accusation, which is the exact '
                .'inversion that produced the 255.',
        );
        self::assertSame(
            $silent,
            $saysRead,
            "the audit counts {$silent} rows as read-and-silent, but {$saysRead} of its UNEXPLAINED rows carry "
                .'no blindness confession. The counts and the rows must agree in BOTH directions, or the split '
                .'is only reconciling by coincidence.',
        );

        // The blind bucket is not allowed to be silently empty, either. If it were
        // ever zero, that would mean the tool believes it can see every parent's
        // TL type — a much stronger claim than anything measured here supports,
        // and one that must be re-earned rather than assumed.
        self::assertIsArray(
            $coverage['blind_parent_tables'],
            'the audit must name the parent tables it is blind to, so the blindness is actionable',
        );
        self::assertSame(
            count($coverage['blind_parent_tables']),
            count(array_unique($coverage['blind_parent_tables'])),
            'blind_parent_tables contains duplicates, so the coverage summary overstates the reach',
        );
    }

    /**
     * KNOWN-TRUE FINDINGS MUST STILL BE REPORTED, AND THE COLUMN CHECKS MUST
     * STILL BE COMPARING SOMETHING.
     *
     * A regression gate for a near-miss that is worth recording. Repairing a
     * silently-disabled rule in `$structural()` made it fire for the first time —
     * and the rule's guard was `! isset($schema['columns'][…]['tl_field'])`, and
     * `tl_field` is NEVER WRITTEN anywhere in the tool. So the guard was vacuously
     * true for every key column of every table with provenance, and the moment the
     * rule started running it blinded C3 to every key column in the database. Both
     * confirmed findings VANISHED on the run that fixed the first defect.
     *
     * That is the general hazard in one line: a rule that has never run cannot
     * have been reviewed, and repairing the defect that was hiding it can expose a
     * second defect underneath rather than a clean rule.
     *
     * ## WHY THE TWO ORIGINAL FIXTURES ARE GONE, AND WHY THIS IS NOT A LOST FINDING
     *
     * This test used to pin exactly two COPYING-GAPs:
     *
     *   tf_stars_transactions_extended_media.id   varchar where the rule judged `int`
     *   tf_stars_transactions_stargift.id         varchar where the rule judged `long`
     *
     * Both fixtures are now DISPROVEN, and they are disproven by the source of
     * record rather than by opinion:
     *
     *   `schema/sources/TL_telegram_v227.tl:1936` — `starsTransaction#13659eb0 … id:string …`
     *
     * The `id` on `tf_stars_transactions` is `string`. BOTH of those child tables are
     * `FK>`/Vector children of `tf_stars_transactions`, so their `id` legs hold that same
     * opaque Telegram transaction id and are foreign-key legs into
     * `tf_stars_transactions(id)`, which is `VARCHAR(255)` because the TL says `string`.
     * The names `int` and `long` came from the CHILDREN'S OWN unions —
     * `messageMediaStory#68cb6283 … id:int` and `starGift#313a9547 … id:long` — which
     * describe a DIFFERENT field that happens to share the name. That is the name test
     * the auditor's own header calls wrong, and it was wrong here.
     *
     * The retypes were investigated and DISMISSED, deliberately:
     *
     *   1. MySQL refuses them outright: `ALTER TABLE tf_stars_transactions_stargift
     *      MODIFY id BIGINT NOT NULL` → `SQLSTATE[HY000] 3780` (referencing and
     *      referenced column incompatible), because the child `id` IS a foreign key
     *      leg. Measured on MySQL 8.4.11.
     *   2. Where a retype would be accepted it would TRUNCATE an opaque transaction id
     *      into an integer, across the twenty-one children of `tf_stars_transactions`
     *      that all carry the identical inherited `id` leg. That is precisely the
     *      infidelity this audit exists to catch, committed by the tool reporting it.
     *   3. The arithmetic is the tell: the old rule judged NINETEEN of those
     *      twenty-one children structural and exactly TWO unstructural, and the two it
     *      singled out were exactly the two whose union coincidentally declares a
     *      field named `id`. A rule separating nineteen identical cases from two on a
     *      name is reading a coincidence.
     *
     * So this is a deliberate NON-ACTION, and the fixtures are replaced rather than
     * deleted: a guard that cannot fail is the failure this test warns about. The
     * replacements below are findings the tool reports TODAY, each verified against
     * the TL, and — critically — one of them is on `tf_stars_transactions_extended_media`
     * itself, so the table whose retypes were dismissed is STILL under observation, on a
     * reason that is actually true.
     *
     * ## WHY PINS ALONE ARE NOT ENOUGH — MEASURED, NOT ASSUMED
     *
     * Each fixture below was chosen against a POISONED copy of the auditor under
     * `/tmp` (the repo's `bin/audit-fidelity.php` is never edited for a test), because
     * a guard that only pins findings cannot see the historical defect at all:
     *
     *   - `$structural()` forced to `return true` — C3/C4 `compared` collapse to 0
     *     (measured 2026-10-11 against a poisoned COPY of the tool under /tmp,
     *     never the repo tool). Re-measured today the run goes
     *     36/0/27/0/9 → 35/0/27/0/8 (comparisons, COPYING-GAP, STORAGE-TRANSLATION,
     *     DELIBERATE, UNEXPLAINED) with `C3 compared=0`, `C4 compared=0`, and it
     *     still exits 1. Note that this bullet previously read "while `counts`
     *     stay at 51/0/41/0/10, UNCHANGED. No findings move." — the load-bearing
     *     claim is the one that is easy to eyeball past, and it is false on the
     *     current tree: poisoning moves the tally and still exits 1 for an
     *     unrelated reason while printing a normal-looking verdict table. A
     *     findings-only pin therefore cannot see the blindness at all, which is
     *     why the non-vacuity assertions on the two column checks below exist.
     *   - the C7 ordinal rule disabled — the pinned C7 rows SURVIVE with the same
     *     table and subject but flip `STORAGE-TRANSLATION` → `UNEXPLAINED`, so the
     *     verdict is pinned alongside the identity.
     *   - `declaredDescent()` forced to always report `declared` — all three pinned C5
     *     rows VANISH and the run exits 0. A blindness that silences a check is
     *     exactly what must fail here.
     */
    public function test_the_known_true_findings_are_still_reported_and_the_column_checks_still_compare(): void
    {
        $report = $this->audit(['--format=json', '--no-write']);

        // ---- 1. THE COLUMN CHECKS MUST BE COMPARING SOMETHING. -------------
        //
        // `COPYING-GAP` is 0 on this tree, which is a real result and not a
        // licence to check nothing. These two counts are what a blinded C3/C4
        // destroys silently: measured, `$structural()` returning true for every
        // column drops both to 0 and leaves every other number in the report
        // untouched, so the run still looks clean and still exits 1 for
        // unrelated reasons.
        $compared = [];
        foreach ($report['checks'] as $check) {
            if (($check['id'] ?? '') === 'C3' || ($check['id'] ?? '') === 'C4') {
                $compared[$check['id']] = (int) ($check['compared'] ?? 0);
            }
        }

        self::assertArrayHasKey('C3', $compared, 'the audit publishes no C3 comparison count, so a C3 that compared nothing is indistinguishable from a C3 that found nothing');
        self::assertArrayHasKey('C4', $compared, 'the audit publishes no C4 comparison count, so a C4 that compared nothing is indistinguishable from a C4 that found nothing');

        foreach ($compared as $id => $n) {
            self::assertGreaterThan(
                0,
                $n,
                "{$id} compared ZERO columns. This is the shape of the defect this gate was written for: "
                    .'a rule that fires for the first time blinded C3 to every key column in the database, the '
                    .'run still reported a clean verdict tally, and the only observable symptom was a count '
                    .'nobody was asserting. A check that compared nothing must be unable to pass this file.',
            );
        }

        // ---- 2. THE KNOWN-TRUE FINDINGS. -----------------------------------
        //
        // Each row is [check, table, subject, verdict, substring of `telegram`].
        // The substring is part of the pin on purpose: it is what makes the pin a
        // claim about TELEGRAM rather than a claim about a row that happens to
        // exist, so a row that survives with drifted reasoning is still a failure.
        $wanted = [
            // C5 — the tool's OWN BLINDNESS, spoken aloud — now RECORDED rather
            // than confessed. TRUE because `storeBucket` does not exist anywhere in
            // the v227 TL (measured: `bucket|vault|backup` = 0 hits across all seven
            // TL sources; no v227 constructor declares a `kind:` field), so there is
            // genuinely no TL type to read. The verdict moved UNEXPLAINED ->
            // DELIBERATE when `2026_10_11` declared it with that measurement, and the
            // pin moved with it deliberately: the row is still REPORTED, still says
            // the same thing about Telegram, and the substring below is unchanged —
            // so this still pins a claim about Telegram, not merely about a row.
            ['C5', 'tf_store_media', 'fk:tf_store_media->tf_store_buckets', 'DELIBERATE', 'holds NO TL type for `tf_store_buckets`'],

            // C5 — read the authority, it stayed silent — likewise now DECLARED.
            // TRUE because `Chat`'s five constructors declare NO field whose type
            // nests another constructor (measured), so the catalog's
            // `tf_channel_participants` child table is an over-decomposition no
            // authority sanctions; the relation is a DOMAIN edge carrying
            // `channelParticipant` membership, not a field on `Chat`. Declared
            // DELIBERATE with the same measurement rather than folded, because
            // narrowing it is forbidden by D-020 alternative A.
            ['C5', 'tf_channel_participants', 'fk:tf_channel_participants->tf_channels', 'DELIBERATE', 'neither authority states this descent'],

            // C7 — the `Vector` → `position` ordinal mapping. TRUE because
            // `message#7600b9d3 … entities:flags.7?Vector<MessageEntity>` (TL:121) is
            // a Vector, a relational store has no array type, and the element index
            // is part of the wire value — so a child table keyed on `position` is
            // correct and the row says so instead of recommending a fix.
            ['C7', 'tf_messages_entities', 'key:account_id+peer_type+peer_id+id+position', 'STORAGE-TRANSLATION', 'the element index becomes the column `position`'],

            // C7 — THE SAME TABLE whose two `id` retypes were investigated and
            // dismissed above. It is still reported, and for a reason that is
            // actually true: `starsTransaction#13659eb0 … extended_media:flags.9?
            // Vector<MessageMedia>` (TL:1936) is a Vector, so the child table needs
            // its ordinal in the key. The dismissed retypes were about the `id` leg
            // and said nothing about this one; keeping the table pinned here is what
            // proves the dismissal did not also silence it.
            ['C7', 'tf_stars_transactions_extended_media', 'key:account_id+id+position', 'STORAGE-TRANSLATION', 'the element index becomes the column `position`'],
        ];

        $seen = [];
        foreach ($report['findings'] as $finding) {
            $seen[$finding['check'].'|'.$finding['table'].'|'.$finding['subject']] = $finding;
        }

        foreach ($wanted as [$check, $table, $subject, $verdict, $because]) {
            $key = $check.'|'.$table.'|'.$subject;

            self::assertArrayHasKey(
                $key,
                $seen,
                "{$check} no longer reports {$table} / {$subject} as {$verdict}. A rule that fires for the "
                    .'first time can swallow findings as easily as it can reveal them, so the known-true '
                    .'findings are pinned here — and this row is currently true: '.$because,
            );

            self::assertSame(
                $verdict,
                (string) $seen[$key]['verdict'],
                "{$table} / {$subject} is still reported by {$check}, but its verdict drifted to "
                    ."'{$seen[$key]['verdict']}'. The verdict is the reader's instruction: a "
                    .'STORAGE-TRANSLATION that reads as UNEXPLAINED (or the reverse) is the tool changing '
                    .'its mind without saying why.',
            );

            self::assertStringContainsString(
                $because,
                (string) $seen[$key]['telegram'],
                "{$table} / {$subject} is reported, but no longer against the authority that makes it true "
                    ."— the reasoning drifted. Expected it to still read: {$because}",
            );
        }
    }

    public function test_a_clean_scope_exits_zero_and_the_same_scope_with_an_injected_divergence_exits_one(): void
    {
        // A check scoped run, so the exit code is about THE CODE PATH and not
        // about whatever the rest of the audit happens to find today.
        $scope = 'C1';

        $clean = $this->runRaw(['--scope='.$scope, '--format=json', '--no-write']);
        $cleanJson = $this->decode($clean['stdout']);
        self::assertSame(
            0,
            $clean['exit'],
            'scope '.$scope.' is expected to be clean on this tree, so it must exit 0 — got '.$clean['exit']
            .': '.trim((string) $cleanJson['counts']['UNEXPLAINED']).' unexplained. If this is a real '
            .'finding rather than a broken test, the scope in this assertion is the thing to change, '
            .'and it must be changed with the reason recorded.',
        );

        // The injection is a COLUMN TYPE, overridden in the model the tool reads
        // back from the engine — the same defect class as the `tf_wallpapers.id`
        // / `tf_wallpapers_settings.id` pair that stopped the MySQL track: a
        // child leg whose integer width disagrees with the parent it joins.
        $injected = $this->runRaw([
            '--scope='.$scope,
            '--inject=type:tf_messages_entities.peer_type:int32',
            '--format=json',
            '--no-write',
        ]);
        $injectedJson = $this->decode($injected['stdout']);

        self::assertSame(
            1,
            $injected['exit'],
            'an injected type divergence must make the tool exit 1 — an auditor that cannot be made '
            .'to fail is not a gate',
        );
        self::assertGreaterThan(
            (int) $cleanJson['counts']['UNEXPLAINED'],
            (int) $injectedJson['counts']['UNEXPLAINED'],
            'the injected divergence must be REPORTED as unexplained, not merely change the exit code',
        );
    }

    public function test_the_committed_report_is_the_tool_output_and_has_not_drifted(): void
    {
        $path = self::ROOT.'/'.self::REPORT;
        self::assertFileExists($path, self::REPORT.' must exist — it is generated by '.self::TOOL);

        $markdown = (string) file_get_contents($path);
        $block = $this->machineBlock($markdown);

        // ── THE ENGINE THE REPORT WAS ACTUALLY BUILT ON ──────────────────────
        //
        // This assertion has been comparing a SQLite run against a MySQL
        // artifact since commit ed3ecc6f, and it could never pass: the tool
        // reads integer WIDTH and SIGNEDNESS from the live engine, and
        // `SQLiteGrammar` maps `typeInteger()` and `typeBigInteger()` to the
        // same word — `integer` — so C1 reports `measurable: false` on SQLite
        // and the run yields 49 comparisons where MySQL yields 50.
        //
        // A gate that fires for a structural reason nobody can see is worse
        // than no gate: shard-8 has been red on it, and the failure reads as
        // "another flaky schema test" rather than as the drift it was written
        // to catch. So the engine is now READ from the report rather than
        // assumed, and the run below is made on THAT engine.
        $engine = $this->reportedEngine($markdown);

        // The engine the committed artifact was built on is the only one this
        // comparison is meaningful on, so that is the one it asks for. If this
        // environment cannot reach it, the tool cannot run at all there, and
        // `driftIsOwnedByAGate()` takes over — see that method for why a
        // silently-passing branch is not acceptable and what replaces it.
        $fresh = $this->auditOn($engine);

        if ($fresh === null) {
            $this->driftIsOwnedByAGate($engine);

            return;
        }

        self::assertSame(
            $engine,
            (string) ($fresh['engine'] ?? ''),
            self::TOOL.' reports the engine it ran on as something other than the `--engine` it was '
            .'given. The comparison below is only like-for-like if the tool actually ran there.',
        );

        // Everything a reader acts on, compared field by field …
        self::assertSame(
            $fresh['counts'],
            $block['counts'],
            self::REPORT.' publishes counts the tool no longer produces on '.$engine.' — regenerate it with `php '
            .self::TOOL.' --engine='.$engine.'`',
        );
        self::assertSame(
            $fresh['measured'],
            $block['measured'],
            self::REPORT.' publishes a measurement the tool no longer produces — regenerate it',
        );

        // … and a digest over EVERY finding, so a finding that is ADDED, DROPPED,
        // or re-verdicted is caught even when it is one of the storage
        // translations that is summarised rather than listed.
        //
        // PRECISE SCOPE — the digest hashes the tool's FINDINGS, not the markdown
        // it renders (`bin/audit-fidelity.php`:2137-2139 sorts `$findings` into
        // `$canonical` and hashes that), so it is blind to a hand-edit of a row's
        // rendered TEXT. What it does catch is any change to the finding SET:
        // adding, dropping or re-verdicting a row all move it. The rendered
        // markdown is covered by the loop below, which reads every verdict — it
        // read UNEXPLAINED only until 2026-10-11, which left every row of a clean
        // tree (where UNEXPLAINED is 0) unguarded.
        self::assertSame(
            $fresh['digest'],
            $block['digest'],
            self::REPORT.' publishes a finding digest the tool no longer produces on '.$engine.'. Every comparison is '
            .'in the digest, not only the listed ones, so a finding cannot be added, dropped or re-verdicted in silence.',
        );

        // … and a row for EVERY finding, in EVERY bucket.
        //
        // The digest above hashes the tool's FINDINGS, not the markdown it
        // renders, so it moves when a finding is added, dropped or re-verdicted
        // and does NOT move when a listed row's rendered text is edited by hand.
        // Measured on this tree, that left every non-UNEXPLAINED row unguarded:
        // a scratch copy of the report with one listed STORAGE-TRANSLATION row's
        // table renamed `tf_photos_sizes` -> `tf_photos_sizes_TAMPERED`, leaving
        // counts, measured and digest untouched, left this test GREEN (10
        // assertions, OK, exit 0). The row loop below is the only thing that
        // reads the rendered markdown, so it must read it for all four verdicts
        // and not only for the one that happens to be non-empty on a clean tree.
        //
        // WHY THE ROW, AND NOT THE WHOLE LINE: the assertion pins the
        // table/subject pair a reader navigates by. That is the exact shape the
        // proven control broke, and it is engine-independent — it needs no
        // rerun of the tool and no digest, so it also holds on the branch where
        // this environment cannot reach MySQL and the comparison is delegated.
        //
        // MEASURED 2026-10-11, the day this was extended: all 36 findings the
        // tool reports (9 DELIBERATE, 27 STORAGE-TRANSLATION, 0 of the other
        // two) have their `| `table` | `subject` |` row present in the
        // committed document — 0 missing — so this fires on drift and not on a
        // clean tree. A future verdict whose rows are summarised rather than
        // listed will fail here loudly, which is the correct direction to fail:
        // the document is then not what the tool renders.
        foreach ($fresh['findings'] as $finding) {
            $row = '| `'.$finding['table'].'` | `'.($finding['subject'] ?? '').'` |';
            self::assertStringContainsString(
                $row,
                $markdown,
                self::REPORT.' does not list the '.$finding['verdict'].' finding the tool reports at '
                .$finding['table'].' — the document is not the tool output',
            );
        }
    }

    /**
     * A MISSING PDO DRIVER IS AN UNREACHABLE ENGINE, NOT A BROKEN TOOL.
     *
     * A regression gate for a green-looking gate. The classification of a failed
     * subprocess used to be "the tool printed nothing", and on the runner that is
     * false: PHP writes an uncaught fatal to STDOUT whenever `display_errors` is
     * on, so a missing `pdo_mysql` produced 409 bytes of stack trace on the pipe
     * the test reads as its report. The empty-output branch was skipped, the
     * stack trace was handed to `json_decode`, and the shard failed with a
     * message about `null` that said nothing about the actual cause — while the
     * honest question ("is this engine reachable here at all?") went unasked.
     *
     * The bytes below are the failing job's, transcribed: the fatal on stdout
     * with an empty stderr, the same fatal on stderr with an empty stdout (how a
     * host with `display_errors=stderr` reports it), and a refused connection.
     * Each must read as UNREACHABLE, so that `driftIsOwnedByAGate()` gets to
     * answer the question it was written to answer.
     *
     * The last three are the other half of the contract and the reason this is
     * not a licence to absorb every failure: a truncated JSON document and a PHP
     * parse error are a broken TOOL, and folding them into "unreachable" would
     * turn a real defect into a silent pass. A run that has STARTED emitting JSON
     * is never unreachable — it got past the connection — so a fatal that lands
     * after the payload is reported stays BROKEN.
     */
    public function test_a_missing_pdo_driver_is_an_unreachable_engine_and_not_a_broken_tool(): void
    {
        $fatal = 'Fatal error: Uncaught PDOException: could not find driver in '
            .'/__w/teleframe/teleframe/bin/audit-fidelity.php:382'.PHP_EOL
            .'Stack trace:'.PHP_EOL
            .'#0 bin/audit-fidelity.php(382): PDO->__construct()'.PHP_EOL
            .'#1 bin/audit-fidelity.php(1032): bootDialect()';

        self::assertSame(
            self::RUN_UNREACHABLE,
            $this->classifyRun(PHP_EOL.$fatal, ''),
            'an uncaught "could not find driver" fatal on STDOUT is the exact failure that took the '
                .'unreachable branch out of reach — PHP prints it on stdout whenever display_errors is on, '
                .'so classifying on the ABSENCE of output cannot see it',
        );
        self::assertSame(
            self::RUN_UNREACHABLE,
            $this->classifyRun('', 'PHP '.$fatal),
            'the same fatal on stderr is the same unreachable engine — the classification must not '
                .'depend on which stream PHP happened to choose',
        );
        self::assertSame(
            self::RUN_UNREACHABLE,
            $this->classifyRun('', 'SQLSTATE[HY000] [2002] Connection refused for user'),
            'a refused connection is an unreachable engine, not a broken tool',
        );

        self::assertSame(
            self::RUN_BROKEN,
            $this->classifyRun('PHP Parse error: syntax error, unexpected token "}"', ''),
            'a parse error is a broken tool, and calling it "unreachable" would turn a real defect into '
                .'a silent pass — which is what this file exists to prevent',
        );
        self::assertSame(
            self::RUN_BROKEN,
            $this->classifyRun('{"counts":', 'Fatal error: Uncaught Error: Call to undefined method Foo()'),
            'a fatal AFTER the payload has started means the engine WAS reached, so the run is broken and '
                .'must not be filed as an unreachable server',
        );
        self::assertSame(
            self::RUN_OK,
            $this->classifyRun('{"counts":{"comparisons":36},"findings":[]}', ''),
            'a complete JSON document on stdout is a run that happened — the tool writes nothing else to '
                .'stdout in json format, so this is the whole of a successful run',
        );

        // The escalation is part of the same contract: a forced lane must surface
        // the real error instead of handing the question to another job, and an
        // unforced one must hand it over rather than skip.
        self::assertSame(
            self::RUN_ESCALATE,
            $this->unreachableDisposition(true),
            'a FORCED run must never delegate its failure — that is the same reason '
                .'RunsMysqlMigrations::mysqlTrackEnabled() returns true on TELEFRAME_MYSQL=1 before it '
                .'opens a socket',
        );
        self::assertSame(
            self::RUN_DELEGATE,
            $this->unreachableDisposition(false),
            'an unforced run must delegate rather than skip: markTestSkipped would report no verdict, so a '
                .'rotting cross-engine gate would look identical to a green one',
        );
    }

    /**
     * THE COMMITTED REPORT MUST NAME ITS OWN ENGINE, and the engine must be one
     * the tool can actually be run on.
     *
     * The machine block carries only `counts`, `measured` and `digest` — none of
     * which records where the numbers came from. That absence is what let a
     * SQLite run and a MySQL artifact be compared silently for a whole commit
     * range: nothing in the artifact said "these numbers are MySQL's", so
     * nothing could notice the run was SQLite's.
     *
     * So the engine is published twice — once in the Headline table the tool
     * writes, and once, from here on, as the argv every drift comparison uses.
     * An unreadable or unknown engine is a hard failure rather than a default:
     * silently falling back to `sqlite` is precisely the defect being repaired.
     *
     * @return non-empty-string
     */
    private function reportedEngine(string $markdown): string
    {
        foreach (explode("\n", $markdown) as $line) {
            if (! str_starts_with(trim($line), '| engine |')) {
                continue;
            }

            $cell = trim(str_replace('|', '', substr(trim($line), strlen('| engine |'))));
            $cell = trim($cell, '` ');

            self::assertContains(
                $cell,
                ['sqlite', 'mysql'],
                self::REPORT.' names an engine the tool cannot be run on (`'.$cell.'`). The tool accepts '
                .'only `sqlite` and `mysql` (bin/audit-fidelity.php, parseOptions), so a report built on '
                .'anything else can never be reproduced and this gate could never pass.',
            );

            return $cell;
        }

        self::fail(
            self::REPORT.' publishes no `| engine |` row in its Headline table. Without it a reader cannot '
            .'tell which engine produced these numbers, and this gate cannot know which engine to '
            .'reproduce them on — which is how a MySQL artifact came to be compared against a SQLite run.',
        );
    }

    // ---------------------------------------------------------------- helpers

    /**
     * WHAT A RUN OF THE TOOL ACTUALLY WAS.
     *
     * The distinction between "unreachable" and "ran and disagreed" is the whole
     * point, and it is also the distinction this file used to get wrong: it was
     * drawn from whether the tool had printed anything, and "printed anything" is
     * a property of PHP's `display_errors`, not of the tool. On a runner with no
     * `pdo_mysql` — `regen-gates.yml` builds `bcmath gmp zip pdo_pgsql sockets
     * intl` and no MySQL service — the uncaught `PDOException` is written to
     * STDOUT, so a run that never reached its engine still "had output", the
     * connection-level branch was skipped, and the stack trace was decoded as if
     * it were the report.
     *
     * So the classifier asks the two questions that are actually decidable:
     *
     *   1. DID IT EMIT THE DOCUMENT? `bin/audit-fidelity.php`'s text writer
     *      (`$out`, :2142) returns immediately in `json` format, so a successful
     *      run's stdout IS the JSON payload and nothing else. JSON parses ⇒ the
     *      tool ran. A run that has STARTED emitting JSON therefore got past the
     *      connection, and no fatal after that point may be filed as an
     *      unreachable server.
     *   2. IF NOT, WHAT DID IT SAY? The diagnostics — BOTH streams, because which
     *      one PHP chose is not ours to decide — are matched against the same
     *      connection-level vocabulary the empty-output branch always used.
     *      Absent a marker from that vocabulary it is a broken tool, and a broken
     *      tool is a hard failure here: that is a defect in this repository, not
     *      a missing server, and delegating it would report a verdict nobody
     *      earned.
     *
     * @see test_a_missing_pdo_driver_is_an_unreachable_engine_and_not_a_broken_tool
     */
    private function classifyRun(string $stdout, string $stderr): string
    {
        if (is_array(json_decode($stdout, true))) {
            return self::RUN_OK;
        }

        $startedEmitting = str_starts_with(ltrim($stdout), '{');

        if (! $startedEmitting && $this->isConnectionFailure(trim($stdout."\n".$stderr))) {
            return self::RUN_UNREACHABLE;
        }

        return self::RUN_BROKEN;
    }

    /**
     * Whether a failed run names a connection the environment could not make,
     * as opposed to a defect in the tool.
     *
     * The vocabulary is deliberately short, unchanged from the branch this
     * replaces, and literal: it names ways of failing to CONNECT and nothing
     * else, so no new failure mode can quietly be reclassified as "the server is
     * not here". These are whole phrases, not patterns, so `str_contains()` is
     * the whole matcher — no regex, which this repository bans outside
     * `src/Laravel`, `src/Schema` and `src/Bot`.
     */
    private function isConnectionFailure(string $diagnostics): bool
    {
        foreach (['could not find driver', 'PDOException', 'Connection refused', 'getaddrinfo', 'Access denied'] as $marker) {
            if (str_contains($diagnostics, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * What an unreachable engine does next.
     *
     * FORCED (`TELEFRAME_MYSQL=1`, the flag
     * `RunsMysqlMigrations::mysqlTrackEnabled()` honours) escalates: an opt-in
     * run that quietly delegates proves nothing, and that method returns `true`
     * on the flag BEFORE it opens a socket for the same reason. UNFORCED
     * delegates, and notably does NOT skip — see `driftIsOwnedByAGate()`.
     */
    private function unreachableDisposition(bool $forced): string
    {
        return $forced ? self::RUN_ESCALATE : self::RUN_DELEGATE;
    }

    private function mysqlLaneIsForced(): bool
    {
        return (string) (getenv('TELEFRAME_MYSQL') ?: '') === '1';
    }

    /**
     * Run the tool on a named engine, or return null when that engine is not
     * reachable from here.
     *
     * Null means "this environment cannot answer the question", and it is
     * returned for exactly one reason: the run failed while CONNECTING (see
     * `classifyRun()`). The caller hands it to `driftIsOwnedByAGate()`, which
     * answers the question this shard cannot. Every other failure is a hard
     * failure, and a FORCED lane never delegates at all — an unreachable server
     * under `TELEFRAME_MYSQL=1` is the real error, surfaced with the tool's own
     * diagnostics, because a run nobody forced must not be allowed to pass
     * silently and a run somebody forced must not be allowed to pretend.
     *
     * @return array<string, mixed>|null
     */
    private function auditOn(string $engine): ?array
    {
        $key = '--engine='.$engine.' --format=json --no-write';

        if (! array_key_exists($key, self::$runs)) {
            $argv = ['--engine='.$engine, '--format=json', '--no-write'];

            $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(self::ROOT.'/'.self::TOOL);
            foreach ($argv as $arg) {
                $command .= ' '.escapeshellarg($arg);
            }

            $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $process = @proc_open($command, $descriptors, $pipes, self::ROOT);
            if (! is_resource($process)) {
                self::fail('could not start '.self::TOOL);
            }

            $stdout = (string) stream_get_contents($pipes[1]);
            $stderr = (string) stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($process);

            $verdict = $this->classifyRun($stdout, $stderr);

            if ($verdict === self::RUN_OK) {
                return self::$runs[$key] = $this->decode($stdout);
            }

            // Both streams, because "PHP wrote it to stdout" is not a fact this
            // file may assume — it is `display_errors`, and the same defect
            // arrived as stdout on one host and as stderr on another.
            $diagnostics = trim($stdout."\n".$stderr);

            if ($verdict === self::RUN_BROKEN) {
                self::fail(
                    self::TOOL.' failed while trying to run on '.$engine.', and the failure was not a '
                    .'connection-level one. This is a broken tool, not a missing server, and treating it as '
                    .'"engine unreachable" would turn a real defect into a silent pass. exit='.$exit
                    .'. output: '.$diagnostics,
                );
            }

            if ($this->unreachableDisposition($this->mysqlLaneIsForced()) === self::RUN_ESCALATE) {
                self::fail(
                    'TELEFRAME_MYSQL=1 forces this lane to measure '.$engine.', and it cannot: '
                    .self::TOOL.' never reached the engine. A forced run that quietly delegates proves '
                    .'nothing, so the real error is surfaced here instead. exit='.$exit
                    .'. output: '.$diagnostics,
                );
            }

            return self::$runs[$key] = null;
        }

        return self::$runs[$key];
    }

    /**
     * WHEN THE COMMITTED ARTIFECT CANNOT BE REPRODUCED HERE, THE COMPARISON MUST
     * BE OWNED BY SOMETHING THAT CAN REPRODUCE IT.
     *
     * This is the branch the sharded suite takes, and it is deliberately NOT a
     * `markTestSkipped()`. A skipped test reports no verdict, so a permanently
     * skipped drift check and a permanently green drift check are
     * indistinguishable in the CI rollup — which is how "255 UNEXPLAINED" and a
     * red shard-8 both went unnoticed. So rather than declining to answer, this
     * asserts two things that are true here and that FAIL LOUDLY if the
     * delegation decays:
     *
     *   1. THE MEASUREMENT IS THE SAME ON BOTH ENGINES. `measured` is a count of
     *      tables, columns, foreign keys and primary keys — dialect-independent
     *      quantities, verified identical on SQLite and MySQL at HEAD. If a
     *      future dialect ever made one of them engine-specific, this assertion
     *      fires instead of quietly comparing unlike things.
     *   2. A NAMED CI GATE OWNS THE CROSS-ENGINE COMPARISON. It must exist, name
     *      this exact engine, and use `--check`, which is the tool's own
     *      byte-for-byte "is the committed document still what I produce" mode.
     *      Deleting or weakening that job turns this test red here, so the
     *      delegation cannot rot unnoticed.
     *
     * What this branch does NOT do is compare `counts` or `digest` across
     * engines — those legitimately differ (49/8 on SQLite vs 50/9 on MySQL,
     * because C1 is `measurable: false` on SQLite). Asserting them equal would
     * be the drift this repair exists to stop.
     */
    private function driftIsOwnedByAGate(string $engine): void
    {
        $path = self::ROOT.'/'.self::REPORT;
        $markdown = (string) file_get_contents($path);
        $block = $this->machineBlock($markdown);

        $fresh = $this->audit(['--format=json', '--no-write']);

        self::assertSame(
            $fresh['measured'],
            $block['measured'],
            'the committed report\'s `measured` block disagrees with a run on THIS engine. `measured` is a '
            .'count of tables, columns, foreign keys and primary keys, and those are dialect-independent '
            .'quantities — so this is real drift in the shipped schema, not an engine difference, and it '
            .'must be fixed rather than delegated.',
        );

        $workflows = glob(self::ROOT.'/.github/workflows/*.yml') ?: [];
        $owner = null;
        foreach ($workflows as $workflow) {
            $body = (string) file_get_contents($workflow);
            if (str_contains($body, self::TOOL.' --engine='.$engine.' --check')) {
                $owner = basename($workflow);

                break;
            }
        }

        self::assertNotNull(
            $owner,
            'the committed report was built on '.$engine.', this environment cannot reach '.$engine
            .', and no CI job owns the comparison. Nothing is checking that '
            .self::REPORT.' is still what '.self::TOOL.' produces — the drift gate is simply absent. '
            .'A workflow must run `php '.self::TOOL.' --engine='.$engine.' --check`, which is the tool\'s '
            .'own byte-for-byte comparison of the committed document against a fresh generation.',
        );
    }

    /**
     * The full run, memoised: it executes 55 migrations and every case in this
     * class needs the same measurement.
     *
     * Keyed by argv rather than held in one slot, because the drift comparison
     * runs on the engine the committed report records while every other case
     * runs on the default. One slot would make whichever ran last the answer to
     * both — the same class of bug, moved one level down.
     *
     * @param  list<string>  $extra
     * @return array<string, mixed>
     */
    private function audit(array $extra): array
    {
        $key = implode(' ', $extra);

        if (! array_key_exists($key, self::$runs)) {
            $raw = $this->runRaw($extra);

            self::$runs[$key] = $this->decode($raw['stdout']);
        }

        return self::$runs[$key];
    }

    /**
     * @param  list<string>  $argv
     * @return array{exit:int, stdout:string, stderr:string}
     */
    private function runRaw(array $argv): array
    {
        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(self::ROOT.'/'.self::TOOL);
        foreach ($argv as $arg) {
            $command .= ' '.escapeshellarg($arg);
        }

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($command, $descriptors, $pipes, self::ROOT);
        if (! is_resource($process)) {
            throw new RuntimeException('could not start '.self::TOOL);
        }

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        self::assertNotSame('', trim($stdout), self::TOOL.' produced no output. stderr: '.$stderr);

        return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $stdout): array
    {
        $decoded = json_decode($stdout, true);

        self::assertIsArray($decoded, self::TOOL.' did not emit JSON. Output was: '.substr($stdout, 0, 2000));

        return $decoded;
    }

    /**
     * The fenced machine block the generated report carries.
     *
     * @return array<string, mixed>
     */
    private function machineBlock(string $markdown): array
    {
        $open = "```json fidelity-audit\n";
        $start = strpos($markdown, $open);
        self::assertNotFalse($start, self::REPORT.' carries no ```json fidelity-audit block');

        $from = $start + strlen($open);
        $end = strpos($markdown, '```', $from);
        self::assertNotFalse($end, self::REPORT.' machine block is never closed');

        $block = json_decode(substr($markdown, $from, $end - $from), true);
        self::assertIsArray($block, self::REPORT.' machine block is not valid JSON');

        return $block;
    }
}
