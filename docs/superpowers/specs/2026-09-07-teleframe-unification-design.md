# Teleframe Unification Design (Approach A — Nutgram Seams, Native Modules)

**Status:** Approved 2026-09-07 (user decisions folded in verbatim) · Phase 0 (Standalone Foundation) **COMPLETE** 2026-09-07 — 5 tasks landed, both gates green, `bin/standalone-smoke.php` exit 0. · Phase 1 (Schema Upgrade Pipeline) **COMPLETE** 2026-09-07 — unified `teleframe:schema-update` (diff + regenerate + stamp, NO migrate), `Teleframe::schemaLayer(): int`, composer `extra.telegram-layer=229`, skill v2, `schema-manifest.json` stamped layer 229, `cacheSalt()` primitive for Phase 5d. · Phase 2 (Module Merge) **COMPLETE** 2026-09-08 — 7 tasks, 6 commits; Ingest/Bus/Daemon/Backfill/Backup moved into `MeRezaRezaei\Teleframe\*`, plain-PHP smoke 24/24, `ext-sodium` proven (`VaultCrypto` suite in-repo), teleclient archived. · Phase 3 (Handler Substrate) **COMPLETE** 2026-09-08 — handler-as-data registry + 26-line onion + `onMessage` + `EchoEliminator` (Q2d) + `FakeDispatcher` running-mode surface + `Teleframe` facade compose-not-own; both intake rows (event + bus) collapse into ONE pipeline (friction I.1 dissolved); plain-PHP smoke 30/30; 656 tests green. · Phase 4 (Laravel Thin Bridge) **COMPLETE** 2026-09-08 — dead code deleted (`HotReloadRouter`, unwired), Packagist metadata landed (homepage/support/keywords, no path repos, `extra.laravel` final), README/llms.txt/AGENTS rewritten to the single-package layout, bus docs de-wired (`tg:bus:reload` = wake-up nudge only); gates green — 651 tests/13333 assertions, phpstan clean, smoke exit 0, PG 7/49; laravel + plain-PHP facade parity proven. · Phase 5a (Uprate Router + Loop Prevention) **COMPLETE** 2026-09-08 — sscanf arg receipt (`/start %s` → `string $arg` DI param, `HandlerMatch{handler,args}`), deterministic `Update::updateId()` + `ReplayDedup` middleware (psr-16 `teleframe.handler.seen`, `seenReplays` counter), opt-in `ValidatedUpdate` (FormRequest analog, zero failure magic per Q6a); replay-dedup proven by test, self-echo eliminated with mirror intact. · Phase 5b (Identity & Laravel Bindings) **COMPLETE** 2026-09-08 — Q8 package-owned `tl_user_bindings` (nullable morph, `contact_lost`, no cascade, app-owned migration exempted in ShipDialGolden), Q7 `findTF` (primary-account default + accountId override, tenancy-preserving), `HasTelegram`/`HasUserTelegram`, Q9 TelegramChannel via `routeNotificationForTelegram()` + default-sender fallback, Q10 `tg-webapp` + `tg-session` guards; replayed initData rejected. · Phase 5c (Keyboard Objects) **COMPLETE** 2026-09-08 — Q15 HMAC-signed 64B callback (`v1:<keyId>:<keyIdx>:<arg>:<sig10B>`, msg+chat MAC-bound, msg-wildcard pre-send binding), Q16 content-hash menu ids + version-byte rotation, `MenuRouter` expiry via `answerCallbackQuery`; forge/replay/relay vectors all rejected. · Phase 5d (Message Templates) **COMPLETE** 2026-09-08 — Q13 plain-PHP templates + zero-regex `EntityParser` spans adapter over the core entity shape, Q14 `MessageCompiler` file-caches compiled entity plans salted by `SchemaLayer::cacheSalt()` (layer bump invalidates without mtime change — proven), `message(name, data)` finder, MethodRegistry sendMessage plan typecheck; media/album closure fallback never cached. · Phase 5e (Stage Machine) **COMPLETE** 2026-09-08 — declarative `StageSet` + `StageRegistry`, Q17 plain-array state in PSR-16 (filesystem-backed, restart-proof proven via subprocess), Q18 `TelegramContext::inStageFlow` flag, Q20 in-process `Request::create` final submit to the SAME route, F5 precedence echo→stage→keyboard→handler slotted in the onion, template-shaped per-stage failures, dual-leg `StageFormRequest` (same rules for web + telegram). · Phase 5f (Bot Map) **COMPLETE** 2026-09-08 — Q19 getMyCommands probe + manual capability manifest (cached); `BotMap::for('x')->command()` fluent invocation, tokens DECRYPTED from the Backup vault seam on resolve, `BotTransport` bot-on-bot transport, authenticated outbound HTTP exposure + `Route::telegramBotApi` macro. · Phase 5g (Mini-App Hosting) **COMPLETE** 2026-09-08 — Q12 publishable stubs (`vendor:publish --tag=teleframe-miniapp`): Blade host + zero-build `telegram-web-app.js` bridge + Vue example SFC + theme CSS vars (all 10 `--tg-theme-*`) + Vite preset + package.json; guard→binding→User auth proven (valid init-data authenticates bound user; forged/replayed rejected).
**Related research:** handler/coupling deep-dive (2026-09-07 session), Nutgram graphify analysis (`/tmp/opencode/nutgram/graphify-out/GRAPH_REPORT.md`)
**Upstream vision:** `2026-09-07-teleframe-vision-verbatim.md` — the owner's full framework vision (uprate routing, loop prevention, Laravel-layer inheritance, keyboard objects, message templates, stage machine). This unification spec builds the SUBSTRATE; the vision's framework layers become Phase 5+ specs, each with its own gap/friction-mining brainstorm per the owner's planning rule.

## 1. Goal

One package — `merezarezaei/teleframe` — containing the MTProto/Bot engine AND the
client capabilities (Ingest truth mirror, Bus, Daemon, Backfill, Backup, Schema
pipeline), usable from a Laravel app AND from plain PHP with no framework, exposed
to developers as one composed facade class. Internal concerns stay separated as
modules; external surface is one class call.

## 2. Decisions ledger (binding — argued from user rulings)

| # | Decision | Source |
|---|---|---|
| D1 | Handler approach: subscription API on our pipeline (`onMessage()`-style first; Nutgram-style `onCommand`/middleware sugar layered later, non-breaking) | user: "one class call for dev people" + option (b) |
| D2 | Adopt Nutgram's SEAMS (PSR-3/11/16/18, delegate container, null-object defaults, handler-as-data, boundary mocking); REJECT its runtime model (in-process, no durability) | user: patterns-not-architecture agreement |
| D3 | `Artisan::call('migrate')` inside ingest is a **bug**, not a feature. Migrations are explicit, developer-run | user: "the migration call is a bug not a feature" |
| D4 | Schema upgrades are a **manual developer-run pipeline** (never automatic at runtime); apps using a slice of the API may skip layers | user ruling |
| D5 | Every package/manifest **declares which Telegram schema layer it speaks** (surface `schema-manifest.json` layer via API + composer extra) | user ruling |
| D6 | AI-assisted schema upgrades (AI reads Telegram changelog → adjusts) is FUTURE scope, enabled by the repo skill file, not built now | user ruling, YAGNI |
| D7 | Engine is sound (zero engine-logic defects in inventory); all found defects are wiring/entry/manifest. Therefore **Approach A** — renovate seams, never rewrite engine | defect inventory + user: "there must be something wrong… you should have noticed" — nothing was |
| D8 | DB-first pipeline is the contract: update → mirror rows → models out → event carries ready models → response routing (`ingestResponse` + `tl_route_*`) delivers call results to the caller | user's architecture description, already implemented and tested |

## 3. Target architecture

```
                        ┌─────────────────────────────────────────────┐
                        │  Teleframe (facade, composes — never owns)  │
                        │  ingest/ingestResponse/onMessage/run/migrate│
                        └──────┬───────────┬──────────┬───────────────┘
                               │           │          │
   PSR-11 delegate container ──┤           │          ├── RunningMode-style sources
   (Laravel app OR self)       │           │          │   (poll/webhook/fake)
                               ▼           ▼          ▼
 ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐
 │ Ingest  │  │  Bus    │  │ Daemon  │  │Backfill │  │ Backup  │  │ Schema  │
 │ (mirror)│  │(streams)│  │(superv.)│  │         │  │ (vault) │  │pipeline │
 └────┬────┘  └────┬────┘  └─────────┘  └─────────┘  └─────────┘  └────┬────┘
      │            │                                            generated:
      ▼            ▼                                            migrations/models/
 PostgreSQL truth (idempotent, advisory locks)     Redis at-least-once   DTOs per layer
```

- **Facade composes, modules own.** The dev-facing class delegates to modules;
  no module logic lives in it (avoids Nutgram's trait-stack at our scale).
- **PSR seams with null-object defaults** per module: logger (PSR-3, NullLogger),
  cache (PSR-16, array), container (own minimal, `delegate()` accepts Laravel).
- **Engine untouched:** MTProto wire, pts state machine, batching demux,
  idempotent ingest, advisory locks, 3-strike DL, vault crypto move as-is.

## 4. Correction to earlier research (accuracy note)

`TelegramGapDetected::dispatch()` (teleframe) is **already guarded** —
`class_exists(Event::class) && Event::getFacadeApplication()` + try/catch
(`src/Laravel/Events/TelegramGapDetected.php:46-54`). It does not fatal standalone;
it **silently drops** signals. The genuinely fatal standalone defects are all
teleclient-side: `UpdateStored` imports `Illuminate\Foundation\*`/`Queue\*`
(not in prod deps — class cannot even autoload), `event()` at
`UpdateIngestor.php:223`, `now()` at `UpdateIngestor.php:385` +
`RouteIdempotency.php:128-129`, `Artisan::call('migrate')` at
`UpdateIngestor.php:110`.

## 5. Phases (each ends green; each gets its own writing-plans plan)

| Phase | Scope | Repo |
|---|---|---|
| **0 — Standalone Foundation** | Kill fatal seams: UpdateStored → plain DTO + injected dispatcher; kill `Artisan::call('migrate')`; kill `now()` helpers via injected clock; add teleframe poller signal sink (gap/resync observable standalone); composer manifest becomes true; plain-PHP smoke test. | teleclient + teleframe |
| **1 — Schema Upgrade Pipeline** | Unify the two schema flows into the developer-run upgrade command; `schemaLayer(): int` API; composer `extra.telegram-layer`; activate skill v2. | teleframe |
| **2 — Module Merge** | Move Ingest/Bus/Daemon/Backfill/Backup into `src/Teleframe/*` behind PSR seams, one module per task, teleclient tests move with their module. | teleframe |
| **3 — Handler Layer + Facade** | Handler-as-data registry, middleware onion (26-line chain), `onMessage` subscription wiring to `UpdateStored` + bus, single `Teleframe` facade composing modules. | teleframe |
| **4 — Laravel Thin Bridge** | Provider shrinks to delegate-container wiring + `extra.laravel`; verify Laravel + plain-PHP parity; delete dead code (`HotReloadRouter`, unwired hooks, src-compat). | teleframe |

Deferred to their own specs if demanded (YAGNI now): conversations system,
rate limiting, webhook exposure of the handler layer, AI-assisted upgrades.

## 6. Schema upgrade pipeline (Phase 1 detail — user's D4/D5 design)

1. Telegram publishes a new layer → developer decides to adopt.
2. `teleframe:schema-update` fetches/refreshes `.tl` sources (or reads a
   locally placed file), shows the layer diff (SchemaDiffer).
3. Regenerates, in order: methods-mtproto/botapi schemas, curated builders,
   skill files, RPC catalog, mirror metamodel → migrations/models/DTOs/factories.
4. Version stamp: `generated/schema-manifest.json` `layer` is the truth;
   `Teleframe::schemaLayer(): int` surfaces it; composer `extra.telegram-layer`
   records it at release time.
5. Developer applies migrations **manually** (`php artisan migrate` /
   explicit standalone command). Nothing ever migrates at runtime.
6. The repo skill `skills/telegram-schema-update/SKILL.md` (v1 written
   2026-09-07) documents the procedure for humans and AI agents; Phase 1
   updates it to the unified command (v2).

## 7. Non-goals

- No rewrite of ingest idempotency/locking semantics.
- No automatic runtime schema adoption.
- No second event vocabulary: `UpdateStored` stays THE stored-update event;
  teleframe gap/resync signals stay teleframe events (observable via sink).
- No Packagist publish until Phase 4 is green.
