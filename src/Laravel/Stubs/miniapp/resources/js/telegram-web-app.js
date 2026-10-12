/**
 * teleframe Mini App bridge (Phase 5g, Q12 ruling (a)).
 *
 * A dependency-free ES module that wraps the Telegram SDK surface a Mini App
 * host needs. It NEVER ships the SDK, never rewrites the `initData` HMAC —
 * it reads what Telegram gave the webview (`window.Telegram.WebApp`) and hands
 * it to your app in one call.
 *
 * NO BUILD STEP is required for the bridge itself: the host page loads it
 * directly as a module (`<script type="module" src=".../telegram-web-app.js">`)
 * and the example Vue app imports it the same way (Vite bundles the import).
 *
 * Default export (callable):  () => ({ initData, user })
 *   import telegramWebApp from './telegram-web-app.js';
 *   const { initData, user } = telegramWebApp();
 *
 * The same callable is attached to `window.TeleFrameBridge` for classic-script
 * / non-module hosts and for devtools. Every method is safe to call before —
 * and outside — a Telegram webview (they fall back to what the Blade host
 * bootstrap stashed on `window.__teleframeInitData`, else empty values).
 */

'use strict';

const win = typeof window !== 'undefined' ? window : null;

function webApp() {
    return win && win.Telegram && win.Telegram.WebApp ? win.Telegram.WebApp : null;
}

function stashed() {
    return win && win.__teleframeInitData && typeof win.__teleframeInitData === 'object'
        ? win.__teleframeInitData
        : null;
}

function readInitData() {
    const sdk = webApp();
    if (sdk && typeof sdk.initData === 'string' && sdk.initData.length > 0) {
        return sdk.initData;
    }
    const stash = stashed();
    return stash && typeof stash.initData === 'string' && stash.initData.length > 0
        ? stash.initData
        : null;
}

function readUser() {
    const sdk = webApp();
    if (sdk && sdk.initDataUnsafe && sdk.initDataUnsafe.user) {
        return sdk.initDataUnsafe.user;
    }
    const stash = stashed();
    return stash && stash.user ? stash.user : null;
}

function readThemeParams() {
    const sdk = webApp();
    const params = sdk && sdk.themeParams && typeof sdk.themeParams === 'object'
        ? sdk.themeParams
        : {};
    const scheme = sdk && sdk.colorScheme ? sdk.colorScheme : null;
    return {
        colorScheme: scheme === 'dark' || scheme === 'light' ? scheme : null,
        bgColor: params.bg_color || null,
        textColor: params.text_color || null,
        hintColor: params.hint_color || null,
        buttonColor: params.button_color || null,
        buttonTextColor: params.button_text_color || null,
        secondaryBgColor: params.secondary_bg_color || null,
        headerBgColor: params.header_bg_color || null,
        linkColor: params.link_color || null,
        bottomBarBgColor: params.bottom_bar_bg_color || null,
        destructiveTextColor: params.destructive_text_color || null,
    };
}

function applyTheme(dark) {
    if (typeof document === 'undefined' || !document.documentElement) {
        return;
    }
    const resolved = dark === true
        ? true
        : dark === false
            ? false
            : !!(win && win.matchMedia && win.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.setAttribute('data-theme', resolved ? 'dark' : 'light');
}

function sendData(data) {
    const sdk = webApp();
    if (!sdk || typeof sdk.sendData !== 'function') {
        return false;
    }
    sdk.sendData(typeof data === 'string' ? data : JSON.stringify(data));
    return true;
}

function postMessage(payload) {
    if (!win || !win.parent || win.parent === win || typeof win.parent.postMessage !== 'function') {
        return false;
    }
    win.parent.postMessage(payload, '*');
    return true;
}

/**
 * Subscribe to Telegram's `themeChanged` event (fires immediately with the
 * current theme). Re-applies `data-theme` on <html> and hands the callback
 * the live themeParams. Returns an unsubscribing function.
 */
function onThemeChange(callback) {
    const sdk = webApp();
    const theme = readThemeParams();

    if (sdk && typeof sdk.onEvent === 'function') {
        sdk.onEvent('themeChanged', () => {
            const next = readThemeParams();
            if (next.colorScheme === 'dark' || next.colorScheme === 'light') {
                applyTheme(next.colorScheme === 'dark');
            }
            if (typeof callback === 'function') {
                callback(next);
            }
        });
    }

    applyTheme(theme.colorScheme === 'dark');
    if (typeof callback === 'function') {
        callback(theme);
    }

    return () => {
        if (sdk && typeof sdk.offEvent === 'function') {
            sdk.offEvent('themeChanged');
        }
    };
}

/**
 * The bridge's public call: `() => ({ initData, user })`.
 */
function bridge() {
    return {
        initData: readInitData(),
        user: readUser(),
    };
}

bridge.initData = readInitData;
bridge.currentUser = readUser;
bridge.themeParams = readThemeParams;
bridge.applyTheme = applyTheme;
bridge.sendData = sendData;
bridge.postMessage = postMessage;
bridge.onThemeChange = onThemeChange;

if (win) {
    win.TeleFrameBridge = bridge;
}

export default bridge;
export { bridge, readInitData as initData, readUser as currentUser };