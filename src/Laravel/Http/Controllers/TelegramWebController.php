<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the rebuilt official Telegram Web client (telegram-tt / Web A)
 * from the package's tgweb/dist static bundle.
 *
 * Registered via the Route::telegramWeb macro:
 *
 *     Route::telegramWeb();          // GET /tg/{path?}
 *     Route::telegramWeb('web/tg');  // custom prefix
 *
 * SPA fallback: any non-file path (no dot in the last segment) falls
 * back to index.html so client-side routing works. The route is
 * 404 when teleframe.telegram_web.enabled is false or the dist
 * directory does not exist.
 */
final class TelegramWebController
{
    /**
     * Map of file extensions to MIME types served from the bundle.
     *
     * @var array<string, string>
     */
    private static array $mimeMap = [
        'html' => 'text/html; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'map' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'mp3' => 'audio/mpeg',
        'ogg' => 'audio/ogg',
        'wav' => 'audio/wav',
        'wasm' => 'application/wasm',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'txt' => 'text/plain; charset=utf-8',
        'xml' => 'application/xml; charset=utf-8',
        'webmanifest' => 'application/manifest+json; charset=utf-8',
        'vtt' => 'text/vtt; charset=utf-8',
        'avif' => 'image/avif',
        'flac' => 'audio/flac',
        'm4a' => 'audio/mp4',
        'aac' => 'audio/aac',
    ];

    public function __invoke(?string $path = null): Response|BinaryFileResponse|RedirectResponse
    {
        if (! config('teleframe.telegram_web.enabled', false)) {
            return new Response('Telegram Web client is not enabled (set TELEGRAM_WEB_ENABLED=true).', 404);
        }

        $distPath = $this->resolveDistPath();

        if ($distPath === null || ! is_dir($distPath)) {
            return new Response('Telegram Web bundle not found (run tgweb/build.sh).', 404);
        }

        $requested = $path ?? 'index.html';
        $requested = ltrim($requested, '/');

        // Prevent directory traversal.
        if (str_contains($requested, '..')) {
            return new Response('Not found.', 404);
        }

        $resolved = realpath($distPath.DIRECTORY_SEPARATOR.$requested);

        if ($resolved !== false && str_starts_with($resolved, $distPath) && is_file($resolved)) {
            // A bare `/tg` resolves straight to index.html via the default
            // path; it is still the SPA entry and must carry its trailing
            // slash so the relative ./assets/... references resolve under /tg/.
            if ($path === null) {
                $redirect = $this->redirectToTrailingSlash();

                if ($redirect !== null) {
                    return $redirect;
                }
            }

            return $this->serveFile($resolved);
        }

        // SPA fallback: non-file request → index.html.
        $segments = explode('/', $requested);
        $last = end($segments);

        if (! str_contains($last, '.')) {
            // The client is HASH-routed but references its assets via
            // relative URLs (./assets/..., ./redirect.js, ./site.webmanifest),
            // so it must live on a trailing-slash URL or the browser resolves
            // those against the site root. 301 marks the trailing slash as the
            // canonical form (permanent, SEO/cache friendly); 302 would leave
            // every slash-less hit as a separate "temporary" location.
            $redirect = $this->redirectToTrailingSlash();

            if ($redirect !== null) {
                return $redirect;
            }

            $index = $distPath.DIRECTORY_SEPARATOR.'index.html';

            if (is_file($index)) {
                return $this->serveFile($index);
            }
        }

        return new Response('Not found.', 404);
    }

    /**
     * Moving the SPA to a URL without a trailing slash resolves the bundle's
     * relative URLs against the site root, so every asset 404s. Redirect to
     * the trailing-slash form, preserving the query string.
     */
    private function redirectToTrailingSlash(): ?RedirectResponse
    {
        if (str_ends_with((string) request()->getPathInfo(), '/')) {
            return null;
        }

        // `redirect()->to()` formats the target via UrlGenerator::format(),
        // which trims trailing slashes away — exactly the slash this redirect
        // exists to add. Build the response raw instead: getRequestUri()
        // carries the query string and the trailing '/' goes before it
        // (/tg?x=1 → /tg/?x=1), landing verbatim in the Location header.
        // 301 marks the trailing slash as canonical (permanent, cache/SEO
        // friendly); 302 would keep every slash-less hit a separate
        // "temporary" location.
        $uri = request()->getRequestUri();
        $position = strpos($uri, '?');

        if ($position !== false) {
            $uri = substr($uri, 0, $position).'/'.substr($uri, $position);
        } else {
            $uri .= '/';
        }

        // 301 is cached permanently by browsers/proxies — forbid it. If the
        // SPA mount path ever changes, cached clients must not hold the old
        // slashless URL as the canonical location, and intermediates must not
        // cache the redirect either (W-41). The HTML shell itself already
        // carries no-store via serveFile().
        $redirect = new RedirectResponse($uri, 301);
        $redirect->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $redirect;
    }

    private function serveFile(string $file): BinaryFileResponse
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = self::$mimeMap[$ext] ?? mime_content_type($file) ?: 'application/octet-stream';

        /** @var BinaryFileResponse $response */
        $response = response()->file($file, [
            'Content-Type' => $mime,
            'Cache-Control' => $ext === 'html'
                ? 'no-store, no-cache, must-revalidate'
                : 'public, max-age=31536000, immutable',
        ]);

        // Service workers must be served with no-store from the origin scope.
        if ($ext === 'js' && str_contains(basename($file), 'service-worker')) {
            $response->headers->set('Cache-Control', 'no-store');
        }

        return $response;
    }

    private function resolveDistPath(): ?string
    {
        /** @var string|null $override */
        $override = config('teleframe.telegram_web.dist_path');

        if ($override !== null && $override !== '' && is_dir($override)) {
            return realpath($override);
        }

        // Default: package root / tgweb/dist  (package is vendor/merezarezaei/teleframe)
        $packageRoot = dirname(__DIR__, 4);
        $default = $packageRoot.DIRECTORY_SEPARATOR.'tgweb'.DIRECTORY_SEPARATOR.'dist';

        return is_dir($default) ? realpath($default) : null;
    }
}
