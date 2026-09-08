# Phase 4 — Laravel Thin Bridge (packaging + docs landing)

**Parent:** `plans/2026-09-07-master-roadmap.md` — Phase 4 gate.
**Created:** 2026-09-08. **Depends on:** Phase 3 complete (commit `4501521`).

## Goal

Ship-ready surface. The provider is already a delegate-container (binding-only);
this phase lands the remaining deliverables on the roadmap gate:
composer publish metadata, dead code deleted (`HotReloadRouter` — unwired), and
the stale engine-era docs rewritten to the current single-package layout.

## Tasks

- [x] **Step 1: delete dead code `HotReloadRouter`.** The class is unwired in
      production (no consumer, command, or provider references it; the route
      table is re-read per stream entry, so the reload channel is vestigial).
      Delete `src/Teleframe/Bus/HotReloadRouter.php`, `tests/Bus/HotReloadRouterTest.php`,
      and the `test_reload_signal_swaps_routes_before_the_next_consume` case +
      import in `tests/Bus/IngestConsumerTest.php`. Keep the documented wire-key
      contract (`StreamSchema::RELOAD_CHANNEL` = `tg:bus:reload`, config
      `teleframe.bus.reload_channel`) — it is asserted by StreamSchemaTest and
      part of the ops-facing bus contract, not dead code.
- [x] **Step 2: Packagist publish readiness.** `composer.json`: add `homepage`,
      `support`, `keywords`; confirm no path/`repositories` entries; confirm
      `extra.laravel` shape (providers + aliases) is final; LICENSE file present.
- [x] **Step 3: docs refresh.** Rewrite `README.md` and `llms.txt` from the
      4-package teleproto monorepo narrative to the unified
      `merezarezaei/teleframe` (engine `src/{Core,Bot,Laravel,Schema}` +
      consumer modules `src/Teleframe/*` + handler pipeline + `Teleframe`
      facade + `teleframe:*` commands). Rewrite `AGENTS.md` (stale engine
      layout; new facts: src/Teleframe modules, Handler/Testing, zero-regex
      scope, gates).
- [x] **Step 4: bus docs de-wire.** `docs/bus.md` drops the `HotReloadRouter`
      / reload-channel fan-out wording (per-entry hash re-read is the mechanism);
      `docs/quickstart.md` recipe (g) and `docs/index.md` Bus row updated.
- [x] **Step 5: gate.** `composer verify` exit 0; `php bin/standalone-smoke.php`
      exit 0; Laravel-path facade test (`tests/Handler/TeleframeFacadeTest`) and
      plain-PHP facade test (`tests/Standalone/PlainPhpLoadTest`) both green —
      the roadmap gate "Laravel app + plain-PHP script both drive the identical
      facade". Commit + push `main`.

**Gate:** dead code gone (no `HotReloadRouter` references), publish metadata
complete, README/llms/AGENTS describe the real package, tests green, pushed.