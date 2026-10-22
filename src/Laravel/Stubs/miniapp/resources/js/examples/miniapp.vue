<template>
    <div class="miniapp">
        <header class="miniapp__header tg-header">
            <h1 class="tg-text">{{ title }}</h1>
            <span class="tg-hint">colorScheme: {{ dark ? 'dark' : 'light' }}</span>
        </header>

        <main class="miniapp__body">
            <p class="tg-hint">
                Authenticated as the bound Laravel user via the
                <code>tg-webapp</code> guard (init-data HMAC + <code>TlUserBinding</code>).
            </p>

            <section class="tg-card">
                <h2 class="tg-text">Telegram identity</h2>
                <dl class="miniapp__grid">
                    <dt>initData</dt>
                    <dd class="tg-hint">{{ initData ? initData.slice(0, 24) + '…' : 'absent (emulation without SDK)' }}</dd>
                    <dt>user id</dt>
                    <dd class="tg-text">{{ user && user.id ? user.id : '—' }}</dd>
                    <dt>first name</dt>
                    <dd class="tg-text">{{ user && user.first_name ? user.first_name : '—' }}</dd>
                    <dt>username</dt>
                    <dd class="tg-text">{{ user && user.username ? '@' + user.username : '—' }}</dd>
                </dl>
            </section>

            <section class="tg-card">
                <h2 class="tg-text">Bound endpoint</h2>
                <button class="tg-button" type="button" :disabled="loading" @click="callBoundEndpoint">
                    {{ loading ? 'Calling…' : 'GET /api/telegram/me' }}
                </button>
                <pre class="miniapp__response tg-hint">{{ endpointResponse || 'Bound Laravel User payload lands here (host implements the route).' }}</pre>
            </section>
        </main>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import telegramWebApp from '../telegram-web-app.js';

/**
 * Example Mini App (Phase 5g). Uses the bridge as an import: the default
 * export is the `() => ({ initData, user })` callable the host page also
 * exposes as `window.TeleFrameBridge`.
 */
const { initData, user } = telegramWebApp();

const title = ref('Telegram Mini App');
const dark = ref(false);
const loading = ref(false);
const endpointResponse = ref('');

onMounted(() => {
    telegramWebApp.onThemeChange((themeParams) => {
        dark.value = themeParams.colorScheme === 'dark';
    });
});

/**
 * Authenticate as the bound Laravel user: the SAME init-data the host page
 * bootstrapped goes in the X-Telegram-Init-Data header, exactly like the
 * `tg.miniapp` middleware / `tg-webapp` guard expect.
 */
async function callBoundEndpoint() {
    const headers = new Headers({ Accept: 'application/json' });
    if (initData) {
        headers.set('X-Telegram-Init-Data', initData);
    }

    loading.value = true;
    try {
        const response = await fetch('/api/telegram/me', { headers, credentials: 'same-origin' });
        const body = response.ok ? await response.json() : { status: response.status };
        endpointResponse.value = JSON.stringify(body, null, 2);
    } catch (error) {
        endpointResponse.value = 'fetch failed: ' + error.message;
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.miniapp {
    min-height: 100vh;
    background: var(--tg-theme-bg-color);
    color: var(--tg-theme-text-color);
    font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    padding: 1rem;
    box-sizing: border-box;
}

.miniapp__header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
}

.miniapp__body {
    margin-top: 1rem;
}

.miniapp__grid {
    display: grid;
    grid-template-columns: 8rem 1fr;
    gap: 0.25rem 0.75rem;
    margin: 0;
}

.miniapp__response {
    white-space: pre-wrap;
    word-break: break-word;
    margin-top: 0.75rem;
}

.tg-card {
    background: var(--tg-theme-secondary-bg-color);
    color: var(--tg-theme-text-color);
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    margin-top: 1rem;
}

.tg-button {
    background: var(--tg-theme-button-color);
    color: var(--tg-theme-button-text-color);
    border: 0 none;
    border-radius: 0.5rem;
    padding: 0.6rem 1rem;
    font-size: 1rem;
}

.tg-hint {
    color: var(--tg-theme-hint-color);
}

a.tg-link {
    color: var(--tg-theme-link-color);
    text-decoration: none;
}
</style>