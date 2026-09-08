# Phase 3: Handler Substrate + Facade — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the dev-facing handler layer on top of the merged modules: a **handler-as-data registry** with a **middleware onion** (26-line chain), an **`onMessage()` subscription** wiring the two intake paths (Laravel `UpdateStored` event + Redis bus consumer) into one pipeline, the "update → my code ran" path (`onMessage`) with a **Fake RunningMode** testing surface, and a single composed **`Teleframe`** facade that exposes the whole package as one class. Dissolves gap frictions I.1 (two hook mechanisms), I.2 (docware bootstrap — no "update → my code ran" path), and I.3 (no testing surface).

**Architecture:** Per spec §2 D1/D2 and §3, the facade **composes, never owns** — it delegates to modules; no module logic lives in it. The handler substrate is a new module under `src/Teleframe/Handler/` (segment = module) exposing:
- `HandlerRegistry` — handler-as-data: each handler is a value object `{match, priority, handler}: PSR-11-invokable string|callable|array`, compiled to a flat ordered chain. Code wins, registry hash = cache (Q1).
- `UpdateDispatcher` — Nutgram-style middleware onion (the 26-line `array_reduce` pipeline) over a `CallableResolver` (ContainerContext-aware, Q18 container-bound `TelegramContext`).
- `Pipeline` (the 26-line onion) — `array_reduce($middleware, fn($next, $m) => fn($req) => $m($req, $next), $terminal)`.
- `Handler` value objects (handler-as-data), `HandlerRegistry::on($match, $handler)` (D1 `onMessage()`-style first; `onCommand`/Nutgram sugar deferred, non-breaking per D1).
- `Message` uprate DTO — two-stage (Q4): raw `_`/constructor-name match at the router, spatie/model hydration via DI in the handler. Carries `update:array`, `account_id:int`, `source:event|bus`, `self_originated:bool` verdict (Q2 d+b registry), ts.
- **Loop prevention (Q2)** — elimination registry + default-on built-in first middleware: a send-time registry (Q2d, written in the facade's send/dismiss) off which the first middleware eliminates self-echoed responses before fan-out; `self_originated` populated on the uprate (Q2b default-on).
- **Fake RunningMode** — a PSR-11-delegate `Testing\FakeDispatcher` / `FakeRunningMode` that, from a fixture `{update, account_id}` list, runs the real pipeline synchronously and records dispatched messages — proving "fake uprate flows the real pipeline" (the phase gate).
- `Teleframe` facade — single class composing `Ingest` (ingest/ingestResponse/user), `Bus`, `Handler` (on/run), `Daemon`, `Backfill`, `Backup`, `Schema` (schemaLayer). Replaces/extends the transitional `MeRezaRezaei\Teleframe\Teleclient` as the primary public face (non-breaking: `Teleclient` stays as the ingest-only sub-surface; `Teleframe` is the composite).

**Tech Stack:** PHP 8.2+, composer PSR-4, illuminate/{support,events,container}, PSR-11 container (delegate), PSR-3 logger (NullLogger), PSR-16 cache (array) — all already in teleframe. No new runtime deps. phpunit ^11, phpstan (level 5, `preg_*` ban).

**Spec:** `docs/superpowers/specs/2026-09-07-teleframe-unification-design.md` §2 (D1, D2, D8) and §3 (target architecture — the facade row; `onMessage` on the facade's public face). Gap: `docs/superpowers/specs/2026-09-07-framework-layers-gap-analysis.md` §I frictions 1-8 + Q-rulings Q1/Q2/Q4/Q5/Q6/Q18. Roadmap: `docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 3 (lines 44-48).

## Global Constraints

- `preg_*` stays banned in handler source: matching is exact-map + sscanf-style only (gap constraint 1). The `onMessage`/`on($match)` matcher uses the SAME `RouteTable`/`RouteMatcher` semantics, not regex.
- The facade composes; no module logic inside it (avoids Nutgram's trait-stack). `Teleframe` exposes the modules' own service classes via PSR-11 `__get`/delegate; it never re-implements ingest/bus/backup.
- Loop prevention uses the **registry** (Q2), NOT the `out` column verdict alone — `out=true` cannot distinguish framework-sent from human-typed-on-phone (gap §I "core subtlety"). The send-time registry is written by the facade's send/dismiss methods.
- Handler return: **v1 handlers return void; explicit replies via the facade** (Q6). The full Laravel `Response`-reuse endgame is Phase 5e, not this phase.
- Two-stage routing (Q4): the router matches raw `_`; models hydrate via DI in the handler from the container. Replay-safe (re-ingest of an identical update re-runs or is deduped at the ingress sink — unchanged, Phase 0 handled replay dedup at ingest).
- Gates after every task: `composer verify` green (all suites) + the task's own suite green. The phase gate specifically: **fake uprate flows the real pipeline in a test** (FakeRunningMode test drives the real `UpdateDispatcher`/middleware onion) + facade one-class DX documented in `docs/handlers.md` + all gates green.
- No new public commands in this phase (the wire/service artisan surface is already `teleframe:*` from Phase 2; `teleframe:daemon` stays a host-level concern per architecture). Optional: `teleframe:routes` list command only if friction I.4 shows a cheap win — gated, NOT in scope unless a task finds it trivially portable.
- The transitional `Teleclient` class stays byte-stable (public face, non-breaking); `Teleframe` is added alongside. No class/namespace removals in this phase (Phase 4 does the thinning).

---

## Namespace / Layout (rulings)

| Component | Namespace | Physical |
|---|---|---|
| `HandlerRegistry` | `MeRezaRezaei\Teleframe\Handler\HandlerRegistry` | `src/Teleframe/Handler/HandlerRegistry.php` |
| `Handler` (value object, handler-as-data) | `...\Handler\Handler` | `src/Teleframe/Handler/Handler.php` |
| `HandlerMatcher` | `...\Handler\HandlerMatcher` | `src/Teleframe/Handler/HandlerMatcher.php` |
| `Update` (uprate DTO) | `...\Handler\Update` | `src/Teleframe/Handler/Update.php` |
| `UpdateDispatcher` (middleware onion host) | `...\Handler\UpdateDispatcher` | `src/Teleframe/Handler/UpdateDispatcher.php` |
| `Pipeline` (the 26-line onion) | `...\Handler\Pipeline` | `src/Teleframe/Handler/Pipeline.php` |
| `EchoEliminator` (Q2 built-in first middleware) | `...\Handler\Middleware\EchoEliminator` | `src/Teleframe/Handler/Middleware/EchoEliminator.php` |
| `TelegramContext` (Q18 container-bound) | `...\Handler\TelegramContext` | `src/Teleframe/Handler/TelegramContext.php` |
| `Teleframe` facade | `MeRezaRezaei\Teleframe\Teleframe` | `src/Teleframe/Teleframe.php` |
| Fake RunningMode | `MeRezaRezaei\Teleframe\Testing\FakeDispatcher` (+ `FakeTelegramHttp` if reuse needed) | `src/Teleframe/Testing/` |
| tests | `MeRezaRezaei\Teleframe\Tests\Handler\*` | `tests/Handler/` |

`MeRezaRezaei\Teleframe\Teleclient` (Phase 2) stays as the ingest-only sub-surface. The facade merges the `onMessage` vertex from the spec §3 diagram onto `Teleframe`.

---

## Task 1: Handler substrate — registry, matcher, uprate, 26-line onion, echo elimination

**Creates:** `src/Teleframe/Handler/{Handler,HandlerRegistry,HandlerMatcher,Update,Pipeline,UpdateDispatcher,Middleware/EchoEliminator,TelegramContext}.php`, tests.
**Modifies:** (nothing external yet — facade arrives Task 2).

**Interfaces:** Consumes `MeRezaRezaei\Teleframe\Core\Contracts\UpdateSinkInterface` (unchanged sink contract), PSR-11 container (delegate). Produces `MeRezaRezaei\Teleframe\Handler\*`.

- [ ] **Step 1: `Handler` + `HandlerRegistry` (handler-as-data).** `Handler` value object holds `{match (string, exact|prefix@sscanf), handler ((callable|string|array) invokable, resolved lazily via container), priority:int, id (deterministic Q16 content-hash)}`. `HandlerRegistry` is `on(string $match, callable|string|array $handler, int $priority = 0): static` (return `$this` for fluency) + `all(): array` (flat, priority-sorted stable) + `compile(): array` (returns `array<Handler>` — code wins; no Redis compile artifact yet, kept in-memory per D2 pattern). Thread-safe append; registration is REVERSIBLE and ordered (first-match-wins like `RouteTable`, zero-regex, exact-map + sscanf single-`%s` token only).
- [ ] **Step 2: `HandlerMatcher`.** Given `Update::$_` (constructor name) + optional sscanf args, returns the single best (highest-priority, first-registered) matching `Handler` — identical semantics to `RouteTable::match` (exact first, then prefix) but over registration order. Pure, no I/O.
- [ ] **Step 3: `Update` uprate DTO.** `{array $array; int $accountId; string $source /* 'bus'|'event' */; ?int $ts; bool $selfOriginated = false; ?TlInstanceModel $model}`. **Two-stage (Q4):** `_` (constructor name) eager at construction (`fakeParsed(): string` from `$array['_']`); the mirrored `$model` is NOT hydrated here — it is resolved lazily via DI in the handler (Q4 route-model split). Immutable value object; `with(...)`/`from(...)`.
- [ ] **Step 4: `Pipeline` — the 26-line middleware onion.** `Pipeline::then(array $middleware, callable $terminal): callable` built with `array_reduce` — the canonical Nutgram-style onion, ~26 lines total (comments allowed here; the 26-line chain is a deliberate bounded artifact). Each middleware `($update, callable $next)`; a middleware may short-circuit (return a response/abort) or call `$next($update)`. Used by `UpdateDispatcher`.
- [ ] **Step 5: `UpdateDispatcher`.** Resolves `Handler::handler` via the container (CallableResolver for `[Class,method]`/`Class` invokable/closure), injects the container-bound `TelegramContext` (Q18) so a handler receives the account + update without globals, runs the onion: `[EchoEliminator, RouterMiddleware, HandlerTerminal]` by default. `dispatch(Update $u): null` — v1 handlers return void (Q6); results reach the caller only via façade send calls.
- [ ] **Step 6: `EchoEliminator` (Q2).** Default-on **first** middleware. Consumes the send-time registry (a PSR-16 `CacheInterface` array-backed default, promoted to redis in Laravel) of `{account_id, random_id, msg_id?, sent_at, route_that_sent_it?}` written by the facade's send path. If the uprate's `random_id`/`msg_id` matches a recent send record → mark `selfOriginated` and skip fan-out to handlers (elimination), unless the route declared the escape hatch (`HandlerRegistry::on(..., ['onOwn' => true])`). Mirror stays truth-complete (elimination happens at route level, Q2(b) default-on placement + Q2(c) route-level).
- [ ] **Step 7: tests.** `tests/Handler/HandlerRegistryTest` (register/order/priority/duplicate-id determinism), `HandlerMatcherTest` (exact/prefix/sscanf-first-wins/no-regex), `UpdateTest` (two-stage `_` parse, immutability, `selfOriginated` default false), `PipelineTest` (onion order, short-circuit, terminal reach), `UpdateDispatcherTest` (CallableResolver with an injected array container, DI model hydration invoked lazily via a spy), `EchoEliminatorTest` (self-echo eliminated via registry; onOwn escape hatch runs).
- [ ] **Step 8: phpunit suite + gate.** Add suite `Handler` (`tests/Handler`) to `phpunit.xml.dist`. Run `vendor/bin/phpunit tests/Handler` + `composer verify`.
- **Gate:** Handler suite green (registry order/priority, matcher first-wins, uprate two-stage, onion semantics, DI resolution, echo elimination + escape hatch), `composer verify` green, commit.

## Task 2: `onMessage` subscription — wire event + bus intake into one pipeline, Fake RunningMode

**Creates:** `src/Teleframe/Handler/Subscriptions/UpdateStoredSubscription.php` (+ `Listener` if Laravel event wiring needed), `src/Teleframe/Testing/FakeDispatcher.php`, `tests/Handler/OnMessageTest.php`, `tests/Handler/FakeRunningModeTest.php`.
**Modifies:** `src/Laravel/Providers/TeleframeServiceProvider.php` (bind `UpdateDispatcher` singleton + `HandlerRegistry` singleton, register the UpdateStored→dispatcher listener), `Teleframe` facade is NOT here (Task 3).

**Interfaces:** Consumes `MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored` (Phase 2, plain DTO + injected dispatcher — disposable in plain PHP), `MeRezaRezaei\Teleframe\Bus\IngestConsumer` (Phase 2) + `RouteTable` for the bus path. Produces the `onMessage` entry points per D1 (subscription API on our pipeline).

- [ ] **Step 1: `Subscriptions` — the two intake paths collapse to one.** Both the Laravel `UpdateStored` event AND the bus consumer's matched entries become `Update` uprate DTOs fed into the SAME `UpdateDispatcher`. For the Laravel path: a listener that receives `UpdateStored` (root model + account) → builds `Update` → `dispatcher->dispatch()`. For the bus path: `IngestConsumer` already routes unmatched→ingest and matched→target-stream; additionally the consumer's matched path is where routed entries fan to handlers (via a new `HandlerSink` implementing `UpdateSinkInterface`) so a single payload shape serves bus + event (friction I.1 dissolved — one payload, two transport rows, one pipeline).
- [ ] **Step 2: `onMessage()` sugar.** `HandlerRegistry::onMessage(callable|string|array $handler, int $priority = 0)` = `on('*', ...)` default catch-all (matches any constructor). Kept minimal (D1: `onMessage()`-style first; `onCommand`/Nutgram sugar deferred, non-breaking). Provider binds the registry as a singleton so both intake paths share one handler table.
- [ ] **Step 3: Fake RunningMode — the gate surface.** `Testing\FakeDispatcher`: constructor takes `(array $fixtures /* list<array{update,account_id,source}> */, ?array $registry = null, ?ContainerInterface $container = null)`. `run()` feeds each fixture `Update` through the REAL `UpdateDispatcher` (real registry, real `Pipeline`, real `EchoEliminator`) and records every dispatched handler invocation + any facade send calls (`public array $dispatched`, `public array $sent`). This is boundary mocking per D2 — the engine's real pipeline runs, only the transport (redis/network) is faked. No live wire, no Laravel boot required (plain-PHP constructible).
- [ ] **Step 4: Wire the Laravel path in the provider.** `singleton(HandlerRegistry::class)`, `singleton(UpdateDispatcher::class, fn)`, and in `boot()` conditionally register the `UpdateStored` listener when the event class is autoloadable (the Phase-0 pattern — `class_exists(Illuminate\Events\Dispatcher::class)`) so the plain-PHP path never fatals. Consumer className string `...\Bus\IngestConsumer` already resolves (Phase 2).
- [ ] **Step 5: tests.** `OnMessageTest` (register via `onMessage`, dispatch an `Update` through the real onion, assert handler ran), `FakeRunningModeTest` (feed fixtures → real pipeline ran, dispatched recorded, self-echo eliminated, priority ordering honored, DI model lazily hydrated from an array container), a plain-PHP `tests/Standalone/PlainPhpLoadTest` addition proving `FakeDispatcher` + `UpdateDispatcher` construct with no Laravel. Add suite `Handler` already added in Task 1 — extend it.
- [ ] **Step 6: gate.** `vendor/bin/phpunit tests/Handler tests/Standalone` + `composer verify`.
- **Gate:** `onMessage` runs a real (not stubbed) pipeline from a plain PHP FakeDispatcher fixture (the exact phase-gate assertion lives in `FakeRunningModeTest`), both intake rows share one pipeline (friction I.1), `composer verify` green, commit.

## Task 3: `Teleframe` facade — one class composes all modules

**Creates:** `src/Teleframe/Teleframe.php`, `tests/Handler/TeleframeFacadeTest.php`, `docs/handlers.md`.
**Modifies:** `src/Laravel/Providers/TeleframeServiceProvider.php` (register facade singleton), `docs/index.md`/`docs/quickstart.md` (facade as primary DX), `bin/standalone-smoke.php` (construct the facade plain-PHP).

**Interfaces:** `Teleframe` composes (never owns): `Ingest\UpdateIngestor`, `Ingest\EntityAggregator`, `Bus\RouteTable`, `Handler\HandlerRegistry`/`UpdateDispatcher`, `Schema` (`schemaLayer()`), `Daemon\Daemon`, `Backup` vault factory. Delegates via PSR-11 `ContainerInterface` + explicit `__call` forwarding to the composed singletons. The spec §3 facade public face: `ingest/ingestResponse/onMessage/run/schemaLayer` (+ passthrough `user/channel/chat`, `route()`, `backup()`).

- [ ] **Step 1: `Teleframe` facade class.** Constructor `(ContainerInterface $container)` or explicit composed services; exposes:
  - `ingest(array $update, int $accountId): TlInstanceModel` → delegates `UpdateIngestor`
  - `ingestResponse(...)` → delegates (unchanged D8 response routing)
  - `onMessage(callable|string|array $handler, int $priority = 0): static` → `HandlerRegistry::onMessage`
  - `run(array $fixtures): array` → Fake/real dispatch; plain-PHP test entry
  - `user/channel/chat(int $accountId, int $tgId)` → `EntityAggregator`
  - `route(string $match, string $target)` → `RouteTable::set`
  - `backup(): string`-factory accessor + `schemaLayer(): int` passthrough
  - `__call` → resolves the composed service method by name (one hop, never module logic)
  - `send(...)`/`dismiss(...)` verbose helpers that ALSO write the Q2 send-time registry (the elimination data the `EchoEliminator` reads) — the one cross-module glue the facade legitimately owns, kept thin.
- [ ] **Step 2: provider binding.** `singleton(Teleframe::class, fn ($app) => new Teleframe($app))` (PSR-11 Laravel container). No other wiring needed — facade composes existing singletons.
- [ ] **Step 3: docs — the one-class DX.** New `docs/handlers.md`: recipe-driven (subscribe `onMessage`, route by constructor, DI-hydrate the mirrored model, send a reply via facade, run a test with FakeDispatcher). Update `docs/index.md` + `docs/quickstart.md` to lead with `Teleframe` as the primary face, note `Teleclient` (ingest-only) retained non-breaking. This satisfies the roadmap gate "facade one-class DX documented."
- [ ] **Step 4: smoke + tests.** Extend `bin/standalone-smoke.php` with: construct `Teleframe` (plain PHP), `onMessage`, run one fixture through the real pipeline, assert the handler fired. `tests/Handler/TeleframeFacadeTest.php`: facade delegates to real module singletons (ingest→model, user→aggregator, route→table, onMessage→registry, run→dispatcher), `__call` forwarding, send writes the elimination registry + EchoEliminator consumes it. `tests/Standalone/PlainPhpLoadTest.php` adds facade construct.
- [ ] **Step 5: gate.** `vendor/bin/phpunit tests/Handler tests/Standalone` + `composer verify` + `php bin/standalone-smoke.php` exit 0.
- **Gate:** facade one-class DX documented (`docs/handlers.md`), facade delegates to REAL module singletons in tests, plain-PHP construct proven, `composer verify` green, commit.

## Task 4: Phase gate + roadmap tick

**Modifies:** `bin/standalone-smoke.php` (final), `docs/superpowers/plans/2026-09-07-master-roadmap.md` (Phase 3 gate tick), `docs/superpowers/specs/2026-09-07-teleframe-unification-design.md` (Phase 3 status line).

- [ ] **Step 1: Full gate.** `composer verify` green; `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg` green (where env-gated on this box); `php bin/standalone-smoke.php` exit 0 (now including the facade); `php bin/regenerate` idempotent (handlers add no generated surface, cheap re-check). The definitive phase assertion: `FakeRunningModeTest` proves a fake uprate flows the REAL pipeline (registry + onion + echo elimination + DI hydration) — no handler-substrate stub.
- [ ] **Step 2: roadmap + spec ticks.** Tick Phase 3 "Write plan" + "Gate: fake uprate flows the real pipeline in tests; facade one-class DX documented; gates green" checkboxes; append `Phase 3 (Handler Substrate) COMPLETE 2026-09-0X` to the unification spec status line with the gate evidence.
- [ ] **Step 3: commit + push** teleframe `main`; update Nowledge Mem (memory `97c4c8af-f18a-4b6f-89e7-551f8ed2c1aa`) with Phase 3 completion.
- **Gate:** all checkboxes ticked, worktree clean, `main` pushed, phase-gate assertion green, owner reviews the RESULT (per roadmap rule 4).

---

## Out of Scope (YAGNI — deferred per spec §5)

- Nutgram-style `onCommand`/`onCallbackQuery` sugar (D1: layered later, non-breaking).
- Full Laravel `Response` reuse as handler return (Q6 → Phase 5e).
- Conversations/stage machine, rate limiting, webhook exposure of the handler layer (spec §5 deferred list).
- Redis compile artifact for routes (Q1 hash = cache stays in-memory this phase; php `route:cache`-style artifact is Phase 5e surface).
- Deleting dead code / `HotReloadRouter` (Phase 4 does the thinning).
- Keyboards/templates/stages/bot-map substrate (Phase 5c-f, ruled Q13-Q20).
