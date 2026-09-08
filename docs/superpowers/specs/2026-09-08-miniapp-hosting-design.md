# Phase 5g — Mini-App Hosting (design)

**Parent:** `docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5g ·
`2026-09-07-framework-layers-gap-analysis.md` §II (Q12).
**Created:** 2026-09-08. **Depends on:** Phase 5b (identity + guards —
shipped: `TgWebAppGuard`, `TlUserBinding`, `VerifyMiniAppInitData`, `HasTelegram`).

## Problem

The vision wants the "Mini App hosted in Laravel" pattern REAL — the Vue
precedent does **not** exist in the repo (gap doc hard constraint 7: research
proved the "🌱 pattern exists" row is not backed by committed code). The HMAC
side is done (Phase 5b); what is missing is the frontend hosting surface and
the integrated proof that a Mini App authenticated like a Laravel User works
end-to-end.

## RULINGS applied (gap doc §RULINGS, decided 2026-09-07, Autonomy Protocol)

| Q | Ruling | How Phase 5g enacts it |
|---|---|---|
| Q12 | **(a) Ship publishable stubs: Vite preset + Blade host + web-app.js bridge + theme CSS vars** | `src/Laravel/Stubs/miniapp/` publishes `resources/views/telegram/app.blade.php` (host), `resources/js/telegram-web-app.js` (zero-build bridge), `resources/js/examples/miniapp.vue` (example Vue Mini App), `resources/css/telegram-theme.css` (the `--tg-theme-*` var set), `vite.config.js` (preset) + `package.json` (peers `vite`, `vue` — presence-only, never installed in CI). Publish tag `teleframe-miniapp`; provider registration documented for the coordinator (provider file is on the worker's do-not-touch list). |
| hard constraint 7 | No-Vue-precedent ⇒ **build the precedent** | The example app + host are REAL files, and `tests/MiniApp` proves the guard→binding→User path INTEGRATED with valid init-data — the roadmap gate "example Vue mini app runs inside Telegram, authenticated as the bound Laravel user" is evidenced by stubs + integration test (no real browser e2e in CI). |

## Architecture

```
src/Laravel/Stubs/miniapp/                     (publisher source; tag teleframe-miniapp)
├── resources/
│   ├── views/telegram/app.blade.php            Blade host: <html data-theme>, theme CSS,
│   │                                            bridge module, <div id="app">, init-data
│   │                                            bootstrap; CSP-friendly ($nonce, no eval,
│   │                                            no inline handlers, defer-by-default)
│   ├── js/telegram-web-app.js                  Bridge (ES module, no build step):
│   │                                            default export () => ({ initData, user }),
│   │                                            window.TeleFrameBridge, themeParams(),
│   │                                            onThemeChange() → sets data-theme,
│   │                                            sendData()/postMessage() wrappers
│   ├── js/examples/miniapp.vue                 Example Vue app: imports the bridge,
│   │                                            sends initData in X-Telegram-Init-Data,
│   │                                            calls the sample bound endpoint
│   └── css/telegram-theme.css                  --tg-theme-* vars (10 themeParams keys),
│                                                light (default) + dark fallbacks
├── vite.config.js                              Vite preset: miniapp.vue entry →
│                                                public/build/miniapp.js (deterministic),
│                                                dev server :5173
└── package.json                                private; peerDependencies vite + vue
```

### Host → bridge → app flow

1. Telegram webview injects its SDK → the Blade bootstrap stashes
   `window.__teleframeInitData = { initData, user }`, fires
   `telegram:init-data`, applies `colorScheme` to `<html data-theme>`, calls
   `ready()`/`expand()`.
2. The bridge (`type="module"`) reads the SDK (fallback: the stash) — never
   re-verifies; the PHP HMAC core stays the authority.
3. The built Vue app (`public/build/miniapp.js`) imports the bridge, pulls
   `{ initData, user }`, and authenticates its API calls with the raw
   `initData` in `X-Telegram-Init-Data` — the exact contract of the `tg.miniapp`
   middleware and `tg-webapp` guard.

### Guard integration (the roadmap gate)

`TgWebAppGuard` (Phase 5b) already does init-data HMAC → freshness window →
`TlUserBinding::bindingFor()` → morph Laravel `User`. Phase 5g does not touch
it; `tests/MiniApp` proves the FULL route with a genuine signed payload.

## Deliverables

- Publishable stub tree under `src/Laravel/Stubs/miniapp/` (blade host, bridge,
  example Vue SFC, theme CSS, Vite preset, package.json).
- `tests/MiniApp/`: `TestCase` (mirrors `tests/Identity/TestCase.php`),
  `StubPresenceTest` (presence + shape), `BladeHostTest` (host render:
  light/dark `data-theme`, bridge asset, mount, init-data bootstrap, nonce),
  `MiniAppGuardIntegrationTest` (valid HMAC authenticates bound User; forged /
  replayed rejected; session-guard variant resolves).
- Docs: `docs/miniapp.md`, this spec, `docs/superpowers/plans/2026-09-08-
  phase5g-miniapp-hosting.md`.

## Gate

- `vendor/bin/phpunit tests/MiniApp` green — presence/shape, host render, and
  the guard→binding→User integration.
- phpstan clean; `composer verify` stays green.
- No real Telegram / Vue toolchain in CI: presence + shape + HMAC integration
  only (the roadmap note permits stopping short of a browser e2e).