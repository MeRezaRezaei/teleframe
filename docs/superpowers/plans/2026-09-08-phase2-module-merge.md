# Phase 2: Module Merge — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Merge the five teleclient client modules — `Ingest`, `Bus`, `Daemon`, `Backfill`, `Backup` — plus the `Console` artisan surface, the `Schema` mirror generator layer, its committed `generated/` tree, the curated `migrations/` dial and the mirror `.tl` sources into teleframe behind PSR seams, one module per task, tests moving with their module. After the phase gate (single package, both suites green inside teleframe, plain-PHP construct of every module proven) the teleclient repo is archived.

**Architecture:** Modules land literally at `src/Teleframe/*` per spec §5. Namespaces stay single-segment (`MeRezaRezaei\Teleframe\Ingest\...`, not `Teleframe\Teleframe\...`) via a broad PSR-4 root; the pre-existing specific roots (`Core`, `Bot`, `Laravel`, `Schema`) keep winning by longest-prefix. The `Schema` mirror generator folds into the existing `src/Schema/` namespace (`Generator\`, `Eloquent\`), the regenerated tree stays at the package root `generated/` under `MeRezaRezaei\Teleframe\Schema\Generated\`, and the curated dial stays at root `migrations/`. `TeleclientServiceProvider` is dissolved into teleframe's single `TeleframeServiceProvider`. The public face class survives Phase 2 as `MeRezaRezaei\Teleframe\Teleclient` (transitional; the composed `Teleframe` facade is Phase 3's deliverable).

**Tech Stack:** PHP 8.2+, composer PSR-4, illuminate/{database,support,console,http,redis,events}, spatie/laravel-data ^4, symfony/uid, predis/predis ^3.6, ext-pdo, ext-sodium, orchestra/testbench, phpunit ^11, phpstan (level 5, `preg_*` ban).

**Spec:** `docs/superpowers/specs/2026-09-07-teleframe-unification-design.md` §5 (Phase 2 row, line 73) and §3 (target module layout). Roadmap: `docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 2 (lines 37-42).

## Global Constraints

- The spec's phrase `src/Teleframe/*` is honored literally: the five modules physically live under `src/Teleframe/{Module}/`. Namespace convention of the repo (segment = module) is preserved by explicit PSR-4 mapping; the stock teleframe roots (`Core/Bot/Laravel/Schema`) are untouched.
- `preg_*` stays banned in moved source (teleclient's own policy was regex-free in `src/` — no new `allowIn` needed; `src/Laravel/*` and `src/Schema/*` already carry their historical `allowIn`).
- Generated artifacts never hand-edited: the mirror is **regenerated in place** inside teleframe with the new namespace via `php bin/regenerate`, never sed-renamed across 35 MB. Golden tests (`RegenerationGoldenTest`, `ShipDialGoldenTest`) prove byte-identical regen vs committed tree.
- NO implicit migrate anywhere (D4). The provider only `loadMigrationsFrom()`; migrations remain developer-run.
- Gates after every task: teleframe `composer verify` green (all suites) + the task's own module suite green. `tests/Pg` stays env-gated (`TELEFRAME_PG=1`, PG 17) and is excluded from the default sqlite matrix run.
- env/config namespace moves `teleclient.*` → `teleframe.*` and `TELECLIENT_*` → `TELEFRAME_*` (single package; old package is archived, no BC needed). The **redis stream/group/route key VALUES** inside `StreamSchema` (`tg:stream:updates`, `teleclient` group, `tg:bus:reload`, `tg:bus:routes`) are opaque runtime state, not namespaces — they are NOT renamed in this phase (renaming would strand any live redis state and is a Phase 4 cleanup candidate). The `Bus` migration's `ArrayRedis` test pins `StreamSchema::GROUP === 'teleclient'`; that assertion survives unchanged.
- Commands are renamed to `teleframe:*` as primary; the rename-era `telegram-client:*` aliases are dropped (dead-line policy, Phase 4 already slated to delete them). No `teleclient:*` aliases are kept: after this phase the teleclient package is archived, so a host still calling `teleclient:*` is on a frozen package — aliases would only postpone the rename it has to make anyway.
- Cross-module source dependencies in teleframe move from `MeRezaRezaei\Teleframe\{Core,Laravel}` (already teleframe namespaces) into intra-package refs with no code edits; `MeRezaRezaei\Teleclient\*` refs become `MeRezaRezaei\Teleframe\*` mechanically.
- Ruling A (recorded): the `Schema` mirror generator layer + `generated/` + `migrations/` + `schema/sources/` move in Task 1, NOT as their own roadmap task block, because `Ingest` (`UpdateIngestor`) imports the generator metamodel and generated models at runtime — the five client modules cannot ship without it. The roadmap's "one module per task" applies to the five client tasks; Task 1 carries this documented prerequisite.
- Ruling B (recorded, test-move ordering): a test moves with its **own module's suite** unless its direct imports pull classes from a LATER module — then it rides with the later module so every task gate is independently green. Specifically, verified by grep on teleclient `tests/`:
  - `tests/Ingest/TeleclientTest.php` imports `MeRezaRezaei\Teleclient\Teleclient` (the public face) → the **public face moves with Ingest in Task 2**, not Task 7 (its singleton + `TeleclientTest` ride there too; Task 7 then only does docs/smoke/archive/ticks).
  - `tests/Standalone/PlainPhpLoadTest.php` imports `Ingest\{Events\UpdateStored,UpdateIngestor}` and `new UpdateIngestor()` → moves with **Ingest, Task 2**, not Task 1.
  - `tests/Pg/*` (FullMirrorPg, PgTestCase, DeferredFk) import `Ingest\{EntityAggregator,UpdateIngestor}`, `Tests\Ingest\Concerns\HasNestedUpdateFixtures`, `Tests\Schema\TestCase`, `Tests\Concerns\RunsPostgresMigrations` → moves with **Ingest, Task 2** (needs Schema Task 1 + Ingest); the `Pg` phpunit suite and the CI `pg-tests` job are added in **Task 2**, not Task 1.
  - `tests/Console/IngestCommandTest.php` imports `Bus\{LaravelRedisAdapter,RedisConnectionContract,StreamSchema}`, `Console\IngestCommand`, `Tests\Support\ArrayRedis`, `TeleclientServiceProvider` → moves with **Bus, Task 3** (command source `IngestCommand` moves in Task 2 with its module, only its `CONSUMER_CLASS` string constant is updated there).
  - `tests/Support/ArrayRedis.php` imports `Bus\RedisConnectionContract` → moves with **Bus, Task 3**; the other Support files (`FakeUserScope`, `FakeVaultApi`, `RecordingConnection`, `ScriptedFetch`, `SleepRecorder`) import only Illuminate/teleframe/PHP → move in Task 1.
  - `tests/Console/BackfillCommandTest.php` imports `Console\BackfillCommand` + `Tests\Ingest\IngestTestCase` + `Support\FakeUserScope` → moves with **Backfill, Task 5**.
  - `tests/Console/BackupCommandTest.php` imports `Backup\{InMemoryVault,VaultInterface}`, `Console\{BackupCommand,BackfillCommand}`, `TeleclientServiceProvider` → moves with **Backup, Task 6**.
  - `tests/Concerns/RunsPostgresMigrations.php` imports only Illuminate + PDO → moves in Task 1 (used by `tests/Pg`).
  - phpunit `<testsuite>` entries and the CI `setup-php` extension list are added **only in the task that creates the directory/needs the extension** — PHPUnit errors on a suite dir that does not exist yet.
- All other teleclient test files import only their own module + `Tests\Schema\TestCase` (+ generated `Schema\Generated\*` from Task 1) → they ride with their named module task.

---

## Namespace / Layout Mapping (rulings)

| Legacy (teleclient) | Moved to namespace | Physical location |
|---|---|---|
| `MeRezaRezaei\Teleclient\Ingest\*` | `MeRezaRezaei\Teleframe\Ingest\*` | `src/Teleframe/Ingest/` |
| `MeRezaRezaei\Teleclient\Bus\*` | `MeRezaRezaei\Teleframe\Bus\*` | `src/Teleframe/Bus/` |
| `MeRezaRezaei\Teleclient\Daemon\*` | `MeRezaRezaei\Teleframe\Daemon\*` | `src/Teleframe/Daemon/` |
| `MeRezaRezaei\Teleclient\Backfill\*` | `MeRezaRezaei\Teleframe\Backfill\*` | `src/Teleframe/Backfill/` |
| `MeRezaRezaei\Teleclient\Backup\*` | `MeRezaRezaei\Teleframe\Backup\*` | `src/Teleframe/Backup/` |
| `MeRezaRezaei\Teleclient\Console\*` | `MeRezaRezaei\Teleframe\Laravel\Console\*` | `src/Laravel/Console/` |
| `MeRezaRezaei\Teleclient\Schema\Generator\*` | `MeRezaRezaei\Teleframe\Schema\Generator\*` | `src/Schema/Generator/` |
| `MeRezaRezaei\Teleclient\Schema\Eloquent\*` | `MeRezaRezaei\Teleframe\Schema\Eloquent\*` | `src/Schema/Eloquent/` |
| `MeRezaRezaei\Teleclient\Schema\RegenerateCommand` | `MeRezaRezaei\Teleframe\Laravel\Console\RegenerateCommand` | `src/Laravel/Console/` |
| `MeRezaRezaei\Teleclient\Schema\Generated\*` | `MeRezaRezaei\Teleframe\Schema\Generated\*` | `generated/` (package root) |
| `MeRezaRezaei\Teleclient\Teleclient` (public face) | `MeRezaRezaei\Teleframe\Teleclient` | `src/Teleframe/Teleclient.php` |
| `MeRezaRezaei\Teleclient\TeleclientServiceProvider` | dissolved → `TeleframeServiceProvider` | `src/Laravel/Providers/` |
| `MeRezaRezaei\Teleclient\Tests\*` | `MeRezaRezaei\Teleframe\Tests\*` | `tests/{Module}/` |
| `src-compat/` (`MeRezaRezaei\TelegramClient\*`) | NOT ported (dead-line; Phase 4 deletes) | stays in archived teleclient |
| `config/teleclient.php` | sections merged into `src/Laravel/config/teleframe.php` under `teleframe.*` | |
| `schema/sources/*.tl` (3 mirror files) | same path, teleframe root `schema/sources/` | |
| `bin/regenerate`, `bin/standalone-smoke.php` | ported + extended | `bin/` |
| `docs/{index,quickstart,ingest,bus,backup}.md` | ported, refs renamed | `docs/` |

**Composer PSR-4 after Task 1** (additions in bold):

```json
"autoload": {
    "psr-4": {
        "MeRezaRezaei\\Teleframe\\Core\\": "src/Core/",
        "MeRezaRezaei\\Teleframe\\Bot\\": "src/Bot/",
        "MeRezaRezaei\\Teleframe\\Laravel\\": "src/Laravel/",
        "MeRezaRezaei\\Teleframe\\Schema\\": "src/Schema/",
        "MeRezaRezaei\\Teleframe\\": "src/Teleframe/",
        "MeRezaRezaei\\Teleframe\\Schema\\Generated\\": "generated/"
    }
}
```

The broad root `MeRezaRezaei\Teleframe\` → `src/Teleframe/` is the seam the spec requires; all five `Teleframe\{Module}` namespaces resolve through it (e.g. `MeRezaRezaei\Teleframe\Ingest\UpdateIngestor` → `src/Teleframe/Ingest/UpdateIngestor.php`), the existing specific roots win for their own segments, and `MeRezaRezaei\Teleframe\Schema\Generated\` → `generated/` wins over the `Schema\` root for generated classes.

**New composer `require` entries** (silent additions — no `merezarezaei/teleframe` self-dep, no `merezarezaei/teleclient` dependency): `ext-pdo`, `ext-sodium`, `illuminate/database ^10|^11|^12`, `illuminate/redis ^10|^11|^12`, `spatie/laravel-data ^4`, `symfony/uid ^6|^7`, `predis/predis ^3.6`.

**Teleclient root-depth rule** (applies to every `dirname(__DIR__, N)` below): teleclient package root was reached by `dirname(__DIR__, 2)` from `src/Module`. After the move the modules sit one level deeper (`src/Teleframe/Module` / `src/Laravel/Console`), so package-root `generated/`, `migrations/`, `schema/sources/`, `schema-manifest.json` paths all need `dirname(__DIR__, 3)` in: `UpdateIngestor::migrationPaths()`, `RouteIdempotency::migrationPaths()`, `RegenerateCommand::handle()`. Exceptions: `TeleframeSchemeLoader::defaultSourcesDir()` (src/Schema/Generator keeps depth 3 — same depth as teleclient) and the provider's `loadMigrationsFrom()` (see Task 1 Step 7).

---

### Task 1: Foundation + Schema mirror layer (composer, autoload, config, CI, generator layer, regenerated tree, curated dial)

**Files:**
- Modify: `composer.json` (require + autoload.add; nothing removed)
- Modify: `src/Laravel/config/teleframe.php` (add `schema`, `ship_namespaces`, `bus`, `daemon`, `backfill`, `backup` sections; env reads renamed to `TELEFRAME_*`)
- Modify: `src/Laravel/Providers/TeleframeServiceProvider.php` (fold client register/boot P1 sections that only depend on Task 1 classes)
- Modify: `.github/workflows/run-tests.yml` (extensions `sodium`, `pdo`, `pdo_sqlite`, `sqlite3`, `pdo_pgsql` on the matrix job; NO pg-tests job yet — that lands in Task 2 with `tests/Pg`)
- Move+rename namespace: `src/Schema/Generator/*` (16 files incl. `Model/` subdir), `src/Schema/Eloquent/*` (3), `src/Schema/RegenerateCommand.php` → `src/Laravel/Console/`
- Move: `schema/sources/*.tl` (3), `bin/regenerate`
- Recreate by regen: `generated/`, `migrations/` (never copy the 35 MB teleclient tree)
- Move+rename: `tests/Schema/*` (13 files), `tests/Support/*` **except `ArrayRedis.php`**, `tests/Concerns/*`
- Do NOT touch (Ruling B): `tests/Standalone/*`, `tests/Pg/*`, `tests/Console/*`, `tests/Support/ArrayRedis.php`, the public face, `Ingest`/`Bus`/`Daemon`/`Backfill`/`Backup` sources.

**Interfaces:**
- Consumes: `SchemaArtifacts` (engine-side, unaffected), `MeRezaRezaei\Teleframe\Core\MTProto\TL\{TLSignatureParser,ParsedSignature}` (already teleframe), composer autoloader, `php bin/regenerate`.
- Produces: `MeRezaRezaei\Teleframe\Schema\Generator\{SchemaRegenerator,TeleframeSchemeLoader,Naming,ModelGenerator,DtoGenerator,FactoryGenerator,MigrationGenerator,CodeWriter,Manifest,TlCanon,TlParser,TlParseException,TlRegenerateException,Model\{TlConstructor,TlMethod,TlParam,TlScheme,TlType}}`, `MeRezaRezaei\Teleframe\Schema\Eloquent\{TlAnchorModel,TlInstanceModel,HasTlChildren}`, `MeRezaRezaei\Teleframe\Laravel\Console\RegenerateCommand` (signature `teleframe:regenerate`), `bin/regenerate`, regenerated `generated/` (new namespace) + `migrations/` (112 curated), merged `teleframe.*` config sections, `TeleframeServiceProvider` P1 (SchemaRegenerator singleton + RegenerateCommand registration + `loadMigrationsFrom`).

- [x] **Step 1: composer.json** — add the new `require` entries and the two new PSR-4 roots (broad `MeRezaRezaei\Teleframe\` → `src/Teleframe/` and `MeRezaRezaei\Teleframe\Schema\Generated\` → `generated/`) exactly as in the mapping table. Run `composer update --no-interaction` (composer.lock stays gitignored). Do NOT add any new phpunit suite entries — the existing `Schema` suite already points at `tests/Schema`, and no new test dirs exist yet.
- [x] **Step 2: Move the Schema generator layer.** Copy teleclient `src/Schema/Generator/*`, `src/Schema/Eloquent/*`, `src/Schema/RegenerateCommand.php` wholesale into `src/Schema/Generator/`, `src/Schema/Eloquent/`, and `src/Laravel/Console/RegenerateCommand.php`. Rewrite namespace + all `use` lines mechanically: `MeRezaRezaei\Teleclient\Schema\` → `MeRezaRezaei\Teleframe\Schema\` (covers `Generator`, `Eloquent`, `RegenerateCommand`, `Model\Tl*`); the file landing in `Laravel\Console` gets namespace `MeRezaRezaei\Teleframe\Laravel\Console`. In `RegenerateCommand`: `$signature = 'teleframe:regenerate'`, `$aliases = []` (drop `telegram-client:regenerate`), update the docblock `config` refs `teleclient.ship_namespaces` → `teleframe.ship_namespaces`, and in `handle()` set `$packageRoot = dirname(__DIR__, 3)` (was 2 — module is one level deeper now).
- [x] **Step 3: Fix generator namespace constants + root depth.** In the moved generators: `DtoGenerator::{METHODS_NS,TYPES_NS}` → `'MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods|Types'`; `ModelGenerator::NS` → `'MeRezaRezaei\Teleframe\Schema\Generated\Models'`; `FactoryGenerator::NS` → `'MeRezaRezaei\Teleframe\Schema\Generated\Factories'`; every emitted `use MeRezaRezaei\Teleclient\Schema\Eloquent\...` line → `MeRezaRezaei\Teleframe\Schema\Eloquent\...`. `TeleframeSchemeLoader::defaultSourcesDir()`: keep `dirname(__DIR__, 3) . '/schema/sources'` (already reaches the teleframe root mirror) and rewrite its config read `config('teleclient.schema_sources')` → `config('teleframe.schema_sources')`; the stale `vendor/merezarezaei/teleframe/...` and `../merezarezaei/teleframe/...` teleclient-era fallback branches are dropped (teleframe IS the engine now — the vendored-mirror pointer is meaningless).
- [x] **Step 4: Move the mirror corpus.** Copy teleclient `schema/sources/` (TL_telegram_v227.tl, TL_mtproto_v1.tl, TL_secret.tl) → teleframe `schema/sources/`. Do NOT copy `generated/` or `migrations/` — they are artifacts to be regenerated in place.
- [x] **Step 5: Regenerate in place.** Port `bin/regenerate` (`require` stays `../vendor/autoload.php`; the `use MeRezaRezaei\Teleclient\Schema\RegenerateCommand` → `MeRezaRezaei\Teleframe\Laravel\Console\RegenerateCommand`; `Application('teleframe', '1.0.0')`; input line `teleframe:regenerate`). Run `php bin/regenerate --ship`. Commit the full regenerated `generated/` (Models, Data/, Factories, migrations, `schema-manifest.json` — now under `MeRezaRezaei\Teleframe\Schema\Generated\`) + `migrations/` (112 curated dial). `generated/` and `migrations/` are NOT gitignored in teleframe, so they are committed (same as teleclient did).
- [x] **Step 6: Config sections.** Append to `src/Laravel/config/teleframe.php` (under the existing keys): `schema_sources` (env `TELEFRAME_SCHEMA_SOURCES`, default null), `ship_namespaces` (env `TELEFRAME_SHIP_NAMESPACES`, default `SchemaRegenerator::DEFAULT_SHIP_NAMESPACES`), `bus` (`stream`/`group`/`reload_channel` = the LITERAL `StreamSchema` const values `tg:stream:updates` / `teleclient` / `tg:bus:reload` — the class moves in Task 3, which then drives these from the consts; `connection` = 'default', `redis_client` = env `TELEFRAME_REDIS`, default 'predis'), `daemon` (`accounts` = `[]`), `backfill` (`request_budget` = env `TELEFRAME_BACKFILL_BUDGET` default 25, `flood_cap_seconds` = 3600), `backup` (`driver` = env `TELEFRAME_BACKUP_DRIVER` default 'memory', `account` = env `TELEFRAME_BACKUP_ACCOUNT`, `chunk_size` = env `TELEFRAME_BACKUP_CHUNK_SIZE` default 4194304, `sets.default` with `paths`/`excludes` copied from teleclient config). All `TELEGRAM_CLIENT_*` fallbacks dropped.
- [x] **Step 7: Provider P1 merge.** In `TeleframeServiceProvider::register()` add `singleton(SchemaRegenerator::class)` (the `mergeConfigFrom(__DIR__ . '/../config/teleframe.php', 'teleframe')` already exists). In `boot()` add `loadMigrationsFrom(dirname(__DIR__, 3) . '/migrations')` (from `src/Laravel/Providers` 3 levels up = teleframe root — NOT a relative `__DIR__ . '/../migrations'` chain) and register `RegenerateCommand::class` in the existing runningInConsole commands array. Keep the four engine commands untouched.
- [x] **Step 8: Move Schema + Support (minus ArrayRedis) + Concerns tests.** Copy teleclient `tests/Schema/*`, `tests/Support/*` (except `ArrayRedis.php`), `tests/Concerns/*` into teleframe `tests/`. Rewrite namespace `MeRezaRezaei\Teleclient\Tests\` → `MeRezaRezaei\Teleframe\Tests\`. In `tests/Schema/TestCase.php` update `getPackageProviders()` to `[LaravelDataServiceProvider::class, MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider::class]`. No phpunit suite changes (Schema suite exists; Support/Concerns contain no `*Test.php`).
- [x] **Step 9: CI extensions.** In `.github/workflows/run-tests.yml` matrix `setup-php` extensions append `sodium, pdo, pdo_sqlite, sqlite3, pdo_pgsql`. Do NOT add the `pg-tests` job yet (needs tests/Pg, Task 2).
- [x] **Step 10: Lock the golden baseline.** `composer install`, `composer verify`, then `vendor/bin/phpunit tests/Schema`. `RegenerationGoldenTest` + `ShipDialGoldenTest` must pass byte-identical vs the freshly regenerated tree. Expect the ±30% constructor-count sanity gate to pass with the v227 mirror.

**Gate:** `composer verify` green; `tests/Schema` green; regenerated `generated/` + `migrations/` committed under the new namespace; `php bin/regenerate` reproduces them byte-identically (`git status --porcelain generated migrations` clean after rerun); CI matrix has sodium/PDO extensions; commit.

---

### Task 2: Ingest module (UpdateIngestor, EntityAggregator, RouteIdempotency, PayloadWalker, IdentityLock, Events\UpdateStored, IngestCommand, public face)

**Files:**
- Move+rename: `src/Teleframe/Ingest/*` (6 files + `Events/`)
- Move+rename: `src/Teleclient.php` → `src/Teleframe/Teleclient.php` (public face — Ruling B)
- Move+rename: `src/Laravel/Console/IngestCommand.php`
- Move+rename: `tests/Ingest/*` (10 tests + 2 Concerns), `tests/Standalone/*`, `tests/Pg/*`
- Modify: `UpdateIngestor` root-depth + `MODELS_NS`; `RouteIdempotency` root depth; `IngestCommand` signature + `CONSUMER_CLASS` string; provider ingest/face singletons
- Modify: `phpunit.xml.dist` (add suites `Ingest`, `Standalone`, `Pg`)
- Modify: `.github/workflows/run-tests.yml` (add `pg-tests` job; `TELEFRAME_PG=1`)
- Modify: `tests/Pg/PgTestCase.php` + `tests/Concerns/RunsPostgresMigrations.php` env gate → `TELEFRAME_PG`

**Interfaces:** Consumes generated models + generator metamodel (Task 1), `MeRezaRezaei\Teleframe\Core\...` unchanged. Produces `MeRezaRezaei\Teleframe\Ingest\*`, `MeRezaRezaei\Teleframe\Teleclient`, `MeRezaRezaei\Teleframe\Laravel\Console\IngestCommand`.

- [x] **Step 1:** Copy `src/Ingest/*` → `src/Teleframe/Ingest/`, rewrite ns/uses to `MeRezaRezaei\Teleframe\Ingest\`.
- [x] **Step 2:** `UpdateIngestor` at `src/Teleframe/Ingest` → `dirname(__DIR__, 3)` for `generated/`, `migrations/`, `schema-manifest.json` roots; `MODELS_NS = 'MeRezaRezaei\Teleframe\Schema\Generated\Models\\'`; update the stale error hint to `run artisan teleframe:regenerate`. Same depth change in `RouteIdempotency::migrationPaths()` (`dirname(__DIR__, 3) . '/generated/migrations/2026_08_28_900633_create_tl_route_tables.php'`).
- [x] **Step 3:** Public face `src/Teleclient.php` → `src/Teleframe/Teleclient.php`, namespace `MeRezaRezaei\Teleframe\Teleclient`, uses → `Teleframe\{Ingest,Schema\Generated}\*` (ctor shape `(UpdateIngestor, EntityAggregator)` unchanged — still plain-PHP constructible). Provider: `singleton(UpdateIngestor::class, fn ($app) => new UpdateIngestor(...))` (keep the dispatch/clock injection teleclient used), `singleton(EntityAggregator::class)`, `singleton(Teleclient::class)`.
- [x] **Step 4:** `IngestCommand` → `MeRezaRezaei\Teleframe\Laravel\Console\IngestCommand`, `$signature = 'teleframe:ingest'`, `$aliases = []` (drop `telegram-client:ingest`), `CONSUMER_CLASS = 'MeRezaRezaei\Teleframe\Bus\IngestConsumer'` (string literal — resolves only when the command runs; Bus lands in Task 3). Provider registers it. Keep `--once`/`--max` options and pcntl handling byte-identical.
- [x] **Step 5:** Move `tests/Ingest/*` (incl. `IngestTestCase` extending `Tests\Schema\TestCase`, `Concerns\{RunsMigrations,HasNestedUpdateFixtures}`), `tests/Standalone/*` (`PlainPhpLoadTest` — asserts `new UpdateIngestor()` and `UpdateStored` purity), `tests/Pg/*` (PgTestCase + FullMirrorPg + DeferredFk; `RunsPostgresMigrations` concern already in tests/Concerns from Task 1). Namespace rewrite `Teleclient\Tests\` → `Teleframe\Tests\`. In PgTestCase + RunsPostgresMigrations rename the env gate `TELECLIENT_PG` → `TELEFRAME_PG` (skip message too). phpunit.xml.dist: add suites `Ingest` (`tests/Ingest`), `Standalone` (`tests/Standalone`), `Pg` (`tests/Pg`). CI: add the `pg-tests` job mirroring teleclient's (PG 17 service, `TELEFRAME_PG=1`, `TELEFRAME_PG_{HOST,PORT,DATABASE,USER,PASSWORD}`, `vendor/bin/phpunit tests/Pg`, PHP 8.4).
- [x] **Step 6:** `vendor/bin/phpunit tests/Ingest tests/Standalone tests/Pg` (Pg will skip without the env gate), then full `composer verify`.

**Gate:** Ingest suite green (route-dedup, nested updates, idempotency, injectable clock, DTO dispatch, public-face singleton/delegation), Standalone green (plain-PHP construct proof for Ingest now active), Pg env-skips by default on the matrix and is green on PG-17 via the new CI job, `composer verify` green, commit.

---

### Task 3: Bus module (RedisStreamSink, StreamSchema, RouteTable, HotReloadRouter, IngestConsumer, LaravelRedisAdapter, RedisConnectionContract)

**Files:**
- Move+rename: `src/Teleframe/Bus/*` (7 files)
- Move+rename: `tests/Bus/*` (8 files), `tests/Support/ArrayRedis.php` (Ruling B), `tests/Console/IngestCommandTest.php` (Ruling B)
- Modify: provider Redis binding; config `teleframe.bus.*` reads; `StreamSchema` consts drive `teleframe.bus.stream/group/reload_channel` defaults (replacing Task 1 literals)
- Modify: `phpunit.xml.dist` (add suites `Bus`, `Console`)

**Interfaces:** `RedisStreamSink`/`IngestConsumer` already implement teleframe's `MeRezaRezaei\Teleframe\Core\Contracts\UpdateSinkInterface` — unchanged. `IngestConsumer` import of the public face `use MeRezaRezaei\Teleclient\Teleclient;` → `use MeRezaRezaei\Teleframe\Teleclient;` (Task 2). Produces `MeRezaRezaei\Teleframe\Bus\*` incl. `RedisConnectionContract` (host override seam).

- [x] **Step 1:** Copy `src/Bus/*` → `src/Teleframe/Bus/`, ns rewrite to `MeRezaRezaei\Teleframe\Bus\` (the `use MeRezaRezaei\Teleframe\Core\Contracts\UpdateSinkInterface` lines are already correct). Verify `StreamSchema::GROUP` stays `'teleclient'` (Ruling on stream values — no rename).
- [x] **Step 2:** Provider: bind `RedisConnectionContract` per teleclient logic (`LaravelRedisAdapter` when `app('redis')` exists, loud failure otherwise, message suggesting `illuminate/redis` or a host override); config reads `teleframe.bus.connection` / `teleframe.bus.redis_client`. Change the config defaults so `stream`/`group`/`reload_channel` reference `StreamSchema::{STREAM,GROUP,RELOAD_CHANNEL}` (replacing the Task 1 literals; values identical).
- [x] **Step 3:** Move `tests/Bus/*` + `tests/Support/ArrayRedis.php` + `tests/Console/IngestCommandTest.php`, ns rewrite. `IngestCommandTest` `getPackageProviders` → `TeleframeServiceProvider::class`, its `config('teleclient.bus.group')` assertion → `config('teleframe.bus.group')`. Add phpunit suites `Bus` (`tests/Bus`) and `Console` (`tests/Console`).
- [x] **Step 4:** `vendor/bin/phpunit tests/Bus tests/Console` + `composer verify`. Note: `HotReloadRouter` ports as-is; Phase 4 deletes it (not this phase); its test rides along unchanged.

**Gate:** Bus suite green (stream encode/decode, route table, hot-reload, ArrayRedis-backed adapter + IngestConsumer + command), `composer verify` green, commit.

---

### Task 4: Daemon module (Daemon, AccountWorker, AccountPoller, WorkerInterface)

**Files:**
- Move+rename: `src/Teleframe/Daemon/*` (4 files)
- Move+rename: `tests/Daemon/*` (2 files)
- Modify: `phpunit.xml.dist` (add suite `Daemon`)
- Provider: none extra (AccountWorker stays container-free and plain-PHP constructible; the scope resolver arrives with Backfill in Task 5)

**Interfaces:** `AccountWorker` ctor seams (scopeFactory closure, sleep, resume cursor) unchanged. Consumes teleframe `Core\Services\UserAccountScope`, `Core\{Contracts\UpdateSinkInterface,Exceptions\{DcMigrationException,Rpc\FloodWaitException},MTProto\SessionData}`, `Laravel\Services\{TeleframeClient,UpdatePollerService}` — all already teleframe namespaces (no edit beyond the namespace prefix). Produces `MeRezaRezaei\Teleframe\Daemon\*`.

- [x] **Step 1:** Copy `src/Daemon/*` → `src/Teleframe/Daemon/`, ns rewrite to `MeRezaRezaei\Teleframe\Daemon\` (teleframe-core imports already correct).
- [x] **Step 2:** Move `tests/Daemon/*`, ns rewrite. Add phpunit suite `Daemon` (`tests/Daemon`).
- [x] **Step 3:** `vendor/bin/phpunit tests/Daemon` + `composer verify`.

**Gate:** Daemon suite green (supervision loop, flood-wait backoff, DC-migration rebuild, stop semantics), `composer verify` green, commit.

---

### Task 5: Backfill module (BackfillWorker, FetchQueue, BackfillCommand)

**Files:**
- Move+rename: `src/Teleframe/Backfill/*` (2 files)
- Move+rename: `src/Laravel/Console/BackfillCommand.php`
- Move+rename: `tests/Backfill/*` (2 files), `tests/Console/BackfillCommandTest.php` (Ruling B)
- Modify: provider — `SCOPE_RESOLVER_KEY` (reads `teleframe.daemon.accounts`, uses `AccountWorker::buildLiveScope`), `INGESTER_KEY` (default batch writer); command signature `teleframe:backfill`
- Modify: `phpunit.xml.dist` (add suite `Backfill`; `Console` suite already exists)

**Interfaces:** Produces `MeRezaRezaei\Teleframe\Backfill\*`. `BackfillCommand::{SCOPE_RESOLVER_KEY,INGESTER_KEY}` stay as bind keys host apps can override. Consumes `Daemon\AccountWorker` (Task 4) for the scope resolver and `Ingest\UpdateIngestor` (Task 2) as the default ingester.

- [x] **Step 1:** Copy `src/Backfill/*` → `src/Teleframe/Backfill/`, ns rewrite to `MeRezaRezaei\Teleframe\Backfill\`.
- [x] **Step 2:** `BackfillCommand` → `MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand`, `$signature = 'teleframe:backfill'`, `$aliases = []` (drop `telegram-client:backfill`); constants unchanged as bind keys. Provider binds `SCOPE_RESOLVER_KEY` (callable(int): UserAccountScope over `teleframe.daemon.accounts` + `AccountWorker::buildLiveScope`) and `INGESTER_KEY` (default `UpdateIngestor` batch writer), both reading `teleframe.*` config.
- [x] **Step 3:** Move `tests/Backfill/*` (ScriptedFetch/SleepRecorder support already in tests/ from Task 1) + `tests/Console/BackfillCommandTest.php` (getPackageProviders → `TeleframeServiceProvider`), ns rewrite. Add phpunit suite `Backfill` (`tests/Backfill`).
- [x] **Step 4:** `vendor/bin/phpunit tests/Backfill tests/Console` + `composer verify`.

**Gate:** Backfill suite green (fetch loop, budget/flood caps, scripted-history scope command), `composer verify` green, commit.

---

### Task 6: Backup module (VaultInterface, VaultCrypto, InMemoryVault, TelegramVault, BackupRunner, Chunker, Pruner, Restorer, Verifier, BackupCommand)

**Files:**
- Move+rename: `src/Teleframe/Backup/*` (9 files)
- Move+rename: `src/Laravel/Console/BackupCommand.php`
- Move+rename: `tests/Backup/*` (11 files), `tests/Console/BackupCommandTest.php` (Ruling B)
- Modify: provider vault factory (`teleframe.backup.driver` memory|telegram, telegram path reuses the Task 5 scope resolver); `LiveVaultSmokeTest` env gate → `TELEFRAME_LIVE=1`
- Modify: `phpunit.xml.dist` (add suite `Backup`; `Console` suite exists)

**Interfaces:** `VaultInterface`/`VaultCrypto` run on ext-sodium (arrived in Task 1 composer + CI — gap constraint 3 clears here by proof). `TelegramVault::forScope($scope, $setId)` uses teleframe `Core\Services\UserAccountScope`. Produces `MeRezaRezaei\Teleframe\Backup\*`.

- [x] **Step 1:** Copy `src/Backup/*` → `src/Teleframe/Backup/`, ns rewrite to `MeRezaRezaei\Teleframe\Backup\`.
- [x] **Step 2:** `BackupCommand` → `MeRezaRezaei\Teleframe\Laravel\Console\BackupCommand`, `$signature = 'teleframe:backup'`, `$aliases = []` (drop `telegram-client:backup`). Provider binds `VAULT_FACTORY_KEY` per teleclient logic (memory driver → per-set `InMemoryVault` singleton; telegram driver → resolve scope via Task 5 resolver, `TelegramVault::forScope`), reading `teleframe.backup.*` config.
- [x] **Step 3:** Move `tests/Backup/*` (FakeVaultApi/FakeUserScope support already in tests/ from Task 1) + `tests/Console/BackupCommandTest.php` (getPackageProviders → `TeleframeServiceProvider`; drop the teleclient provider imports), ns rewrite. Rename the live smoke env gate `TELECLIENT_LIVE` → `TELEFRAME_LIVE`. Add phpunit suite `Backup` (`tests/Backup`).
- [x] **Step 4:** `vendor/bin/phpunit tests/Backup tests/Console` + `composer verify`. Confirm `VaultCryptoTest` proves sodium round-trips on the CI matrix (sodium extension active since Task 1).

**Gate:** Backup suite green (crypto round-trip on ext-sodium, chunker, pruner, restorer, verifier, in-memory + live-gated vaults), `composer verify` green, commit.

---

### Task 7: Smoke proof + docs + archival + roadmap/spec ticks (+ full gate)

**Files:**
- Modify: `bin/standalone-smoke.php` (extend — the public face moved in Task 2, this task only extends the proof)
- Modify: `tests/Standalone/PlainPhpLoadTest.php` (align with all-module smoke)
- Modify: `docs/{index,quickstart,ingest,bus,backup}.md` (ported from teleclient)
- Modify: `docs/superpowers/plans/2026-09-07-master-roadmap.md` (Phase 2 ticks)
- Modify: `docs/superpowers/specs/2026-09-07-teleframe-unification-design.md` (status line)
- Modify: `docs/superpowers/specs/2026-09-07-framework-layers-gap-analysis.md` (constraint 3 closed note)
- Archive commit on teleclient repo (README banner + docs note) — no teleframe file movement here (face already in Task 2)

**Interfaces:** Face as of Phase 2 keeps the teleclient vocabulary (`ingest`, `ingestResponse`, `user`, ...) — Phase 3 composes the `Teleframe` facade. Standalone smoke proves plain-PHP construction of ALL modules with production deps only.

- [x] **Step 1: Standalone smoke — the plain-PHP gate.** Rewrite `bin/standalone-smoke.php` to construct, with NO Laravel booted: `UpdateIngestor` (+ injected fake dispatcher), `RouteIdempotency`, `PayloadWalker`, `IdentityLock`, `Events\UpdateStored` (plain DTO check), the public `Teleclient` (with the two ingestor singletons), `StreamSchema`, `RouteTable`, `RedisStreamSink` (with an in-memory `RedisConnectionContract` stub), `BackfillWorker`/`FetchQueue` (scripted fetch), `VaultCrypto`/`Chunker`/`InMemoryVault`/`BackupRunner`/`Pruner`/`Restorer`/`Verifier`, `SchemaRegenerator::loadScheme` + `TeleframeSchemeLoader::parseString`, and `AccountWorker` (injected scopeFactory + sleeper closures — no live wire). Exit 0 = gate proven. Keep `tests/Standalone/PlainPhpLoadTest.php` in lockstep so the phpunit Standalone suite mirrors the bin.
- [x] **Step 2: Docs.** Port teleclient `docs/{index,quickstart,ingest,bus,backup}.md`; rename `teleclient:`, `config('teleclient')`, `TELECLIENT_*`, `MeRezaRezaei\Teleclient\` references to their teleframe equivalents; add a "merged from teleclient (archived)" note. Engine docs/AGENTS full refresh stays Phase 4 per roadmap.
- [x] **Step 3: Archive teleclient repo.** Commit an ARCHIVED notice on teleclient `main` (README banner + docs note: "superseded by merezarezaei/teleframe — archived after Phase 2 module merge; code + history + `src-compat/` stay for provenance"). Do NOT delete the repo.
- [x] **Step 4: Ticks.** Toggle both Phase 2 checkboxes in `2026-09-07-master-roadmap.md`; update the spec status line in `2026-09-07-teleframe-unification-design.md` to Phase 2 COMPLETE (and the §5 row if it carries a status); add a closed note to `2026-09-07-framework-layers-gap-analysis.md` constraint 3 (ext-sodium proven in VaultCrypto suite in-repo).
- [x] **Step 5: Full gate.** `composer verify` (all default suites), plus `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg` on a PG-17 box, `php bin/standalone-smoke.php` exit 0, `php bin/regenerate` reproduces `generated/` byte-identically. Commit + push teleframe `main`.

**Gate:** Phase 2 gate — single package, both suites green inside teleframe, plain-PHP construct of every module proven (`standalone-smoke.php` exit 0), teleclient archived, roadmap + spec ticked.

---

## Verification Matrix

| Check | Command | Must be |
|---|---|---|
| Full suite (sqlite matrix) | `composer verify` (test + stan) | 0 failures |
| Schema regen idempotent | `php bin/regenerate --ship` then `git status --porcelain generated migrations` | clean |
| Module construct proof | `php bin/standalone-smoke.php` | exit 0 |
| Postgres track | `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg` (PG 17) | green |
| CI | push triggers matrix + pg-tests (from Task 2) | green |
| Regex-free moved src | phpstan disallowed-calls (no new `allowIn`) | no violations |
| Redis stream values | `StreamSchema` consts unchanged (`teleclient` GROUP) | no diff |