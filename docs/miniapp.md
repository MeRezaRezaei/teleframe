# Mini-App Hosting — publishable Vite + Blade + bridge stubs

> **Phase 5g** (Q12 ruling (a)). The vision's "Vue in Laravel" Mini App pattern
> has **no committed precedent** — this phase BUILDS it (hard constraint 7).
> Everything below is a **publishable stub**: files you copy into your app and
> own, plus the integration proof that the `tg-webapp` guard's
> init-data → binding → Laravel `User` path is real end-to-end.

Source tree: `src/Laravel/Stubs/miniapp/` (this package). Destination: your app.

---

## 1. Publish

```bash
php artisan vendor:publish --tag=teleframe-miniapp
```

The tag is **not wired into the provider yet** (the worker's do-not-touch list
includes `TeleframeServiceProvider.php`). The coordinator wires this once:

```php
// in TeleframeServiceProvider::boot()
$this->publishes([
    __DIR__ . '/../Stubs/miniapp' => base_path(),
], 'teleframe-miniapp');
```

Publishing `Stubs/miniapp` → your app root drops in:

| File | Lands at | What it is |
|---|---|---|
| `resources/views/telegram/app.blade.php` | `resources/views/telegram/app.blade.php` | Blade host page |
| `resources/js/telegram-web-app.js` | `resources/js/telegram-web-app.js` | Zero-build bridge |
| `resources/js/examples/miniapp.vue` | `resources/js/examples/miniapp.vue` | Example Vue Mini App |
| `resources/css/telegram-theme.css` | `resources/css/telegram-theme.css` | `--tg-theme-*` vars |
| `vite.config.js` | your app root | Vite preset |
| `package.json` | your app root | `vite` + `vue` peers (never installed by this package's CI) |

`vendor:publish` skips files that already exist unless you pass `--force`
(which will also overwrite a pre-existing `vite.config.js` / `package.json` —
merge by hand if your app already has a Vite app).

After publishing:

```bash
npm install          # host installs vite + vue (+ @vitejs/plugin-vue)
npm run dev          # Vite dev server on :5173
# or
npm run build        # emits public/build/miniapp.js (deterministic entry name)
```

The bridge never needs a build: copy
`resources/js/telegram-web-app.js` → `public/js/` if you want the host to also
serve it standalone — the Blade host and the Vue import both resolve it.

## 2. Host page

```php
// routes/web.php
Route::get('/telegram/miniapp', fn () =>
    view('telegram.app', ['theme' => 'light'])   // or 'dark'; $title, $nonce optional
);
```

The host page (`resources/views/telegram/app.blade.php`):

- `<html lang="en" data-theme="light|dark">` — the whole theme toggles off this
  attribute (set by the bridge's `onThemeChange()`);
- links `css/telegram-theme.css` (light/dark fallbacks);
- loads the bridge as `type="module"` (`asset('js/telegram-web-app.js')`);
- mounts `<div id="app" data-role="telegram-miniapp">`;
- carries the init-data bootstrap — stashes `window.__teleframeInitData =
  { initData, user }`, fires a `telegram:init-data` CustomEvent, applies the
  Telegram color scheme to `data-theme`, calls `Telegram.WebApp.ready()/expand()`;
- loads the built example app: `asset('build/miniapp.js')`.

**CSP:** no inline event handlers, no `eval`, defer-by-default module scripts.
Hand the view a `$nonce` to stamp it on every inline/module script
(`<script type="module" nonce="...">`). Add `https://telegram.org` to
`script-src` **only** for emulation outside a real webview — inside Telegram the
SDK is already injected, and the bridge treats `window.Telegram.WebApp`
absent as "no init-data yet" (falls back to the bootstrap stash).

## 3. Bridge API — `resources/js/telegram-web-app.js`

Dependency-free ES module; default export is the callable
`() => ({ initData, user })`. Same object on `window.TeleFrameBridge`.

```js
import telegramWebApp from '../telegram-web-app.js';

const { initData, user } = telegramWebApp();      // () => ({ initData, user })

initData;                                          // raw X-Telegram-Init-Data payload
user;                                              // Telegram user { id, first_name, ... }
```

| Member | Signature | Behavior |
|---|---|---|
| `default export` / `window.TeleFrameBridge` | `() => ({ initData, user })` | One-call handoff of the host bootstrapped identity |
| `bridge.initData()` | `() => string\|null` | Raw init-data (SDK first, then `__teleframeInitData`) |
| `bridge.currentUser()` | `() => object\|null` | `initDataUnsafe.user` (or bootstrap stash) |
| `bridge.themeParams()` | `() => {colorScheme, bgColor, textColor, ...}` | Live `themeParams` mapped camelCase |
| `bridge.applyTheme(dark)` | `(bool\|undefined) => void` | Sets `<html data-theme="light\|dark">` (matchMedia fallback) |
| `bridge.onThemeChange(cb)` | `(cb) => off()` | Fires immediately + on `themeChanged`; re-applies `data-theme`; returns unsubscribe |
| `bridge.sendData(data)` | `(string\|object) => bool` | `window.Telegram.WebApp.sendData` wrapper (object → JSON) |
| `bridge.postMessage(payload)` | `(object) => bool` | `window.parent.postMessage(payload, '*')` wrapper (embedded contexts) |

The bridge never re-verifies init-data — verification stays in the PHP HMAC
core (`VerifyMiniAppInitData` → `TgWebAppGuard`, Phase 5b).

## 4. Theme CSS vars

`resources/css/telegram-theme.css` maps Telegram's `themeParams` keys to the
`--tg-theme-*` custom properties your UI consumes, with light (default) and
dark fallbacks under `:root[data-theme='light']` / `:root[data-theme='dark']`.

| Telegram themeParams key | CSS var | Light default | Dark default |
|---|---|---|---|
| `bg_color` | `--tg-theme-bg-color` | `#ffffff` | `#212121` |
| `text_color` | `--tg-theme-text-color` | `#000000` | `#ffffff` |
| `hint_color` | `--tg-theme-hint-color` | `#999999` | `#aaaaaa` |
| `button_color` | `--tg-theme-button-color` | `#5288c1` | `#8774e1` |
| `button_text_color` | `--tg-theme-button-text-color` | `#ffffff` | `#ffffff` |
| `secondary_bg_color` | `--tg-theme-secondary-bg-color` | `#f0f0f0` | `#181818` |
| `header_bg_color` | `--tg-theme-header-bg-color` | `#ffffff` | `#212121` |
| `link_color` | `--tg-theme-link-color` | `#2481cc` | `#5eb1ef` |
| `bottom_bar_bg_color` | `--tg-theme-bottom-bar-bg-color` | `#ffffff` | `#181818` |
| `destructive_text_color` | `--tg-theme-destructive-text-color` | `#d14d4d` | `#e53935` |

The live SDK palette (a user's custom Telegram theme) wins over CSS defaults:
the host bootstrap / bridge apply it as inline custom properties on `<html>`.

## 5. Example Vue Mini App — `resources/js/examples/miniapp.vue`

The SFC is the Vite preset's single entry (`miniapp.vue` → `public/build/miniapp.js`):

1. imports the bridge — `import telegramWebApp from '../telegram-web-app.js'`;
2. calls `const { initData, user } = telegramWebApp()` for the bootstrapped
   identity;
3. on `onThemeChange(themeParams)` it tracks light/dark for display;
4. **`callBoundEndpoint()`** fetches a sample bound endpoint
   (`/api/telegram/me`) **with the raw init-data in the `X-Telegram-Init-Data`
   header** — the exact header the `tg.miniapp` middleware and the
   `tg-webapp` guard authenticate with.

Your backend route (the "sample bound endpoint", wired in **your** host):

```php
// routes/api.php
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\VerifyMiniAppInitData;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyMiniAppInitData::class)->get('/telegram/me', function (Request $request) {
    $tgUser = $request->attributes->get('telegram_user');   // verified init-data user
    $user   = \MeRezaRezaei\Teleframe\Identity\Bindings::findTF((int) $tgUser['id'])?->resolver();

    return response()->json([
        'telegram_user'  => $tgUser,
        'laravel_user'   => $user?->toArray(),
        'authenticated'  => $user !== null,
    ]);
});
```

## 6. Guard wiring

Auth lives in your host's `config/auth.php` (no provider registration — see
`docs/identity.md` §5):

```php
'guards' => [
    'tg-webapp' => [
        'driver'   => 'custom',
        'provider' => 'users',
        'callback' => static fn ($app) => new \MeRezaRezaei\Teleframe\Identity\Guards\TgWebAppGuard($app['request']),
    ],
    // ...
],
```

Then protect any route with `auth:tg-webapp` — a request carrying valid, fresh
init-data for a bound telegram id resolves to that Laravel `User` (the
roadmap gate proven by `tests/MiniApp/MiniAppGuardIntegrationTest.php`).

## 7. Testing notes

- `vendor/bin/phpunit tests/MiniApp` — presence/shape of every stub, Blade host
  render (light/dark + nonce), and the guard→binding→User integration
  (valid HMAC authenticates; forged wrong-token and stale-replay init-data are
  rejected). No Vue/Vite toolchain runs in CI.
- The package's `package.json`/`vite.config.js` are **presence stubs** — peer
  deps referenced, never installed here.