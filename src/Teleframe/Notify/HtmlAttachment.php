<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use DateTimeImmutable;
use RuntimeException;

/**
 * Renders the LONG section of a notification payload into ONE self-contained
 * HTML5 document — the design doc's ship default (§7, spec G7): "that lets
 * browser renders them like if it is laravel exception". All values are
 * `htmlspecialchars`-escaped, the stylesheet is inline, and there are zero
 * external references, so the file renders identically straight off disk.
 *
 * Document sections (design §7): title → metadata table (scalar context) →
 * trace frames (`exception`/`trace` keys) → body → raw payload `<pre>`.
 * File name follows the ruled pattern: `report-{date}-{topic}.html` written
 * into the injected temp dir (default `storage/framework/teleframe-attachments`
 * — the FilesystemCache precedent). The returned array is the
 * AttachmentFile-shaped payload `{name, path, mime, bytes}`.
 *
 * Zero-regex (`str_*` vocabulary) and plain-PHP constructible (constructor
 * wires nothing; no container calls).
 */
final class HtmlAttachment
{
    private const MIME = 'text/html';

    /** Context keys rendered as their own trace-frame sections. */
    private const FILE_ONLY_KEYS = ['exception', 'trace'];

    public function __construct() {}

    /**
     * Render the long section to a self-contained HTML file.
     *
     * @param  array<string, mixed>  $context
     * @return array{name: string, path: string, mime: string, bytes: int}
     *
     * @throws RuntimeException when the directory cannot be created or the
     *                          file cannot be written — loud, never silent
     */
    public function render(string $title, array $context, string $body, string $dir): array
    {
        $name = 'report-'.(new DateTimeImmutable)->format('Ymd-His').'-'.$this->slug($title).'.html';

        $dir = rtrim($dir, '/');
        if ($dir === '' || (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir))) {
            throw new RuntimeException("Cannot create attachment directory [{$dir}].");
        }

        $path = $dir.'/'.$name;

        if (@file_put_contents($path, $this->document($title, $context, $body), LOCK_EX) === false) {
            throw new RuntimeException("Cannot write attachment [{$path}].");
        }

        return [
            'name' => $name,
            'path' => $path,
            'mime' => self::MIME,
            'bytes' => (int) filesize($path),
        ];
    }

    /**
     * Escape one value for safe HTML output (the design's `esc()` surface).
     * Every user-provided value in the document passes through here.
     */
    public function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * The self-contained HTML document: inline CSS (auto dark/light), no
     * external refs, sectioned exactly per design §7.
     *
     * @param  array<string, mixed>  $context
     */
    private function document(string $title, array $context, string $body): string
    {
        $sections = '';

        $metaRows = $this->metadataRows($context);
        if ($metaRows !== '') {
            $sections .= '<h2>Context</h2>'
                .'<table><thead><tr><th scope="col">key</th><th scope="col">value</th></tr></thead>'
                .'<tbody>'.$metaRows.'</tbody></table>';
        }

        $traceBlocks = $this->traceBlocks($context);
        if ($traceBlocks !== '') {
            $sections .= '<h2>Exception / Trace</h2>'.$traceBlocks;
        }

        if ($body !== '') {
            $sections .= '<h2>Details</h2>'
                .'<pre class="body">'.$this->esc($body).'</pre>';
        }

        $sections .= '<h2>Raw payload</h2>'
            .'<pre>'.$this->esc($this->json($context)).'</pre>';

        return '<!DOCTYPE html>'."\n"
            .'<html lang="en">'."\n"
            .'<head>'."\n"
            .'<meta charset="utf-8">'."\n"
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'."\n"
            .'<title>'.$this->esc($title).'</title>'."\n"
            .'<style>'."\n"
            .self::CSS
            .'</style>'."\n"
            .'</head>'."\n"
            .'<body>'."\n"
            .'<div class="wrap">'."\n"
            .'<div class="title">'.$this->esc($title).'</div>'."\n"
            .'<div class="stamp">rendered '.$this->esc((new DateTimeImmutable)->format(DATE_ATOM)).' · '.$this->esc(self::MIME).'</div>'."\n"
            .$sections
            .'</div>'."\n"
            .'</body>'."\n"
            .'</html>';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function metadataRows(array $context): string
    {
        $rows = '';

        foreach ($context as $key => $value) {
            if (in_array((string) $key, self::FILE_ONLY_KEYS, true) || ! is_scalar($value)) {
                continue;
            }

            $rows .= '<tr><th>'.$this->esc((string) $key).'</th><td>'
                .$this->esc($this->displayValue($value)).'</td></tr>';
        }

        return $rows;
    }

    /**
     * Trace-frame sections: the `exception` and `trace` context keys, each
     * dumped in full — never inlined into a message (design §7).
     *
     * @param  array<string, mixed>  $context
     */
    private function traceBlocks(array $context): string
    {
        $blocks = '';

        foreach (self::FILE_ONLY_KEYS as $key) {
            if (! array_key_exists($key, $context)) {
                continue;
            }

            $blocks .= '<h2>'.$this->esc($key).'</h2>'
                .'<pre>'.$this->esc($this->dump($context[$key])).'</pre>';
        }

        return $blocks;
    }

    /**
     * Human-readable dump of one context value: scalars plainly, anything
     * else as pretty JSON (the full context dump from design §7).
     */
    private function dump(mixed $value): string
    {
        if (is_scalar($value)) {
            return $this->displayValue($value);
        }

        if ($value === null) {
            return 'null';
        }

        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? get_debug_type($value).' (not json-encodable)' : $json;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function json(array $context): string
    {
        $json = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? (string) json_last_error_msg() : $json;
    }

    /**
     * Zero-regex slug for the file name: lowercase, path separators and
     * spaces become hyphens (`app.error` stays `app.error`).
     */
    private function slug(string $title): string
    {
        $slug = strtolower(str_replace(['/', '\\', ' ', ':'], '-', $title));

        return $slug === '' ? 'report' : $slug;
    }

    /** Scalar → display string (`true`/`false`/`null` spelled out). */
    private function displayValue(mixed $value): string
    {
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    }

    /** Inline stylesheet — the only styling source, zero external refs. */
    private const CSS = <<<'CSS'
        :root {
            --bg: #1b1b1f;
            --panel: #24242a;
            --border: #393940;
            --fg: #e4e4e7;
            --muted: #a1a1aa;
            --accent: #f87171;
        }
        @media (prefers-color-scheme: light) {
            :root {
                --bg: #ffffff;
                --panel: #f4f4f5;
                --border: #d4d4d8;
                --fg: #18181b;
                --muted: #52525b;
                --accent: #b91c1c;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--fg);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
            font-size: 14px;
            line-height: 1.5;
        }
        .wrap { max-width: 960px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }
        .title { font-size: 1.4rem; font-weight: 700; color: var(--accent); word-break: break-word; }
        .stamp { margin-top: .25rem; font-size: .78rem; color: var(--muted); }
        h2 {
            margin: 2rem 0 .75rem;
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            padding-bottom: .5rem;
        }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid var(--border); padding: .4rem .6rem; font-size: .82rem; text-align: left; vertical-align: top; }
        thead th { background: var(--panel); color: var(--muted); font-weight: 600; }
        tbody th { width: 30%; color: var(--muted); font-weight: 500; word-break: break-word; }
        pre {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: .8rem;
            overflow-x: auto;
            font-size: .8rem;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
        }
        pre.body { max-height: 480px; overflow-y: auto; }
        CSS;
}
