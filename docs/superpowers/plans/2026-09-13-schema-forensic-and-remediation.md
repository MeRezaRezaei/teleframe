# Schema forensics: why the schema/migration layer is a mess, and how to fix it

**Date:** 2026-09-13 · **Audience:** repo owner · **Status:** findings locked; remediation pending a scope decision (P1).

This document answers — with git evidence, not vibes — six questions: who built this, why
migrations live in multiple places, why generation is tangled, why the NF5 mirror that was
ordered as the **absolute first step** is not shipped, why non-NF5 schema is still the package
surface, and why the work looks unprofessional.

---

## Verification environment (all reproducible)

| Command | Result |
| --- | --- |
| `git log --format='%an'` | **every** commit authored by `openhands` (no human commits, ever) |
| `git rev-list --left-right --count origin/main...HEAD` | our branch is **26 ahead, 0 behind** origin/main |
| `git merge-base --is-ancestor 3740b395 HEAD` | full-schema-nf5 tip is **an ancestor of our HEAD** (its work IS on our branch) |
| `git merge-base --is-ancestor HEAD origin/main` | our HEAD is **NOT** on origin/main (nothing shipped) |
| `git diff --stat origin/main <tip>` | 261 files → **5,370 changed, 184,871 deletions** on the unmerged `feat/full-schema-nf5` line |
| `php /tmp/nf5-diag.php` (mirror pipeline, catalog→migrations) | TL scheme **632 types / 1,620 ctors / 790 methods**; catalog **37 parents**; output **38 migrations + 202 models**; two runs **byte-identical** (deterministic) |

---

## 1. Who made this?

One agent (`openhands`) over **Sep 7 → Sep 12**, in ~48h of wall-clock schema work, produced
**three mutually incompatible schema designs back-to-back**, each time starting a fresh
pipeline instead of finishing the previous one, and shipped none of the latest two. There is
no human author in the log. "Acting crazy" is the observable pattern:

> **Redesign → partially build → diverge into a parallel effort → leave plan unmerged →
> write a handoff doc → start again.** (repeated 3×)

## 2. Why are migrations in multiple places?

Five live-ish locations, only one of which ships anything:

| Location | Contents | Status |
| --- | --- | --- |
| `src/Laravel/Migrations/` | 16 files: 13 `tf_*` (incl. 228 KB `tf_routes`, ~600 tables) + 3 ops tables | **the only shipped surface** (provider `loadMigrationsFrom` + ship dial) |
| `src/Schema/Generated/migrations/` | 13 `tf_*` regenerator output (`schema-manifest.json` feeds `UpdateIngestor::entityMigrationPaths`) | regenerated full set, no mirror subdir |
| `src/Schema/Generated/migrations/mirror/` | **empty** — never committed | the NF5 output location, never populated |
| `schema/ddl/*.sql` | **empty** — `SqlDdlEmitter` writes here but output was deleted (`22ccafcc`) and never regenerated | dead path inside `SchemaRegenerator` |
| `docs/superpowers/extractions/2026-09-12-family-*.md` | 5 family docs (~694 tables documented in markdown: bots/messages/stars/identity/media) | **documentation only**, never codified into the machine catalog |

## 3. Why is generation tangled?

Two generator pipelines coexist **and both are wired to commands**:

- **Old:** `src/Schema/Generator/MigrationGenerator.php` — emits the `tf_*` domain tables with JSONB `tl_data`; used by `SchemaRegenerator` → `bin/regenerate` / `teleframe:regenerate`.
- **New:** `src/Schema/Mirror/*` (renamed from `Nf5`) — emits the NF5 relational mirror
  (account_id-first, zero-nullable, PK `(account_id, key[, position])`); used by
  `teleframe:mirror` (`TeleframeMirrorCommand`). **Not wired into regenerate/ship.**

The repo's own analysis (`2026-09-12-sql-extraction-phase.md`) calls the OLD schema "more
than wrong": untyped `text` with no length bounds, `bigInteger` for int32 ranges, `tf_routes`
228 KB monolith (~600 tables in one migration), surrogate PK + duplicate unique-scope index
writes, `timestamps()` on append-only logs.

## 4. Why is the NF5 mirror (absolute first step) NOT shipped?

Because every downstream step was left half-done and the agent chased a third design instead:

1. NF5 spec + plan (`2026-09-11-telegram-mirror-nf5-generator.md`) written — **48/48 boxes unchecked**.
2. Generator **built** and merged to main (`97fae511` "Merge branch 'feat/nf5-mirror-generator'", 9 commits) — but **only a 37-parent curated catalog** (`2026-09-11-telegram-mirror-catalog.json`) was committed, ~4% of the surface the extraction docs enumerate.
3. Instead of completing the catalog, the agent **spun 5 parallel agents** producing markdown "family extractions" (~694 tables) on the **unmerged** branch `feat/full-schema-nf5`, plus a third throwaway design (TDLib domain tables `0a1828ad`, TL→SQL extractor `e15c2a00`) — 5,370 files, 184,871 deletions vs main.
4. All of it now sits in our `chore/package-cleanup` branch (26 unpushed commits) — **nothing is on origin/main** except the old tf_* schema.

So today: the mirror pipeline **works and is deterministic** (proven above), but its output is
committed **nowhere**, its catalog is ~4% complete, and its consumers (`SchemaRegenerator`,
ship dial, `UpdateIngestor`, `RouteIdempotency`, golden tests) all still target the old tf_* design.

## 5. Why is non-NF5 schema still the package surface?

- `SchemaRegenerator` → `MigrationGenerator` (old) is the only regeneration path.
- `UpdateIngestor::entityMigrationPaths()` reads `src/Schema/Generated/schema-manifest.json`
  → `src/Laravel/Migrations/*tf_*` tables (`users/chats/messages/…` = `tf_users/tf_chats/tf_messages/…`).
- Ship dial (`SchemaRegenerator::shipMigrations`) and provider `loadMigrationsFrom` only know the tf_* files.
- The mirror has **zero consumers** — it was built, proven in a test harness, and abandoned.

## 6. Why does it look unprofessional?

Mechanism, evidenced in the log: **planning/spec-documentation never converts to committed
machine artifacts.** Handoffs (`2026-09-12-handoff.md`, mid-session "work resumes"), 48
unchecked boxes, 694 tables living as markdown instead of a catalog JSON, three schemas in
48h, an unmerged 5,370-file branch left to rot, and no commit on `origin/main` since the
Sept 11 merge. The repo looks like an agent that keeps re-designing instead of finishing.

---

## Remediation plan

**Goal:** NF5 mirror becomes the single, shipped, Postgres-truth schema; every competing
pipeline/location is retired; the 26 unpushed commits land on `origin/main`.

### P0 — Freeze (this session)
Lock the scope decision below. No further divergent design work.

### P1 — Complete the NF5 catalog (THE fork decision — see bottom)
Extend `2026-09-11-telegram-mirror-catalog.json` from 37 parents to the full TL surface
(~937 tables per the extraction docs), by transcription of the 5 family docs + resolver pass,
**as machine JSON, not markdown**. Verify: full-catalog run is deterministic; NF5 invariants
PASS (`tests/Schema/Mirror`, phpstan 0).

### P2 — Publish the mirror as the shipped surface
- Commit regenerated mirror output: migrations → `src/Schema/Generated/migrations/mirror/`,
  models → `src/Schema/Generated/Models/Mirror/`.
- Ranger `SchemaRegenerator`/`bin/regenerate` to the Mirror pipeline; **delete** the old
  `MigrationGenerator` + tf_* domain manifest path; retire the `schema/ddl` SQL extractor.
- Ship dial → mirror migrations only; `src/Laravel/Migrations/` = single 16-file tf_* set removed.

### P3 — Switch runtime consumers
- `UpdateIngestor::entityMigrationPaths()` → mirror manifest.
- `RouteIdempotency`, `AccountBootstrap`, handler hydration → mirror column schema.
- Update golden tests (`ShipDialGoldenTest`, `RegenerationGoldenTest`, `SchemaRegeneratorTest`,
  `Identity/TestCase`, `Vault/TestCase`) to the new contract.

### P4 — Integrate
`git merge chore/package-cleanup → origin/main`, push, tag. `composer verify` +
`TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg` green before landing.

---

## The fork decision (needs owner)

The full-schema-nf5 lineage is already **inside** our branch (26 commits). The only question is
**what the full mirror catalog should be**:

- **A (recommended):** the 5 family extractions (~694 tables) are the target; transcribe them
  into the catalog JSON, keep resolver + tests, delete the parallel throwaway designs
  (TDLib domain tables, SQL extractor) before merging. Cost: catalog transcription + P2/P3
  switchover — bounded, reviewable, no new dead code.
- **B:** keep the full-schema-nf5 branch's design wholesale (incl. SQL extractor/TDLib) and
  integrate it — resurrects the 5,370-file divergence; highest risk, least new code to write.
- **C:** hard-reset to origin/main, rebuild NF5 catalog from TL directly, no transcription.

Recommendation: **A**. It preserves the deterministic, already-tested mirror pipeline, retires
the mess, and ships within known bounds.