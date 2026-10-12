{{--
    teleframe Mini App — Blade host stub (Phase 5g, Q12 ruling (a)).

    This is the page Telegram's webview hosts your Mini App in. It:
      1. declares `<html lang="en" data-theme>` and applies the Telegram theme
         vars from resources/css/telegram-theme.css (light/dark fallbacks —
         the runtime `themeParams` from the Telegram SDK win over CSS);
      2. loads the zero-build bridge `resources/js/telegram-web-app.js`
         (exposes `window.TeleFrameBridge`, default-export callable
         `() => ({ initData, user })` when bundled by Vite);
      3. mounts the Vue Mini App on `<div id="app">`;
      4. bootstraps init-data + theme WITHOUT inline handlers or remote
         eval — CSP-friendly (see the commented CSP meta below; add
         `https://telegram.org` to `script-src` only for emulation outside
         a real webview).

    Published with the rest of the stub tree via:
        php artisan vendor:publish --tag=teleframe-miniapp

    Optional variables: $theme (initial `light`|`dark`), $title, $nonce
    (a CSP nonce applied to every inline/module script on the page).
--}}
<!DOCTYPE html>
<html lang="en" data-theme="{{ $theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="color-scheme" content="light dark">
    <meta name="format-detection" content="telephone=no">
    <title>{{ $title ?? 'Telegram Mini App' }}</title>

    {{-- Telegram theme CSS vars. The SDK's live themeParams (applied by the
         bridge) override these defaults at runtime on <html data-theme>. --}}
    <link rel="stylesheet" href="{{ asset('css/telegram-theme.css') }}">

    {{-- Hosts normally set CSP via headers. Example (telegram webview
         emulation needs `https://telegram.org` on script-src):
         <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'nonce-...' https://telegram.org; style-src 'self' 'unsafe-inline'">
    --}}
    @stack('styles')
</head>
<body>
    {{-- Mini App mount point — the example Vue app binds here. --}}
    <div id="app" data-role="telegram-miniapp"></div>

    {{-- Bridge: plain JS module, NO build step required for the bridge itself.
         It runs before the bootstrap below (same type="module" queue) and sets
         window.TeleFrameBridge for the booted app / devtools. --}}
    <script type="module" src="{{ asset('js/telegram-web-app.js') }}"></script>

    {{-- init-data + theme bootstrap (teleframe:miniapp-bootstrap).
         Inside a real Telegram webview the SDK is already present; outside it
         this only leaves safe fallbacks (matchMedia light/dark) in place. --}}
    <script type="module"@if (!empty($nonce)) nonce="{{ $nonce }}"@endif>
        (function () {
            'use strict';
            window.TELEFRAME_MINIAPP_BOOTSTRAP = true;

            var root = document.documentElement;
            var win = window;

            function webApp() {
                return win.Telegram && win.Telegram.WebApp ? win.Telegram.WebApp : null;
            }

            function prefersDark() {
                try {
                    return !!win.matchMedia && win.matchMedia('(prefers-color-scheme: dark)').matches;
                } catch (e) {
                    return false;
                }
            }

            // init-data bootstrap: capture initData + user once and hand them to
            // the app through window.__teleframeInitData + a document event.
            function bootstrapInitData() {
                var sdk = webApp();
                var initData = sdk && typeof sdk.initData === 'string' && sdk.initData.length > 0
                    ? sdk.initData
                    : null;
                var user = sdk && sdk.initDataUnsafe && sdk.initDataUnsafe.user
                    ? sdk.initDataUnsafe.user
                    : null;

                win.__teleframeInitData = { initData: initData, user: user };

                document.dispatchEvent(new CustomEvent('telegram:init-data', {
                    detail: { initData: initData, user: user },
                }));
            }

            function bootstrapTheme() {
                var sdk = webApp();
                var dark = sdk && sdk.colorScheme === 'dark'
                    ? true
                    : sdk && sdk.colorScheme === 'light'
                        ? false
                        : prefersDark();
                root.setAttribute('data-theme', dark ? 'dark' : 'light');

                if (sdk) {
                    if (typeof sdk.ready === 'function') {
                        sdk.ready();
                    }
                    if (typeof sdk.expand === 'function') {
                        sdk.expand();
                    }
                }

                // Re-apply when Telegram flips the scheme; the bridge is the
                // canonical theming hook, this is the zero-dependency fallback.
                if (win.TeleFrameBridge && typeof win.TeleFrameBridge.onThemeChange === 'function') {
                    win.TeleFrameBridge.onThemeChange(function (params) {
                        var scheme = params && params.colorScheme;
                        root.setAttribute('data-theme', scheme === 'dark' ? 'dark'
                            : scheme === 'light' ? 'light'
                            : root.getAttribute('data-theme'));
                    });
                }
            }

            bootstrapInitData();
            bootstrapTheme();
        })();
    </script>

    {{-- Built Vue Mini App entry (the Vite preset emits public/build/miniapp.js).
         The example app reads init-data through the bridge and authenticates
         against your `auth:tg-webapp` routes (see docs/miniapp.md). --}}
    <script type="module"@if (!empty($nonce)) nonce="{{ $nonce }}"@endif src="{{ asset('build/miniapp.js') }}"></script>
    @stack('scripts')
</body>
</html>