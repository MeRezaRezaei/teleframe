import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

/*
 * teleframe Mini App — Vite preset (Phase 5g, Q12 ruling (a)).
 *
 * Wires the example Vue Mini App (resources/js/examples/miniapp.vue) with a
 * deterministic build output (public/build/miniapp.js) that the Blade host
 * (resources/views/telegram/app.blade.php) references, plus a dev server.
 *
 * The bridge (resources/js/telegram-web-app.js) is a dependency-free module —
 * it is imported by the example app and ALSO served directly by the host
 * page, so it never needs @vitejs/plugin-vue or a build of its own.
 *
 * peers: vite + vue (package.json peerDependencies). `npm install` runs in
 * the HOST, never in this package's CI.
 */
export default defineConfig({
    plugins: [vue()],

    resolve: {
        alias: {
            '@teleframe': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },

    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        cors: true,
    },

    build: {
        outDir: 'public/build',
        emptyOutDir: false,
        rollupOptions: {
            input: {
                miniapp: fileURLToPath(
                    new URL('./resources/js/examples/miniapp.vue', import.meta.url),
                ),
            },
            output: {
                entryFileNames: 'miniapp.js',
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name][extname]',
            },
        },
    },
});