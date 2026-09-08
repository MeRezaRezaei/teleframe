# Phase 5a — Uprate Router + Loop Prevention (execution plan)

**Parent:** `specs/2026-09-08-uprate-router-design.md` (owner-gated) +
`docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5a.
**Created:** 2026-09-08. **Depends on:** Phase 4 (commit `f3aa9f1`).

## Tasks

- [x] **Task 1: sscanf arg receipt.** `HandlerMatcher` match result carries
      extracted sscanf args; `UpdateDispatcher` passes them lazily as a DI
      `string` or exposes `Update::$args`. Registration rejects >1 token.
      Tests: `/start {arg}`-style routing zero-regex (register + receipt),
      registration rejection, first-match-wins still intact.
- [x] **Task 2: uprate identity + replay dedup.** `Update::updateId()` =
      `sha1(accountId . "\0" . ts . "\0" . raw_json(array))`; new
      `Middleware/ReplayDedup` on the onion after the echo eliminator reading
      PSR-16 namespace `teleframe.handler.seen` (TTL `teleframe.handler.dedup_ttl`,
      default 60s); `UpdateDispatcher` counts `seen_replays`. Test: duplicate
      entry dispatched twice → second skipped, count incremented; distinct
      payloads never collide.
- [x] **Task 3: `ValidatedUpdate` (FormRequest analog).** Opt-in DI wrapper over
      `Update`; plain-array guardrails on `{from_, chat, message}` + explicit
      `valid`/`errors`/`rules`; no failure magic. Tests: accepts a well-formed
      update, rejects malformed without throwing, resolves through the
      container in `FakeRunningModeTest`-style dispatch.
- [x] **Task 4: docs.** `docs/handlers.md`: args receipt, validation wrapper,
      dedup + `seen_replays`, updateId reference.
- [x] **Task 5: gate.** `composer verify` exit 0 (phpstan clean), smoke exit 0,
      `TELEFRAME_PG=1 tests/Pg` green; roadmap Phase 5a items ticked; spec
      status line updated; commit + push `main` (coordinator commits).

**Gate:** `/start {arg}` zero-regex routing proven; self-echo eliminated with
mirror still storing (extant); replay dedup proven by test; docs adopt it.