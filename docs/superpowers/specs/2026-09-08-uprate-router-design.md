# Uprate Router + Loop Prevention (Phase 5a) — Design

**Parent:** `docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5a.
**Status:** Spec — owner gate = spec review only. RULINGS applied from `2026-09-07-framework-layers-gap-analysis.md` (pending: Q1–Q6 `§RULINGS`); nothing here re-opens decided rulings.

## 0. Principle: 5a-itself is already 60% shipped

Phase 3 (Handler Substrate, commit `4501521`) built most of this phase's
deliverables as the shared pipeline: `Update` uprate (Q4c two-stage), the
declarative `HandlerRegistry` (Q1a code-wins), zero-regex `HandlerMatcher`
(exact / `prefix*` / `*`, sscanf single `%s`), the 26-line `Pipeline`,
`UpdateDispatcher` (Q5a synchronous in-process), `EchoEliminator` loop
prevention (Q2d send-time registry, Q2c route-level escape hatch), and the
facade reply path with account context (`TelegramContext`, Q18).
Phase 5a closes the *deliverable* deltas and proves the roadmap gate:

| 5a deliverable | Status |
| --- | --- |
| Uprate object | ✅ Phase 3 `Update` (+ `fromBus`/`fromMirror`) |
| Declarative routes, zero-regex, sscanf params | ✅ `HandlerRegistry`/`HandlerMatcher` — **gate needs `/start {arg}` proof + documented arg-receipt** |
| Loop-prevention registry + elimination middleware | ✅ `EchoEliminator` (KEY `teleframe.handler.sends`) |
| Reply path with account context preserved | ✅ facade `TelegramContext`/`send` |
| Uprate identity / dedup key | ✅ SHIPPED — deterministic content-hash id + `ReplayDedup` middleware + `seen_replays` |
| Uprate validation object (FormRequest analog) | ✅ SHIPPED — `ValidatedUpdate` wrapper, DI-injected per handler |

## 1. RULINGS applied (gap doc §RULINGS)

- **Q1 — routes truth:** (a) in-code declarations; `HandlerRegistry` IS the
  truth; a compiled Redis artifact stays out (no compile step — array reads,
  process-scoped). Already enacted; documented as settled.
- **Q2 — elimination placement:** (d) send-time PSR-16 registry + (b) built-in
  default-on FIRST middleware, (c) per-route `onOwn` escape hatch. Enacted;
  staying.
- **Q3 — registry key two-phase:** random_id early, msg_id reconciled late.
  Registry the facade writes stores both when known.
- **Q4 — uprate construction:** two-stage (raw constructor match now, model via
  DI in handler). Enacted.
- **Q5 — execution model:** (a) synchronous in-process dispatch. Enacted.
- **Q6 — response contract:** (a) v1 handlers return `void`; explicit replies
  via facade; full Laravel `Response` reuse is the 5e contract. Enacted.

## 2. Deliverable DIFF: sscanf argument receipt

`HandlerMatcher` already returns the matched `Handler`; extend its contract so
the matcher result carries extracted sscanf args. Receipt happens
lazily inside `UpdateDispatcher` at dispatch time — the handler signature may
accept an optional second DI param. Non-binding:

```php
$teleframe->on('/start %s', function (Update $u, string $arg) { /* DI resolve */ });
$teleframe->on('/start %s', fn (Update $u) => $u->args ?? []);
```

Rules: exactly **zero or one** `%s`; more tokens rejected at
registration (exception, mirrors matcher contract); first-match-wins still
applies (route order + priority); zero-regex untouched.

## 3. Deliverable DIFF: uprate identity + dedup

Deterministic content-hash id on `Update`, stable across deploys and
processes:

```
updateId = sha1(accountId . "\0" . entryTs . "\0" . raw_json(updateArray))
```

- Derived from the *entry* (array + account + ts), NOT the handler result —
  so a re-ingested payload dedups regardless of handler mutability.
- First-class on `Update` as a lazy readonly string; `fromBus` seeds with the
  entry ts, `fromMirror` seeds from the stored model's `created_at`/id.
- **Replay dedup:** a framework middleware (after the echo eliminator, on the
  same onion) consults the same PSR-16 send-time registry key namespace
  (`teleframe.handler.seen`) and drops a second identical updateId within a
  TTL window (default from `teleframe.handler.dedup_ttl`, 60s). This is the
  roadmap gate's "replay dedup proven by test".
- Ops-facing: `UpdateDispatcher` counts `seen_replays` for the fake-based
  running-mode surface to assert.

## 4. Deliverable DIFF: uprate validation (FormRequest analog)

A thin `ValidatedUpdate` wrapper the *handler* requests via DI (it stays
purely opt-in — no overloaded magic):

```php
$teleframe->onMessage(function (Update $u, ValidatedUpdate $v) { $v->dump(); });
```

- `ValidatedUpdate::__construct(Update $update)` — resolved through the
  container with the active `Update` bound as its argument source (same
  binding the dispatcher already performs for `Update` params).
- Rules: plain-array validate `{from_, chat, message}` shape guardrails;
  zero failure magic (Q6a): `valid`/`errors`/`rules` and explicit facade
  helpers — no redirect, no auto-reply (5e deferred). Kept YAGNI-small; only
  the guardrail + explicit read APIs ship now.
- Future (Phase 5e) can promote this to a true controller contract; the
  wrapper surface is the compat seam.

## 5. Non-goals (deferred, non-breaking)

- Compiled Redis route artifact (Q1a stays in-code).
- `onCommand` / Nutgram sugar — stays deferred.
- Full Laravel `Response` reuse — the 5e contract.
- Message template / keyboard object integration (5c/5d).

## 6. Gate (roadmap)

- `/start {arg}`-style routing works zero-regex (registration + receipt both
  test-covered inside `tests/Handler`).
- Self-echo eliminated while the mirror still stores it (existing Phase 3
  coverage + arg test).
- Replay dedup proven by test (section 3) — duplicate entry → second dispatch
  skipped + `seen_replays` counts.
- Adopted into `docs/handlers.md` (validation + args + dedup sections).

## 7. Plan pointer

Execution plan: `plans/2026-09-08-phase5a-uprate-router.md` (spec-gate passed).