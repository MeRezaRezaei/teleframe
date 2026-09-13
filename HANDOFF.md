# Handoff — 2026-09-13

State of this clone when it was created. Read this first in any future session.

## Repo identity
- Clone of `github.com/MeRezaRezaei/teleframe`, created 2026-09-13 as the **single canonical** working copy.
- Other clones removed to stop VSCode path collisions: `teleframe-psr3`, `tele/teleframe`, `tele/teleclient`, `teleframe-host` (already gone), `tele/`.
- Branch `chore/package-cleanup`, `HEAD = 22ccafcc`, fully pushed to `origin/chore/package-cleanup`.

## What happened before this clone existed
This repo merged two former projects into one package:
1. `teleproto` → the schema/generated layer: `src/Schema/`, `src/Bot/`, `src/Core/`, `src/Laravel/`, `Migrations`.
2. `teleclient` → the consumer layer, **archived** (`telegram-client.git`, final commit `31c6a335` "superseded by teleframe Phase 2 module merge"). Its modules now live under `MeRezaRezaei\Teleframe\*`:
   - `src/Teleframe/{Backfill,Backup,Bus,Daemon,Handler,Identity,Ingest,Message,Realtime,Repositories,Stage,Vault}`
   - `src/Teleframe/Teleclient.php` (ingest-only face, byte-stable), `src/Teleframe/Teleframe.php` (compose-not-own facade)
   - public bind keys kept fixed: `teleclient.backfill.scope-resolver`, `teleclient.backfill.ingester`, `teleclient.backup.vault-factory` — do not rename.
- Merge commit in history: `ccf2d2d2` "teleclient archive (Phase 2 complete)".

## Two important commits (head of this branch)
- `33f8e7e2` **strip Laravel app scaffold** — this repo is a composer *package*, not a Laravel app. Removed `artisan`, `app/`, `config/`, `routes/`, `database/`, `public/`, `resources/`, `storage/`, `vite.config.js`. `git ls-files` contains zero Laravel app files.
- `22ccafcc` **relocate generated/ under src/Schema/Generated/** — moved `generated/{Models,Data,Factories,migrations,schema-manifest.json}` → `src/Schema/Generated/` byte-identically; removed the now-redundant `Schema\Generated\ → generated/` PSR-4 mapping from `composer.json`; retargeted the regenerator (`bin/regenerate`, `teleframe:regenerate`, `SchemaRegenerator`), ingest paths, and test suites; deleted legacy `schema/ddl/tf_*.sql`; gitignored `.opencode/`, `.openclaude/`, `bootstrap/cache/`.
  - Cost: `phpstan.neon.dist` now `excludePaths: src/Schema/Generated/**` — the 11 invoke-wrapper DTO files emit placeholder `public mixed ${X` params by design (reproducible, pinned), so the generated tree stays out of static analysis, matching the old "generated/ lives outside src-only analysis" contract.

## Gates that must stay green
- `composer verify` = phpunit (1086 tests, 17815 assertions) + phpstan level 5 (paths: `src`, excl. `src/Schema/Generated/**`) + `bin/standalone-smoke.php`.
- Golden determinism: `tests/Schema/RegenerationGoldenTest` pins `src/Schema/Generated/schema-manifest.json` sha256 `430d477cfade0ee230caa2520f7c1ad57ecf6be48191dc3b48f188d50a3dfd44`. A fresh `bin/regenerate` must reproduce bytes; if output changes, bump the pin + committed manifest together.
- PG/Ship golden tests (`RunsPostgresMigrations`, `ShipDialGoldenTest`) are opt-in / temp-dir and need a real Postgres or a regenerated set — not part of default `composer verify`.
- The curated migration dial lives at `src/Laravel/Migrations/` inside the package (13 `tf_*` ship-dial copies + app-owned hand-authored ones); full mirror is `src/Schema/Generated/migrations/`.

## Working-tree hygiene expected
- `.gitignore`: `vendor/`, `.env`, `composer.lock`, `.phpunit.*`, `.superpowers/`, `.openclaude/`, `.opencode/`, `bootstrap/cache/`, `schema/audit-report.md`, `MadelineProto.log`, `packages/*/vendor/`.
- `.env` holds real session credentials — never commit. `composer.lock` and `bootstrap/cache/` are deliberately gitignored (testbench recreates the cache on every phpunit run).
- Do not recreate root-level `generated/`; it was removed (empty leftover).

## Open items / not verified here
- **Root cleanup (chore/package-cleanup):** `migrations/` → `src/Laravel/Migrations/`
  (provider/ingest/route/regenerator/tests retargeted); `.env.example` →
  `examples/.env.example`; `skills/telegram-schema-update` → `docs/skills/`.
  `laravel/framework` stays in `require` (decisive: `src/Teleframe/Stage/StageFormRequest.php`
  extends `Illuminate\Foundation\Http\FormRequest`, which only ships inside the framework).
  Gates green at commit time (phpunit 1086 / phpstan 0 / smoke 33).
- Worktrees from the old `teleframe-psr3` were not carried over (`.openclaude/worktrees/agent-*`, `/tmp/teleframe-head-baseline`, `/tmp/wt-phase1`) — verify they are irrelevant before pruning anything.
- Deep MTProto internals remain future work (see `AGENTS.md` known-gaps); method-level wire coverage for every TL method is the next extension area.

## Run — 2026-09-13 (run 7) — Factory + model writer reconciliation

**Commit:** pending (to be `fix: dedupe mirror writer class names; emit full factory + model subtrees`)

### What was done
- Created `src/Schema/Mirror/MirrorClassName.php` — shared injective, deterministic tf-name → PHP class-name mapping. Greedy ascending order: singular table claims the bare name first; plural falls back to full PascalCase.
- Rewired `MirrorModelWriter` and `MirrorFactoryWriter` to use `MirrorClassName::map()` instead of their private `className()` methods. Both writers now first collect all tfNames from the resolved subtree (deduped walk), compute the map, then write — guaranteeing 400 distinct model paths AND 400 distinct factory paths with class names agreeing between them.
- Added `test_full_catalog_factories_match_models_and_are_deduped` to `MirrorFactoryWriterTest` (asserts model count == factory count; both path arrays fully unique; all files exist on disk).

### Collision fix
The blind trailing-'s' strip in the old `className()` produced identical class names for 3 table pairs (400 tfNames → 397 distinct). The shared `MirrorClassName::map()` resolves these deterministically:
- `tf_users_username` → `TfUsersUsername` (singular claims first); `tf_users_usernames` → `TfUsersUsernames`.
- `tf_bot_inline_results_..._buttons_peer_type` → `...PeerType`; `...peer_types` → `...PeerTypes`.
- Same pattern for the `tf_messages_reply_markup_...` pair.

### Gates
- `vendor/bin/phpunit`: 1087 tests, 20997 assertions, 6 skipped (opt-in PG/ship), 0 failures.
- `vendor/bin/phpstan analyse --no-progress`: [OK] No errors.
- `php bin/standalone-smoke.php`: EXIT=0.
- `tests/Schema/Mirror`: 47 tests, 5222 assertions, OK.
- `php /tmp/nf5-diag.php`: 632/1620/790, 37 parents, 25 migrations, 400 models, deterministic.
- `php /tmp/nf5-doccheck.php`: 139 documented child tables all present in migration output.

### Next step
- Commit this increment, then P1 is effectively complete (catalog matches extraction docs, emission covers 400/400 tables, class names injective). Verify with owner before marking P1 done in plan.
- P2 = wire mirror pipeline into `bin/regenerate`; delete old MigrationGenerator + SqlDdlEmitter; ship dial → mirror migrations only.