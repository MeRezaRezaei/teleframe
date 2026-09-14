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
