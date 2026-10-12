# Measured per-area wall time — the basis for the CI shard boundaries

**Every boundary in `ci/phpunit-shards.json` comes from this table. A shard
boundary chosen from a guess is a boundary that will be wrong on the next schema
change.**

## How this was measured

One `phpunit` process per `tests/` subdirectory, bounded, each writing its own
JUnit report so a killed run still leaves the areas that finished:

```bash
php -d memory_limit=1G vendor/bin/phpunit --log-junit /tmp/<area>.xml tests/<Area>
```

Areas ran 8-at-a-time on the development box, then `tests/Ingest` — the heaviest
area — was re-measured on its own. **JUnit is written only when the process
exits**, which is why the per-area form is what was used: it yields a partial
answer at every moment instead of an all-or-nothing one at the end.

The per-test times inside those JUnit reports are the weights the boundaries
were actually cut from (see [How the measurements became
boundaries](#how-the-measurements-became-boundaries)).

### What this measurement is NOT

- **It is not a CI runner.** The box carries other work (a ~30 GB `/var/www`
  process, an embedding server, and two other lanes running this same suite), and
  `/` was at 98% full for part of the run. Absolute seconds here are inflated
  and noisy relative to a dedicated `php:8.4-cli` container. They are used for
  **RANKING and PROPORTION**, which is what a boundary needs — not as a
  prediction of CI wall time.
- **It is not a verdict.** The exit codes below are real and are reported
  unedited. They belong to other lanes. Nothing in the sharding suppresses,
  skips or excludes them.

### Three measurements were voided, and that is recorded on purpose

| What | What happened | What was done |
| --- | --- | --- |
| `tests/Ingest` (parallel pass) | exit **137 (SIGKILL)** at 1886s | re-measured alone |
| `tests/MySql` (parallel pass) | exit **137 (SIGKILL)** at 1925s | not needed — not sharded |
| `tests/Ingest` per-file pass | hit the 1800s per-file cap on one file | superseded by the whole-area run |

The first two: running eight PHPUnit processes at `memory_limit=1G` on a box
that already had two other lanes running this same suite exhausted 61 GB of RAM
(kill -9, not a slow test). A 137 is not a measurement, so those seconds were
treated as **floors, not numbers**, and `tests/Ingest` was re-run alone.

The third: `tests/Ingest/EveryClassifiedEndpointIngestsTest.php` alone exceeded
the 1800s per-file cap. That is not a discarded measurement — it is the single
most important fact in this document, and it is why `shard-1` exists as it does.
The whole-area `tests/Ingest` run (2201s, uncapped) superseded it and produced the
authoritative per-file figures.

**The lesson is recorded because it is the shape of the bug this document exists
to prevent: a number that looks like a measurement and is not is worse than no
number, because it will be used to justify a boundary.**

## Measured wall time per area

| Area | seconds | exit | note |
| --- | --- | --- | --- |
| Ingest | 2201 | 2 | measured alone; 500 tests, 2041 assertions |
| Schema | 1758 | 2 | 458 tests, 21513 assertions |
| Console | 689 | 2 | |
| Mirror | 440 | 2 | |
| Bus | 364 | 1 | |
| Handler | 337 | 2 | 186 tests, 388 assertions |
| Laravel | 202 | 2 | 446 tests, 1809 assertions |
| Credentials | 111 | 0 | |
| Backup | 74 | 0 | |
| Core | 68 | 0 | |
| New | 57 | 2 | 3 tests, **0 assertions** |
| Backfill | 51 | 0 | |
| Store | 44 | 0 | |
| Passport | 24 | 0 | |
| BotMap | 9 | 0 | |
| Config | 5 | 0 | |
| Notify / App / Bot / Identity / Control / Live / Daemon / Vault / Realtime / Stage / Teleframe / Standalone / MiniApp / Supervisor / Message | 0.4–2 each | 0 | |
| Auth / Concerns / DataCore | 0.4 | 0 | |

**`tests/MySql` and `tests/Pg` are deliberately not sharded.** They are owned by
the `mysql-tests` and `pg-tests` jobs, which supply a real `mysql:8.4` /
`postgres:17` service and the matching driver. That is measured, not assumed:

- without `TELEFRAME_PG`, the whole `Pg` suite runs **11 tests, 0 assertions, 11
  errors** in 5s;
- without `TELEFRAME_MYSQL`, `tests/MySql` runs **60 tests and 2090 assertions
  with 14 errors and 8 failures** — it does *not* skip cleanly.

So a shard containing either would be red for **environment** reasons, which is
an environment failure wearing the costume of a code failure. They are excluded
through the `owned_elsewhere` list in `ci/phpunit-shards.json`, and that list is
**checked against the owning job's `script`** by
`bin/verify-shard-partition.php` — so if `pg-tests` were ever deleted, or stopped
running `tests/Pg`, the partition proof fails. An omission that outlives its owner
is the failure mode; an omission that is checked against its owner is just a
division of labour.

## The single file that sets the budget

`tests/Ingest/EveryClassifiedEndpointIngestsTest.php` — **1213s across 177
data-provider cases** — is 18% of the whole measured suite on its own. It is the
whole of `shard-1`, and no directory- or file-level boundary can split it
further, because a file is the smallest unit a path-based partition has.

Next heaviest files, for comparison:

| seconds | tests | file |
| --- | --- | --- |
| 1212.8 | 177 | `tests/Ingest/EveryClassifiedEndpointIngestsTest.php` |
| 326.4 | 22 | `tests/Console/BackfillCommandTest.php` |
| 228.0 | 14 | `tests/Mirror/RelationIntegrityTest.php` |
| 207.7 | 12 | `tests/Bus/IngestConsumerTest.php` |
| 194.0 | 20 | `tests/Schema/Mirror/UpdateDifferencesMiscFamilyForeignKeyTest.php` |
| 182.4 | 12 | `tests/Schema/Mirror/TfDialogFiltersFamilyForeignKeyTest.php` |
| 167.0 | 9 | `tests/Handler/ObserveApiTest.php` |

## Total, and the resulting balance

**6646s** of measured test time across the sharded files. Packed into 8 shards by
greedy longest-processing-time over units chosen as coarsely as they can be while
fitting under the per-shard target:

| | seconds |
| --- | --- |
| total measured | 6646 |
| mean per shard | 831 |
| **worst shard** | **1213** (`shard-1`, the one file) |
| best shard | 776 |
| worst ÷ mean | **1.46x** |

A 1.46x spread is the cost of that one file, and it is recorded rather than
hidden by pretending the spread is 1.00x.

## How the measurements became boundaries

The costs are wildly uneven — `Schema` at 1758s is ~4000x `Auth` at 0.4s — so a
naive one-area-per-shard partition would produce one shard that fits the budget
and thirty-five that finish in seconds, while total pipeline wall time is set by
the single worst shard. Boundaries were therefore cut to **balance** the shards,
using measured cost as the weight:

1. Every test file on disk is in exactly one shard, and the union is the whole
   suite — asserted by `bin/verify-shard-partition.php`, not by this document.
   The universe is enumerated **from disk**, never from the measurement reports;
   deriving it from the reports is the exact bug this lane exists to prevent,
   because a file the measurement missed would then be in no shard while the
   partition still looked total.
2. Units are as **coarse as they can be** while fitting under the target: an area
   (`tests/Foo`), else its subdirectories (`tests/Foo/Bar`), else its files.
   `tests/Schema` (1753s of 1758s in `tests/Schema/Mirror`) and `tests/Ingest`
   (2201s) both exceed the target, so both are subdivided.
3. The heaviest unit that cannot be subdivided is left as one shard and becomes
   the stated floor for the budget, rather than being split at random to make the
   numbers look even.

## Red areas — those are not sharding problems

Reported, not hidden, and not touched by this change:

| Area | exit | What the log says |
| --- | --- | --- |
| Ingest | 2 | 500 tests, 2041 assertions, 2 errors, 13 failures, 1 skipped |
| Schema | 2 | 458 tests, 21513 assertions, 3 errors, 6 failures |
| Console | 2 | errors and failures from 65/95 onward |
| Mirror | 2 | errors |
| Handler | 2 | 186 tests, 388 assertions, 1 error (`TeleframeFacadeTest`, FK) |
| Laravel | 2 | 446 tests, 1809 assertions, 6 errors, 1 skipped — `SQLSTATE[23000] … FOREIGN KEY constraint failed` |
| New | 2 | **3 tests, 0 assertions, 3 errors** (`CrossAccountQueryTest`, FK) |
| Bus | 1 | 109 tests, 276 assertions, 4 failures, 6 skipped |
| MySql (not sharded) | 2 | 60 tests, 2090 assertions, 14 errors, 8 failures — no service |
| Pg (not sharded) | 2 | 11 tests, 0 assertions, 11 errors — no service |

Several are the **known pre-existing** defects already recorded in
`docs/agent-registry.md` (`SVC-FK`'s `tf_messages_service` FK, `RPC-UNWRAP`'s
difference family, the `tf_dialog_filters` relation-graph drift). They belong to
the lanes that own them.

The one number that matters most for this lane: **`tests/New` ran 3 tests and made
0 assertions**, and `tests/Pg` ran 11 and made 0. That is the FK-enforcement
pattern this repository has been bitten by twice. It is recorded here so it is
not lost behind a sharding commit, and it is emphatically not this lane's to fix.

## Reproducing this

```bash
# per-area wall time, bounded, incremental
php -d memory_limit=1G vendor/bin/phpunit --log-junit /tmp/Schema.xml tests/Schema

# the partition proof — the assertion, not this document
php bin/verify-shard-partition.php

# what the shards actually ran, counted from their reports
php bin/rollup-shard-reports.php
```

The proof is the authority on whether the partition is total. This file is the
authority on why the boundaries are where they are. When a schema change moves
the costs, re-measure, re-cut, and let the proof confirm the result is still
total.