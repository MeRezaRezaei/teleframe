# Task 5 — Residue reconcile verdict

**Lane:** `RESIDUE-RECONCILE` (child L1) · **Parent:** `teleframe-L0-v2resume` (L0)
**First written:** 2026-10-05T08:53Z · **RE-VERIFIED AND CORRECTED:** 2026-10-05T11:31Z
**Plan:** `.brain/superpowers/plans/2026-10-05-endpoint-coverage-and-mysql-truth-v2.md` Task 5

> **READ THIS BEFORE THE OLD CONTENT.** This document was written twice. The first pass
> (08:53Z) reasoned correctly about its own measurements but was written against a tree
> that has since moved a long way: the dial went 42 -> 52, ten FK families landed, and
> the endpoint-fixture backlog closed 171 -> 0. Several of its numbers are now WRONG and
> one of its conclusions is now DANGEROUS. §5 lists exactly which claims did not survive.
> Do not inherit this document; re-measure it.

Every number below was measured by this lane, with the command, in the block that
publishes it. Throwaway scripts live in `/tmp`, never in the repo.

---

## 0. THE HEADLINE, IN ONE TABLE

Nothing in the residue is reverted. Three files carried wrong numbers or a dead
conclusion and were corrected rather than discarded, because the defect was the text,
not the file. One file is genuine scratch and must be deleted.

| Path | Verdict | One line of evidence |
| --- | --- | --- |
| `src/Schema/Mirror/MirrorTableResolver.php` | **KEEP** | comment-only; code-token stream byte-identical to `HEAD` |
| `tests/Backfill/CrawlReachesMessageIdOneTest.php` | **KEEP** | trait extraction, all 5 bodies hash-identical to `HEAD` |
| `tests/Backfill/CrawlToMessageIdOneCases.php` | **KEEP** | the de-duplicated assertions; now the single source |
| `tests/Backfill/MultiEndpointHistoryLandsTest.php` | **KEEP** | as above; 4 bodies hash-identical to `HEAD` |
| `tests/Backfill/MultiEndpointHistoryLandsCases.php` | **KEEP** | as above |
| `tests/Schema/Mirror/FabricatedMediaKeyTest.php` | **KEEP** | every published number re-measured and **correct** |
| `tests/Schema/Mirror/RejectedRowCostsOneRowTest.php` | **KEEP + CORRECTED** | 42->52 migrations, 270->269 tables, 36->35 catalogued |
| `tests/Ingest/EveryClassifiedEndpointIngestsTest.php` | **KEEP + CORRECTED** | a "RED ON PURPOSE" block that is now false — the worst item here |
| `tests/MySql/CuratedDialMigratesOnMysqlTest.php` | **KEEP** | fixes a REAL vacuity bug (§4), not prose |
| `tests/MySql/BackfillCrawlReachesMessageIdOneOnMysqlTest.php` | **KEEP WITH FIX** | runs 2 of 5 cases; `fkViolations()` is dead code (§6.1) |
| `tests/MySql/EveryClassifiedEndpointIngestsOnMysqlTest.php` | **KEEP WITH FIX** | one vacuous-loop hole (§6.2) |
| `tests/MySql/ForeignKeysAreEnforcedOnMysqlTest.php` | **KEEP** | asserts `@@SESSION.foreign_key_checks`, restores it in `finally` |
| `tests/MySql/TmpRedProofControlHalfTest.php` | **SCRATCH — DELETE** | §3. RED BY CONSTRUCTION. A mutant, not a gate. |
| `tests/MySql/PolymorphicRelationsResolveThroughTfPeersOnMysqlTest.php` | **KEEP** | strongest MySQL file; validates fixtures against `information_schema` |
| `tests/Schema/Mirror/PolymorphicRelationsResolveThroughTfPeersTest.php` | **KEEP WITH FIX** | 2 vacuity holes (§6.3) |

**One structural fact, stated plainly:** two paths in the earlier brief
(`MirrorFactWriter.php`, and the earlier state of `RejectedRowCostsOneRowTest.php`) were
ALREADY COMMITTED by the time this lane looked — `git cat-file -e HEAD:<path>` succeeds.
They were not revertible by an L1 forbidden from committing. Saying so beats implying a
choice existed.

---

## 1. WHAT THE RESIDUE ACTUALLY DOES — THE TRAIT EXTRACTION

`CrawlReachesMessageIdOneTest` and `MultiEndpointHistoryLandsTest` were ~700 lines each.
Both were split into a `<Name>Cases` trait holding the assertions, plus a thin per-engine
class supplying only the seams (database, FK read).

**The extraction is faithful, and this was proven rather than eyeballed.** Every moved test
body was extracted from `HEAD` and from the trait, comments and whitespace normalized, and
hashed. All nine match byte for byte:

```
test_the_crawl_walks_past_a_gap_to_message_id_one_and_stops
  HEAD  1c3e76ec72655afd 3379      CASES 1c3e76ec72655afd 3379
test_a_mid_crawl_stop_persists_the_cursor_and_the_next_run_resumes_there
  HEAD  8c08dd9922babe5f 3011      CASES 8c08dd9922babe5f 3011
test_a_failure_inside_a_page_leaves_no_cursor_and_the_re_walk_converges
  HEAD  2052167dfda56dfe 2657      CASES 2052167dfda56dfe 2657
test_a_dead_peer_is_terminal_in_the_ledger
  HEAD  ff23d75b3f6ee133 1342      CASES ff23d75b3f6ee133 1342
test_the_first_request_returns_the_newest_page_and_the_crawl_ends_at_one
  HEAD  5f0c7ad2a9afa21b  582      CASES 5f0c7ad2a9afa21b  582
test_a_multi_endpoint_payload_set_lands_with_the_fk_graph_satisfied
  HEAD  0b461f6d0c1d45b6 3734      CASES 0b461f6d0c1d45b6 3734
test_the_stored_message_carries_the_text_its_entities_index_into
  HEAD  ae00e14d564004a9  976      CASES ae00e14d564004a9  976
test_the_engine_refuses_an_orphan_row
  HEAD  f9f768ea5eb2ba67  859      CASES f9f768ea5eb2ba67  859
test_a_history_page_alone_places_no_entities
  HEAD  864b7f9ec2148c2d 1431      CASES 864b7f9ec2148c2d 1431
```

No assertion was added, removed, weakened, or reordered in the move. The
`public function` count in each test class went 5 -> 0 and 4 -> 0, and every one of those
methods now lives in the trait — which is what a correct extraction looks like, and what a
lossy one does not.

---

## 2. THE NUMBERS, RE-MEASURED

The residue's own lesson is that **a comment is not a no-op when it publishes a number**.
Applied to itself: every published number in every residue file was re-measured against the
current tree.

| File | Number it publishes | Was | Is NOW | Verdict |
| --- | --- | --- | --- | --- |
| `RejectedRowCostsOneRowTest` | migration files | 42 | **52** | **wrong -> fixed** |
| `RejectedRowCostsOneRowTest` | tables created | 270 | **269** | **wrong -> fixed** |
| `RejectedRowCostsOneRowTest` | catalogued tables | 36 | **35** | **wrong -> fixed** |
| `RejectedRowCostsOneRowTest` | tables w/ 2nd unique | 6 | 6 | correct |
| `RejectedRowCostsOneRowTest` | non-primary uniques | 7 | 7 | correct |
| `RejectedRowCostsOneRowTest` | the six table names | listed | identical | correct |
| `RejectedRowCostsOneRowTest` | catalog names the 6 | 0 | 0 | correct |
| `FabricatedMediaKeyTest` | `primary([...4 cols])` count | 36 | 36 | correct |
| `FabricatedMediaKeyTest` | `tf_messages` child tables | 29 | 29 | correct |
| `FabricatedMediaKeyTest` | `sources.context` | `[id, peer_id]` | `[id, peer_id]` | correct |
| `MirrorTableResolver` | empty-base entries | 1 (`tf_bot_infos`) | 1 (`tf_bot_infos`) | correct |
| `EveryClassifiedEndpoint...` | provider rows | 175 | 175 | correct |
| `EveryClassifiedEndpoint...` | fixture indices 48/90/121/158 | — | exact match | correct |
| `EveryClassifiedEndpoint...` | **171 failures / "RED ON PURPOSE"** | 171 | **0** | **wrong -> fixed** |
| `EveryClassifiedEndpoint...` | cites `200010:108`, `UpdateIngestor:409` | — | both are PROSE | **wrong -> fixed** |

### The 42 -> 52 story, because it is the whole argument for this section

The first pass of this document "corrected" a published `41` to `42`, reasoning that the
number was never measured. That correction was **right** and is recorded in §5 as a
genuine fix. It is **also already stale**: commit `322f385f` ("move both dial pins 42 ->
52") landed ten FK-family migrations, and the dial now globs **52**.

So the residue's number was wrong twice in one day — once for never being measured, once for
being measured against a tree that then moved under it. **Neither drift can fail a build.**
No test asserts a migration count; the gate *observes* the schema and fails only on a second
unique index or a missing primary key. That is precisely why a stale docblock number is
invisible, and why the only defence is to re-run the measurement rather than inherit the
number. Both facts are now stamped in the file with the command that produced them.

### 270 -> 269 and 36 -> 35

Measured by executing the dial through `ShippedStoreSchema::capsuleFor()` and reading the
built schema — never the migration source:

```
migration files globbed : 52
tables created          : 269   (sqlite_master, excluding sqlite_% internals)
CATALOGUED TABLES       : 35    (array_keys of src/Schema/schema/mirror-catalog.json)
tables w/ non-primary unique : 6      non-primary unique indexes : 7
   tl_user_bindings -> tl_user_bindings_tl_user_id_account_id_unique(tl_user_id,account_id)
   telegram_accounts -> telegram_accounts_app_id_label_unique(app_id,label)
   telegram_accounts -> telegram_accounts_user_id_unique(user_id)
   telegram_apps -> telegram_apps_label_unique(label)
   tf_backfill_cursors -> tf_backfill_cursors_account_id_peer_key_unique(account_id,peer_key)
   tf_store_backup_runs -> tf_store_backup_runs_account_id_object_name_unique(account_id,object_name)
   tg_update_routing -> tg_update_routing_account_id_peer_type_peer_id_unique(account_id,peer_type,peer_id)
of the 6, named by the catalog : 0    (every catalogued name starts with `tf_`)
catalogued tables without a discoverable primary key : 0 of 35
```

The six names and the 6/7 counts are **unchanged** across both passes. The three counts
that moved are the three that describe the dial rather than the catalog, and all three moved
because ten migrations landed.

---

## 3. THE RULING ON `TmpRedProofControlHalfTest.php` — SCRATCH, DELETE IT

**It is scratch. Not a gate, not a rename candidate. Delete it.**

The question was whether a file whose NAME says temporary encodes a real property worth
keeping under a proper name. It does encode a real property — and that property is
**already shipped, inside the other file, and would be duplicated by keeping this one.**

The two files are identical except for **two lines**:

```
$ diff tests/MySql/TmpRedProofControlHalfTest.php tests/MySql/ForeignKeysAreEnforcedOnMysqlTest.php
33c33
< final class TmpRedProofControlHalfTest extends MySqlTestCase
---
> final class ForeignKeysAreEnforcedOnMysqlTest extends MySqlTestCase
60c60
<             $db->statement('SET SESSION foreign_key_checks = 0');
---
>             $db->statement('SET SESSION foreign_key_checks = 1');
```

Line 60 is the `finally` that restores the session after the control half. In the shipping
file it restores enforcement to **1**. In the `Tmp` file it restores it to **0** — leaving
the session permissive for the rest of the test, including the very next statement.

**Therefore `TmpRedProofControlHalfTest::test_an_orphan_row_is_refused_with_mysql_error_1452`
is RED BY CONSTRUCTION.** Its "real half" is the insert at line 76, followed by
`assertNotNull($thrown)` at line 81. With `foreign_key_checks` left at `0`, that insert
**cannot** raise, so `$thrown` stays `null` and the assertion fails. The file is a
deliberately-broken copy, created to demonstrate that the control half in
`ForeignKeysAreEnforcedOnMysqlTest` is load-bearing — that a harness which never enforces
would make the refusal half fail.

That demonstration is **served**. Shipping it would add a permanently-red test to CI.

**The property, named precisely, and where it already lives:**

> *"A negative FK assertion is only evidence if the same session demonstrably ACCEPTS an
> orphan when enforcement is switched off. So run the identical insert twice: once
> permissive (it MUST land), once enforced (it MUST raise MySQL 1452)."*

That is `ForeignKeysAreEnforcedOnMysqlTest::test_an_orphan_row_is_refused_with_mysql_error_1452`,
lines 51–95, **as it already ships**. Keeping the `Tmp` file would duplicate the property
under a second class name in the same namespace while being red. There is nothing to rename
and nothing to preserve.

Three further facts, so the deletion is not a judgement call made on the name:

1. **Nothing in the repository references it.** `grep -rn "TmpRedProof"` across `*.php`,
   `*.md`, `*.json`, `*.xml`, `*.yml`, `*.yaml` returns **zero** hits outside the file.
2. **The shipping sibling is referenced, repeatedly** — in both plan documents and four
   times in `.brain/agent-registry.md`. So this is not the survivor of a rename; it is the
   discarded mutant of a file that won.
3. **It is not a weakened version of anything.** Both files assert identically; the `Tmp`
   copy asserts the *opposite* of the shipping copy on the load-bearing line. Deleting it
   removes a red test and removes zero assertions that pass anywhere.

**What would be lost if this judgement is wrong:** one duplicate of a control-half pattern
that already ships 60 lines away in the same directory, under a name beginning `Tmp`. The
cost of keeping it is a permanently-red CI job. The asymmetry is not close.

**Deleting scratch is not "deleting a test to pass."** Stated so no one misreads this later:
a test is deleted to make a suite pass when its assertions are correct and inconvenient.
This file's assertions are not inconvenient — they are **impossible**, deliberately so, and
it was never a gate for anything. No assertion was weakened, no test was skipped to reach
green, and no `markTestSkipped` exists anywhere in the residue.

**Action for the integrator (NOT done by this lane — `tests/MySql/**` is read-only to me):**

```
git rm tests/MySql/TmpRedProofControlHalfTest.php
```

---

## 4. `CuratedDialMigratesOnMysqlTest.php` IS A REAL FIX, NOT PROSE

Every other tracked diff is comment-only. This one is **load-bearing code**, and it fixes a
genuine vacuity bug — the exact class of defect this reconcile exists to catch.

**The bug, verified by running it, not by reading it:**

```
$ php -r 'var_dump(glob("src/Laravel/Migrations"));'
array(1) { [0]=> string(22) "src/Laravel/Migrations" }     <- the DIRECTORY ITSELF

$ php -r 'echo count(glob("src/Laravel/Migrations/*.php"));'
52

$ php -r 'var_dump(@file_get_contents("src/Laravel/Migrations"));'
string(0) ""                                              <- reading a directory yields ""
```

So the old code, `foreach (glob($path) ?: [] as $file)`, received the **directory itself**
from `UpdateIngestor::migrationPaths()`'s first element. `file_get_contents()` on a
directory returns the empty string, the `preg_match_all` found nothing, `$roots` came back
**empty** — and then every `assertContains` nested beneath it was **vacuously green**. On the
two plain-FILE entity anchors `glob` behaved, so the method silently discovered only those
2 of the dial's migrations.

A gate whose failure mode is "green because it found nothing" is worse than no gate: it
converts a missing-schema regression into a silent pass. The fix resolves each entry **by
shape** (`is_dir($path) ? glob($path.'/*.php') : [$path]`) and de-duplicates by membership —
the same reason `migrationPaths()` itself de-duplicates. **KEEP**, and this is the single
highest-value edit in the residue.

The docblock it added still publishes "2 of the dial's 42 migrations"; the 42 is now 52 (§2).

---

## 5. WAS THE PREVIOUS VERDICT RIGHT, PARTLY RIGHT, OR STALE?

**Partly right, and stale in the places that matter.** Precisely:

**Claims that SURVIVED verification:**

1. `FabricatedMediaKeyTest`'s `31 -> 29` correction. MEASURED: the cited source
   `docs/schema/mirror-relation-graph.md:78` does read "29 measured child tables", and an
   independent count of the `tf_messages` erDiagram entity blocks agrees.
2. `FabricatedMediaKeyTest`'s `sources.context` correction to `[id, peer_id]`. MEASURED in
   the catalog: `tf_message_medias` declares `sources[].context = ["id","peer_id"]`.
3. `FabricatedMediaKeyTest`'s `36` for `primary(['account_id','peer_type','peer_id','id'])`
   occurrences. MEASURED: 36, exactly.
4. `MirrorTableResolver`'s "ONE empty-base entry, and it declares a `key`". MEASURED: 35
   catalog entries, exactly 1 with an empty base, and it is `tf_bot_infos` with `key:
   ["id"]`.
5. The 41 -> 42 correction was a genuine, correct fix at the time it was made.
6. The `MirrorTableResolver.php` change is comment-only — verified by stripping comments and
   hashing the remaining token stream against `HEAD` (identical).

**Claims that did NOT survive verification — each one is now corrected in the file it
touched:**

1. **`"42 migration files executed"` — STALE, now 52.** The correction was right when made
   and wrong hours later. Ten FK-family migrations landed (`322f385f`).
2. **`"270 tables created"` — STALE, now 269.**
3. **`"All 36 catalogued tables"` / `"mirror-catalog.json (36 tables)"` — STALE, now 35.**
4. **`"this gate is RED ON PURPOSE … Failures: 171"` — FALSE, and the most dangerous claim
   in the document.** The backlog closed in `f7b09284`. MEASURED via
   `HasEndpointFixtures::fixtureCoverage()`: `required 175, present 175, missing 0, extra 0,
   groups 11, duplicates 0, skipped 0`, and `EveryEndpointHasAFixtureTest` is green (5 tests,
   6 assertions). Four bounded spot-checks of the 175-row gate pass. The full file exceeds
   a 900 s bound (exit 124, measured three times) so it is **not** claimed green end to end.
5. **`"2 of the dial's 42 migrations"`** in the `CuratedDialMigratesOnMysqlTest` docblock —
   STALE on the 42.
6. **Its §6 "what I did not prove"** listed the MySQL half as unproven. Still unproven —
   no MySQL was run by this lane either, by rule.

**And the structural error of method, which is the real lesson.** The first pass measured the
tree it happened to be standing on and froze the result into prose. Every number it got
wrong is a number that describes **something that moved after it was written** — the dial
count, the table count, the catalog count, the fixture backlog. Not one of them is a number
about a fixed artefact. A docblock that publishes a moving count without re-running the
command is a countdown to being wrong, and nothing in CI will notice. The replacement rule,
now stamped into both files: **publish the measurement and the command, never the
conclusion.**

---

## 6. DEFECTS FOUND BY THE TWO L2 VERIFIERS (read-only audits)

Reported as findings. None were fixed by this lane, because two of them are in
`tests/MySql/**`, which is read-only to me.

### 6.1 `BackfillCrawlReachesMessageIdOneOnMysqlTest` — runs 2 of its 5 cases

`CrawlToMessageIdOneCases.php:262` and `:486` hard-code `$db->select('PRAGMA
foreign_key_check')`, and `:708-715` issues `PRAGMA foreign_keys = OFF/ON`. MySQL has no
`PRAGMA`. The trait's own docblock at `:35` promises an `fkViolations()` seam — and the
MySQL class **does** define `fkViolations()` at `:90`, which nothing ever calls. Dead code,
and its body (`return []`) is exactly the vacuous shape the trait's FK assertion would take
if it were ever wired in.

So the file's claim to run the cases "UNCHANGED" holds for 3 of 5; the two that read the FK
graph directly cannot execute on MySQL. **The fix is a real refactor** (route both PRAGMA
reads through the `fkViolations()` seam the docblock already declares), not a comment.

Also: `:88` cites `MultiEndpointHistoryLandsOnMysqlTest::test_the_engine_refuses_an_orphan_row`
as "the independent proof that enforcement is actually live". **That file does not exist.**

### 6.2 `EveryClassifiedEndpointIngestsOnMysqlTest` — one vacuous loop

`:325-327` — `if (! isset($fixtures[$ctor])) { continue; }` — has **no** `assertNotEmpty(
$fixtures)` in that test method. With an empty fixture set the loop body never runs and both
terminal assertions (`:363`, `:377`) pass vacuously. The "presence gate" the comment at
`:326` points at lives in a **different test method** (`:269-283`), which is cross-test
dependence, not an internal control half.

By contrast this file does several things right: `assertGreaterThan(0, $coverage['required'])`
at `:188`, a real control half at `:408-419`, and an **asserted** `foreign_key_checks`
restore at `:425-430`. Its gap is that hand-typed column names in `seedTenant()` (`:451`) and
the `tf_updates` reads (`:162`, `:223`) are never validated against `information_schema` — so
drift surfaces as a fake "MySQL refusal" rather than a fixture defect.

### 6.3 `PolymorphicRelationsResolveThroughTfPeersTest` — 2 vacuity holes

`:296-305` derives an id-column name by substring surgery on a column name and dereferences
it off `stdClass`. If the derived name is not a real column, `stdClass` yields `null`,
`(int) null === 0`, and the assertion the file itself calls "the load-bearing one" passes
**vacuously**. `phpunit.xml.dist` sets no `failOnWarning`, so the undefined-property warning
would not fail the test. It happens to be correct today by naming convention alone.

`:379-402` — the arm-table loop has no control half; three absent tables produce zero
assertions and a green run. The sibling FK scan at `:196-204` gets this exactly right, with
an explicit `assertGreaterThan(0, $observed)`.

Its MySQL twin is the **strongest** file in the residue: `assertRowCoversRequiredColumns()`
at `:380-422` validates every fixture against `information_schema.COLUMNS`.

### 6.4 The write path — one real hole, in `src/` (NOT mine to touch)

By reading `MirrorFactWriter.php`:

- The rejected-row accounting is **sound**. `:711` calls `placeRow()`, `:713-717` routes a
  non-null return to `$fkClues[]` and `continue`s, and `:719` `$inserted++` sits **after**
  that `continue`. No `inserted++` exists outside the `try`, and none precedes a `continue`.
- **But the uncovered-key test at `:1371` is `array_key_exists($column, $row)`, not a
  non-null test.** A primary-key column present with value `null` takes the direct path,
  `$key[$column] = null`, and reaches `upsert()` at `:1435`. On SQLite a NULL in a PRIMARY
  KEY column is accepted. So the docblock's claim that a row is never stored under a
  fabricated key is **true for a MISSING column and false for a NULL one.**
- `:519` states the honest limit of the counter: "`inserted` COUNTS STATEMENTS PLACED. IT IS
  NOT A COUNT OF ROWS HELD", measured at `:541-542` (`inserted=3`, rows held: 1).
- `parentFirst()` (`:979`) is a genuine deferred pass, not a lucky single order. Its honest
  limit: the blocker test at `:1057` is `($remaining[$parent] ?? 0) > 0`, so a parent with no
  rows **in the batch** is never parked — the child is emitted and refused by the database.
  Ordering fixes ordering *within* a batch; it does not conjure a missing parent.
- `migrationPaths()` is **clean**: `:287` returns `array_merge([$dial], $offDial)`, and
  `isInside()` filters both entity anchors, so `$offDial === []` and **no duplicate path is
  possible.** The FKSQL-F4 bite-probe is defused and recorded in place.

**This is a `src/` finding and this lane is forbidden from touching the write path. It is
reported, not fixed.** It belongs to whoever owns `MirrorFactWriter`.

---

## 7. SUITE RESULTS — REPORTED HONESTLY, SKIPS AS SKIPS

| Command | Result |
| --- | --- |
| `phpunit --no-coverage tests/Backfill` | **OK (46 tests, 234 assertions)**, 34.9 s |
| `phpunit tests/Schema/Mirror/FabricatedMediaKeyTest.php` | **OK (3 tests, 7 assertions)** |
| `phpunit tests/Schema/Mirror/RejectedRowCostsOneRowTest.php` | **OK (3 tests, 14 assertions)** — re-run after my edit |
| `phpunit tests/Ingest/EveryClassifiedEndpointIngestsTest.php` | **EXIT 124** — TIMED OUT at 900 s. Measured twice. Reached 63/177 then 102/177. |
| same, after my edit, 2 non-slow tests | **OK (2 tests, 14 assertions)** |
| `phpunit tests/Ingest/EveryEndpointHasAFixtureTest.php` | **OK (5 tests, 6 assertions)** |
| 4 bounded spot-checks of the 175-row gate | **OK** (1 test, 2 assertions each) |

**EXIT 124 IS A NAMED FAILURE AND IT IS NOT PAPERED OVER.** The 175-row
`test_every_classified_endpoint_reaches_the_fact_log` boots the full dial per row; at the
measured ~14 s/row the whole file needs well over 900 s. **The full file was therefore never
run to completion and is NOT claimed green.** What is claimed: 4 of 175 rows pass, and the
derived completeness gate proving the other 171 are covered is green.

**No skips were observed in any suite this lane ran** — every test that ran, ran. The
MySQL suites under `tests/MySql/**` were **not run at all**, by rule: they are opt-in live
gates requiring `TELEFRAME_MYSQL=1`, and this lane is forbidden to touch MySQL. Their
skip behaviour was verified by reading: `MySqlTestCase::setUp():70` skips the whole class
with the reason "set TELEFRAME_MYSQL=1 … or expose a MySQL 8.4 server". **That is an
honest opt-in gate, not a disguised weakening** — no per-test skip exists in any residue
file.

---

## 8. WHAT WAS NOT PROVEN BY THIS LANE

Findings, not passes.

- **Anything about live MySQL.** No MySQL connection was made. Every claim about MySQL
  behaviour here is read from code.
- **That the 175-row endpoint gate is green end to end.** Exit 124, three times.
- **That the ten FK families produce the constraints they declare.** Reading `up()` proves
  intent; whether SQLite accepts each FK (the parent columns must carry a UNIQUE index) was
  not verified against the executed schema. Note `:94-97` of `2026_10_04_000820` returns
  early when the family root is absent, so a rename yields **zero constraints and no error**.
- **Concurrent-writer convergence under REPEATABLE READ** — the property `upsert()` was
  introduced to close. SQLite cannot show it (no MVCC; a sequential probe deadlocks with
  `database is locked`). Recorded in `.brain/decisions.md` D-002; needs a live MySQL and a
  second writer.
- **Whether the driver honours the conflict target.** MySQL ignores `$uniqueBy` entirely and
  emits `on duplicate key update`, firing on ANY unique key. Not provable from inside this
  repo.
- **The `MirrorFactWriter` NULL-key hole in §6.4 is reported, not fixed** — out of this
  lane's scope.
