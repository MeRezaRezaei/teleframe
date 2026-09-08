# Phase 5g — Mini-App Hosting (execution plan)

**Parent:** `specs/2026-09-08-miniapp-hosting-design.md` (this layer's spec) +
`docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5g.
**Created:** 2026-09-08. **Depends on:** Phase 5b (shipped) — `TgWebAppGuard`,
`TlUserBinding`, `VerifyMiniAppInitData`, `Bindings`, `IdentityConfig`; the
`tests/Identity/TestCase.php` harness is mirrored for the render/integration
proof.

## RULINGS enacted (gap doc §RULINGS)

- **Q12 (a):** ship publishable stubs — Vite preset + Blade host +
  `telegram-web-app.js` bridge + theme CSS vars, as package-published files
  under `src/Laravel/Stubs/miniapp/` (publish tag `teleframe-miniapp`).
- **hard constraint 7:** the Vue precedent does NOT exist in the repo — this
  phase BUILDS it; the roadmap gate is evidenced by stub files + a guard→
  binding→User integration test (no browser e2e, no Vite toolchain in CI).

## Rulings made here (autonomy protocol item 2)

- **Publish shape:** `vendor:publish --tag=teleframe-miniapp` copies the whole
  stub tree to the app root (dropping `resources/…`, `vite.config.js`,
  `package.json`). The provider register call is DOCUMENTED for the
  coordinator — `TeleframeServiceProvider.php` is on the worker's do-not-touch
  list, so no provider edit ships here.
- **Bridge is an ES module, zero build:** default export `() => ({ initData,
  user })` + named members, self-registered on `window.TeleFrameBridge`. The
  Blade host loads it via `<script type="module" src="asset('js/telegram-web-app.js')">`
  AND the example Vue app imports it — satisfying "document that the example
  Vue app uses it as an import" without a build step for the bridge itself.
- **Host is render-testable without a Vite manifest:** the built entry is
  referenced as `asset('build/miniapp.js')` (the preset emits exactly that via
  `entryFileNames: 'miniapp.js'`), so BladeHostTest renders the host using only
  the published stub + Testbench — `@vite` / manifest absence never throws.
- **Builder source is PHP-free:** all stub files are JS/CSS/Vue/JSON/Blade,
  so phpstan (`paths: [src]`) and the zero-regex engine rule are untouched;
  `src/Laravel/Stubs/*` is on the Laravel allow-list anyway.

## Tasks

- [x] **Task 1: publishable stub tree.** `src/Laravel/Stubs/miniapp/` with the
      Blade host (`resources/views/telegram/app.blade.php`), bridge
      (`resources/js/telegram-web-app.js`), example Vue SFC
      (`resources/js/examples/miniapp.vue`), theme CSS
      (`resources/css/telegram-theme.css`), Vite preset (`vite.config.js`) and
      `package.json` (peers `vite` + `vue`, presence-only). Publish tag +
      coordinator wiring documented.
- [x] **Task 2: `tests/MiniApp/StubPresenceTest.php`.** Presence + shape:
      every stub file exists; blade host declares `data-theme`, loads the
      bridge, mounts `#app`, carries the init-data bootstrap; bridge exposes
      `() => ({ initData, user })`, `sendData`, `postMessage`, `onThemeChange`,
      `window.TeleFrameBridge`; theme CSS maps all 10 `--tg-theme-*` vars in
      light/dark; Vite preset wires `examples/miniapp.vue` →
      `entryFileNames: 'miniapp.js'`; `package.json` peers `vite`+`vue`.
- [x] **Task 3: `tests/MiniApp/BladeHostTest.php`.** Renders the stub host on
      Testbench: `<html lang="en" data-theme="light|dark">`, theme CSS link,
      bridge asset, `build/miniapp.js`, `<div id="app">`, init-data bootstrap
      (`TELEFRAME_MINIAPP_BOOTSTRAP`, `__teleframeInitData`, `telegram:init-data`),
      and CSP nonce application.
- [x] **Task 4: `tests/MiniApp/MiniAppGuardIntegrationTest.php`.** Route proof:
      valid init-data (HMAC-signed with the configured bot token via the SAME
      `VerifyMiniAppInitData` core `TgWebAppGuard` instantiates) + matching
      `TlUserBinding` → authenticates as that Laravel User; forged (wrong-token)
      and replayed (stale `auth_date`) init-data → guest; session-guard variant
      resolves the same User.
- [x] **Task 5: docs.** `docs/miniapp.md` (publish command/tag + coordinator
      wiring, host usage, bridge API table, theme vars table, example Vue app
      walkthrough, guard wiring note), spec + this plan. Roadmap Phase 5g ticks
      are the coordinator's step (`docs/superpowers/plans/2026-09-07-master-
      roadmap.md` is on the do-not-touch list for this worker).

## Gate evidence (2026-09-08)

- `composer dump-autoload` → OK.
- `vendor/bin/phpunit tests/MiniApp` → OK (24 tests, 83 assertions): bridge +
  theme CSS + Vite + package.json presence/shape; Blade host renders with
  `data-theme` + bridge asset + init-data bootstrap (light + dark + nonce);
  guard authenticates a bound user with valid HMAC init-data; forged and
  replayed init-data rejected; session-guard variant resolves a User.
- `vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress` →
  `[OK] No errors` (all new files under `src/` are non-PHP stubs; `tests/`
  are outside phpstan `paths`).
- `composer verify` → full suite + phpstan green (tail reported by the worker).

**Commits:** none (coordinator commits; Phase 5g files in the tree remain owned
by the 5g worker). Roadmap Phase 5g ticks are the coordinator's step.