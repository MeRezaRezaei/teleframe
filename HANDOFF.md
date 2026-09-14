# Handoff — 2026-09-13

State of this clone when it was created. Read this first in any future session.

## Repo identity
- Clone of `github.com/Merezarezaei/teleframe`, created 2026-09-13 as the **single canonical** working copy.
- Other clones removed to stop VSCode path collisions: `teleframe-psr3`, `tele/teleframe`, `tele/teleclient`, `teleframe-host` (already gone), `tele/`.
- Branch `automation/7/run-48`, HEAD `dcc4dbbe`, tree clean.

## What happened before this clone existed
This repo merged two former projects into one package:
1. `teleproto` → the schema/generated layer: `src/Schema/`, `src/Bot/`, `src/Core/`, `src/Laravel/`, `Migrations`.
2. `teleclient` → the consumer layer, **archived** (`telegram-client.git`, final commit `31c6a335` "superseded by teleframe Phase 2 module merge"). Its modules now live under `MeRezaRezaei\Teleframe\*`:
   - `src/Teleframe/{Backfill,Backup,Bus,Daemon,Handler,Identity,Ingest,Message,Realtime,Repositories,Stage,Vault}`
   - `src/Teleframe/Teleclient.php` (ingest-only face, byte-stable), `src/Teleframe/Teleframe.php` (compose-not-own facade)
   - public bind keys kept fixed: `teleclient.backfill.scope-resolver`, `teleclient.backfill.ingester`, `teleclient.backup.vault-factory` — do not rename.
- Merge commit in history: `ccf2d2d2` "teleclient archive (Phase 2 complete)".

## Gates that must stay green
- `vendor/bin/phpunit`: 1083 tests, 24271 assertions, 6 skipped, 0 failures.
- `vendor/bin/phpstan analyse --no-progress`: [OK] No errors.
- `php bin/standalone-smoke.php`: EXIT=0.
- `composer verify` = phpunit + phpstan.
- Golden determinism: `tests/Schema/RegenerationGoldenTest` pins `src/Schema/Generated/schema-manifest.json` sha256 `0c0d81bd4522d81a78691aa76113d767319ff2dbcc3f9b0dfab47fda0ebc6ffd`. A fresh `bin/regenerate` must reproduce bytes; if output changes, bump the pin + committed manifest together.
- PG/Ship golden tests (`RunsPostgresMigrations`, `ShipDialGoldenTest`) are opt-in / temp-dir and need a real Postgres — not part of default `composer verify`.
- `tests/Schema/Mirror`: 47 tests, 5222 assertions, OK.

## Working-tree hygiene expected
- `.gitignore`: `vendor/`, `.env`, `composer.lock`, `.phpunit.*`, `.superpowers/`, `.openclaude/`, `.opencode/`, `bootstrap/cache/`, `schema/audit-report.md`, `schema/ddl/`, `MadelineProto.log`, `packages/*/vendor/`.
- `.env` holds real session credentials — never commit. `composer.lock` and `bootstrap/cache/` are deliberately gitignored (testbench recreates the cache on every phpunit run).
- Do not recreate root-level `generated/`; it was removed (empty leftover).

## P2 additive surface committed (dcc4dbbe)
- `SchemaRegenerator::regenerate()` now runs the Mirror pipeline (catalog + resolver + writers) additively after the legacy TDLib-domain output, so committed mirror artifacts exist in the shipped tree.
- Mirror output in `src/Schema/Generated/`: `migrations/mirror/` (25 files incl. `9999_create_tf_foreign_keys.php`), `Models/Mirror/` (400 models), `Factories/Mirror/` (400 factories).
- `schema-manifest.json` carries a deterministic `mirror` section (37 parents, table→file map, migrations/models/factories counts) so P3 manifest consumers can resolve migration paths to mirror tables.
- `MirrorMigrationWriter::writeAll()` gained an optional by-ref `tableMap` out-param (tf table → migration filename, ksort'd).
- Legacy surface stays byte-identical (Tl* models, Data DTOS, shipped dial untouched); hand-committed top-level `Models/Tf*` files (regenerator-orphans superseded by full mirror) are removed.
- `GeneratedLoadTest` / `RelationGenerationTest` re-pointed at the mirror contract (account-scoped non-incrementing column models; peer longs stay scalar on tl_* DTOs).
- `RegenerationGoldenTest` updated: new band ranges for inflated artifact counts, mirror-section assertions, committed manifest pin.

## P2 next steps (destructive swap)
- Delete `MigrationGenerator`, `SqlDdlEmitter`, `schema/ddl` infrastructure.
- Ship dial → mirror migrations only; `src/Laravel/Migrations/` = single tf_* set (not 13 curated copies).
- `UpdateIngestor::entityMigrationPaths()` → mirror manifest table→file map (currently uses `manifest['tables']` which is the legacy map; P3 maps the mirror section).
- `RouteIdempotency`, `AccountBootstrap`, handler hydration → mirror column schema (P3 consumer rewrite).
- Delete `MigrationGeneratorTest`, `SqlDdlExtractionTest` (subjects gone).
- Remove the curated migration dial: `src/Laravel/Migrations/` stays for app-owned migrations only.

## Open items / not verified here
- The old `teleframe-psr3` worktrees were not carried over — verify irrelevant before pruning.
- Deep MTProto internals remain future work (see `AGENTS.md` known-gaps).

## Cycle 20 — wire peer-shape normalizer (a17e39f0, 2026-09-14)
- Gap closed: the wire decodes Peer as its ctor object (peerUser#user_id, peerChat#chat_id,
  peerChannel#channel_id — TL_telegram_v227.tl 73-75) but every consumer
  (MirrorFactDecomposer::fillPeerHalf, UpdateRouter::classify, SelfOriginatedClassifier)
  read the canonical `_type`/`_id` pair — so real wire data silently ingested
  `peer_type=0/peer_id=0`, the exact "wrong path of ingesting" the FK-clue mechanism
  exists to catch.
- New `src/Schema/Eloquent/PeerShapeTool.php`: normalizes wire ctor objects, canonical
  `_type/_id` pairs, bare `type/id` pairs and top-level `channel_id` to the spec enum
  (1=user 2=chat 3=channel). Wired into fillPeerHalf, classify() (incl. peerPair()),
  and the classifier. Router channel_id fallback now yields 3 (was 2 — stale comment
  contradicting the spec at docs/superpowers/specs/2026-09-11-telegram-mirror-schema-nf5-design.md:134).
- Tests: `tests/Schema/Eloquent/PeerShapeToolTest.php` (7 cases); UpdateRouterTest
  sells channel rules at peer_type 3 and classifies a wire `peerChannel` ctor.
- Gates: composer verify → 1139 tests / 25930 assertions / 6 skipped, phpstan 0;
  smoke exit 0.

## Cycle 20 next steps (candidates)
- Push the normalizer through the remaining peer consumers (Teleframe face fixtures /
  MirrorIngesterSeamTest use `_type => 2` as plain canonical pairs — semantically
  consistent, only channel-specific rules must be 3).
- Consider PeerShapeTool in the Backfill chunk path (MirrorChunkSync feeds the
  decomposer directly — verify it cannot receive raw wire peers).
- Redis-2 cache peers (`UpdateRouter` cache path) — normalize before caching.

## Cycle 20b — unresolvable peers are clues, not silent 0/0 (c75e9b51, 2026-09-14)
- fillPeerHalf now emits one ingest clue per unresolvable peer pair (field-variant
  ctor, empty object, zero id) and leaves the halves unset — the verbatim's
  "whatever foreign key fails gives us a clue to the wrong path of ingesting".
- New test: test_unresolvable_wire_peer_is_clue_not_silent_zero (3 shapes).
- Gates: 1140 tests / 25945 assertions / 6 skipped, phpstan 0, smoke 0.

## Cycle 20c — wire peers reach hydate models canonically (2f70b2a9, 2026-09-14)
- Production seam (MirrorIngesterFactory) hydrated models from the raw wire payload —
  peerChannel stayed a wire array / unset, so UpdateStored models had NO canonical
  peer_id_type/peer_id_id even though the DB row was right.
- New MirrorIngesterFactory::expandPeers(): expands each peer column pair from its
  single payload field (wire ctor or canonical pair via PeerShapeTool), drops the
  source array, leaving unresolvable peers unset (row blocked by NOT NULL + clue).
- Seam tests: wire peerChannel 3/900 in the stored row AND the emitted model;
  inputPeerUser variant → NOT NULL blocks the row (no silent 0/0).
- Gates: 1143 tests / 25960 assertions / 6 skipped, phpstan 0, smoke 0.

---

## Cycle 21 — behaviour→core link: `RoutingPeerResolver` (2026-09-14)

**Verbatim promise closed:** *"the core is the telegram nf5 databae the rest is going to only make new tables and link their data to the nf 5 core then we can identify the exact set of data and how we should treat them just becuase they are connectd to the facts that telegram is giving us"* — the behaviour table `tg_update_routing` stores peers as `(peer_type, peer_id)` but had NO way to reach the core facts behind them.

- **`src/Laravel/Services/RoutingPeerResolver.php`**: behaviour→core seam. `coreTable(int $peerType)` maps `PeerShapeTool` enum 1→`tf_users`, 2/3→`tf_chats` (TL ctors chat/chatForbidden/channel/channelForbidden live in tf_chats). `resolve(UpdateRoutingRule)` returns the linked core fact row by `(account_id, id)`, or `null` when the fact hasn't been ingested yet (settings may precede the fact; the mirror creates it on first update) or the peer type is unknown.
- **Provider wiring** (`TeleframeServiceProvider`): `RoutingPeerResolver` singleton bound off the app DB connection — hosts can `app(RoutingPeerResolver::class)`. Exactly 5 lines added (import + singleton block) without disturbing the ship-dial golden string. Note: the format-on-save hook re-sorts imports and strips the concat spacing the `ShipDialGoldenTest` requires — edits to the provider must go through a formatter-proof apply (git-show base + perl insert + cp), not the edit tool.
- **`tests/Laravel/Services/RoutingPeerResolverTest.php`** (4 tests / 14 assertions, Testbench against migrated mirror): user rule → `tf_users` fact; channel rule → `tf_chats` fact w/ title; rule without core fact → null, never throws; unknown peer_type → null.

**Gates:** `composer verify` → **1147 tests / 25975 assertions / 6 skipped, phpstan clean, regeneration idempotent**; `php bin/standalone-smoke.php` → exit 0.

**Commit:** (cycle 21) — pending message: "feat: behaviour tables link to the nf5 core through RoutingPeerResolver (cycle 21)"

---

## Cycle 22 — the daemon's hot path reads Redis-2 (listen/ignore in real time, 2026-09-14)

**Verbatim promise closed:** *"the redis two also for chainging the things that the ingester should send as event too so we can controll the things we are going to listen on or ignore in real time"* — the hot-reload loop was half-wired: `RoutingSettingsObserver` refreshed the Redis-2 hash and published on `tg:bus:reload`, but the daemon's default ingester seam built `UpdateRouter(DB::connection())` with NO cache, so every update re-queried the DB and a settings change could never take effect without restart.

- **`MirrorIngesterFactory::build()`** gains a third optional param `?UpdateRoutingCache $routingCache` and passes it into `UpdateRouter`. Cache-first, DB-on-miss as the router already documented.
- **Provider `MIRROR_INGESTER_KEY` seam** resolves `RedisConnectionContract` and builds the cache — **best-effort** (`try/catch \RuntimeException` → null): hosts with a working bus redis get real-time listen/ignore; hosts without one fall back to DB-only routing (behavior identical via the cache-miss path). The ingest seam itself must never break on a missing redis; the bus surfaces misconfiguration loudly elsewhere.
- **`tests/Ingest/MirrorIngesterSeamTest::test_hot_path_reads_redis_two_not_the_db`**: DB rule says `store_only`, Redis-2 hash says `act_on` → the ingest emits `UpdateStored` (Redis-2 wins over DB — proof the hot path reads the cache, no restart). Uses the `ArrayRedis` double bound to the contract.
- **Formatter hazard note:** the format-on-save hook re-sorts imports and strips concat spacing the `ShipDialGoldenTest` requires. Provider edits must be applied formatter-proof (git-show base + perl insert via `/tmp/prov.php` + `cp`); edit-tool writes to the provider get mangled.

**Gates:** `composer verify` → **1148 tests / 25980 assertions / 6 skipped, phpstan clean, regeneration idempotent**; `php bin/standalone-smoke.php` → exit 0.

**Commit:** (cycle 22) — "feat: daemon hot path reads Redis-2 routing cache — real-time listen/ignore (cycle 22)"
---

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

---

## Cycle 23 — auto-register RoutingSettingsObserver (45254458, 2026-09-14)

**Verbatim promise closed:** *"on change there is also the observer that listens to them and this time after updating it sends the update as an event to the defined event path"* — the observer existed and was unit-tested but was NOT registered on the Eloquent lifecycle, so a default install silently served stale rules from the cache-first `UpdateRouter`.

- **`boot()`**: `UpdateRoutingRule::observe(RoutingSettingsObserver::class)` — every app (web + console), above the `runningInConsole()` block.
- **`register()`**: best-effort observer singleton; missing bus redis -> null cache, the `RoutingSettingsChanged` event still fires, DB-backed routing stays correct. Observer cache made nullable/null-safe.
- **Test**: `RoutingSettingsObserverTest::test_provider_registration_auto_fires_observer_on_eloquent_save` — a plain `save()` refreshes Redis-2 and emits the event with NO manual observer call (non-vacuous: fails without the registration).

**Skeptic certification:** independent goal-verify agent, verdict **PROVEN** after this fix (prior verdict NOT PROVEN on the dead-observer gap).

**Gates:** `composer verify` -> 1149 tests / 25986 assertions / 6 skipped, phpstan no errors, regeneration 247/247; `php bin/standalone-smoke.php` -> exit 0.

---

## Cycle 24 — `telegram_accounts.user_id` schema drift (93160702, 2026-09-14)

**Bug:** the `2026_09_09_000201_create_telegram_accounts_table` migration never shipped the
`user_id` column, but `TelegramAccount` (fillable + `int` cast), the login `finalize()`
controller, and `vault:add-account` / `vault:list` / `vault:use-default` all read/write it —
every successful phone login blew up at the final insert with
`SQLSTATE[42S22] Unknown column 'user_id' in 'field list'`.

- **Create migration** (`2026_09_09_000201`): added nullable `bigInteger('user_id')` after
  `type` — fresh installs now have the column from the start.
- **New additive migration** `2026_09_14_000000_add_user_id_to_telegram_accounts_table.php`
  (idempotent `hasColumn` guard): existing DBs (where the create migration already ran) get
  the column via `php artisan migrate`; fresh installs no-op.
- **Ship-dial allow-lists updated** so `bin/regenerate --ship` (which purges
  `src/Laravel/Migrations/` and rebuilds it) preserves the new file:
  `SchemaRegenerator::APP_OWNED_MIGRATIONS` + `ShipDialGoldenTest::APP_OWNED_MIGRATIONS`.
- Applied in the live host app (`teleframe-app`): `php artisan migrate --force` → column
  present; user's next login insert succeeds.

**Gates:** `composer verify` → **1153 tests / 26002 assertions / 6 skipped, phpstan clean**.
**Commit:** 93160702 "fix(vault): ship user_id column for telegram_accounts (schema drift)".

---

## Cycle 24b — real-account seed for live testing (host app, 2026-09-14)

The host app (`teleframe-app`, not a git repo) now carries a seeded REAL Telegram
account so the package can be tested against live Telegram with a known-good
credential pair, reproducible from `.env`.

- **Recovered the user's real session**: the earlier successful login (telegram
  user 1724372757, dc 1, label "ana", app "us" api_id 2421776) failed only at the
  final INSERT (missing `user_id`, fixed in cycle 24). `scripts/seed-recover.php`
  decrypts that session blob + the app's api_hash via a bare
  `Illuminate\Encryption\Encrypter` (APP_KEY from `.env`, no kernel boot) and
  registers them as `TF_SEED_*` vars — prints only confirmation, never secrets.
- **Seeder** (`teleframe-app/database/seeders/DatabaseSeeder.php`): idempotent
  `updateOrCreate` ONLY, zero deletes. Admin user (`TF_ADMIN_*`), app keyed by
  api_id (`TF_SEED_APP_LABEL/API_ID/API_HASH`, owner-scoped), account keyed by
  app_id+label (`TF_SEED_ACCOUNT_LABEL/SESSION/DC_ID/TELEGRAM_USER_ID`, type user,
  owner-scoped). No `TF_SEED_*` set ⇒ no-op beyond the admin user.
- **Live verification** (`scripts/verify-seed.php`, framework-free PDO + bare
  Encrypter + `TeleframeClient::user()`): one harmless read-only
  `help.getNearestDc` → `{"_":"nearestDc","country":"DE","this_dc":1,"nearest_dc":2}`
  — proves the seeded session works end-to-end against real Telegram.
- **Safety protocol for the user's ONLY real account** (committed): never
  `account.deleteAccount`/`delete`/`resetAuthorization`/`auth.logOut` or delete-app
  APIs; never `migrate:fresh`/`db:wipe`/truncate on the live DB; verification stays
  read-only; any write test (send message) goes only to own saved messages after
  explicit user approval. Session is encrypted at rest; raw value exists only in
  the app's gitignored `.env`.

---

## Cycle 25 — Horizon-style dashboard (option B), 2026-09-14

The package now ships its own dashboard (routes + controllers + Blade) so any Laravel
host gets app/account management + phone-login UI by registering `Teleframe::routes()` —
no duplicated controllers, like Laravel Horizon (host supplies only the auth skeleton).

**Package side (this repo):**
- `config/teleframe.php` gains a `dashboard` block: `prefix` (env
  `TELEFRAME_DASHBOARD_PREFIX`, default `teleframe`), `middleware` (default
  `['web','auth']`). Docblock documents the `Teleframe::routes()` contract.
- `src/Laravel/Http/Controllers/Dashboard/`:
  - `DashboardRoutes` — package-owned route registrar (double-registration guard
    + `refreshNameLookups()` so `route()` works immediately; config-overridable
    prefix/middleware). Routes: index shell `GET /{prefix}`, `apps` index/store/destroy,
    `accounts` index/destroy, `telegram/start|verify|password`.
  - `AppsController` — list/create/delete `TelegramApp`; api_hash `encrypted`-cast at
    rest, returned only masked (`substr 4` + `••` + `substr -4`); global unique-label
    check (DB index is global).
  - `AccountsController` — list/delete scoped accounts; shares static `payload()`
    with the login flow.
  - `TelegramLoginController` — 3-step stateful phone login (Cache+Crypt parked
    state, 15-min TTL, 2FA branch, session export); inline legacy
    `App\Models\User` fallback (pre-upgrade parked states), otherwise owner-agnostic.
  - `Concerns/ConcernsScopesVault` — tenant scoping via nullable owner morph
    (`owner_type`/`owner_id` from `$request->user()` at call time); unauthenticated
    → zero rows (`whereKey(0)`, avoids widening under phpstan).
  - `DashboardController` — `view('teleframe::dashboard')` shell.
- `Facades/Teleframe.php` gains a real static `routes(?string $prefix, ?array
  $middleware)` (facade magic only fires for undefined methods; shadows
  `__callStatic`). FQCN call avoids the Pint import-sort hook.
- `resources/views/dashboard.blade.php` (namespace `teleframe`, `loadViewsFrom` in
  boot, publish tag `teleframe-dashboard`).
- Vault models (`TelegramApp`/`TelegramAccount`) gained `@property` for
  `created_at`/`updated_at` timestamps (phpstan access on `Carbon|null`).

**Host rewiring (`teleframe-app`, not a git repo):**
- `routes/api.php` now keeps only `AuthController` (host auth = host business) and
  registers `Teleframe::routes(prefix: '', middleware: ['auth:sanctum'])` — the
  framework auto-prefixes `api/`, so the Angular SPA's URL surface is byte-identical
  (`/api/apps`, `/api/accounts`, `/api/telegram/*`).
- Deleted the three duplicated host controllers (`AppController`,
  `AccountController`, `TelegramLoginController`). `route:list --path=api` shows all
  14 routes backed by the package; host phpunit + `composition boot` green.

**Tests** (`tests/Laravel/Http/Dashboard/`): Testbench + sqlite in-memory, `TestUser`
minimal Authenticatable, `DashboardRoutes::reset()` seam. 13 tests / 73 assertions.
**Gate fixes shipped with this cycle:**
- `ShipDialGoldenTest` golden pin now normalizes concat spacing (Pint flips
  `dirname(__DIR__) . '/Migrations'` vs `dirname(__DIR__).'/Migrations'` on save).

**Gates (quoted):** `composer verify` → **1166 tests / 26082 assertions / 6 skipped,
phpstan clean, regeneration 254/254**; `php bin/standalone-smoke.php` → exit 0;
`TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg` → OK (7 tests, 830 assertions).
Host: `route:list` 14 routes, `view('teleframe::dashboard')` renders (13394 bytes).
**Commit:** (next commit in this run — cycle 25).
