<?php

declare(strict_types=1);

// Generates schema/methods-mtproto.json from the committed tdesktop .tl
// sources, core.telegram.org errors.json and MadelineProto extracted.json.
// Run: php bin/generate-method-schema.php

require __DIR__.'/../vendor/autoload.php';

use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSignatureParser;
use MeRezaRezaei\Teleframe\Schema\WireSchemaCoverage;

$root = dirname(__DIR__).'/src/Schema';
$layer = 0;
/** @var array<string, array{id: string, params: list<array{name: string, type: string, flag_word: string|null, bit: int|null, description: string}>, return: string, docs: string, errors: list<string>, description: string}> $methods */
$methods = [];
$skipped = 0;

// 1) functions from .tl (after ---functions---), layer from LAYER line
foreach (['api.tl', 'mtproto.tl'] as $file) {
    $inFunctions = false;
    $lines = file("{$root}/schema/sources/{$file}", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        fwrite(STDERR, "cannot read {$root}/schema/sources/{$file}\n");
        exit(1);
    }
    foreach ($lines as $raw) {
        $line = trim($raw);
        if ($line === '---functions---') {
            $inFunctions = true;

            continue;
        }
        if ($line === '---types---') {
            $inFunctions = false;

            continue;
        }
        if (str_starts_with($line, '// LAYER ')) {
            $layer = max($layer, (int) substr($line, 9));

            continue;
        }
        if ($line === '' || str_starts_with($line, '//') || str_starts_with($line, '---')) {
            continue;
        }
        if (! $inFunctions) {
            continue;
        }
        $line = rtrim($line, ';');
        try {
            $sig = TLSignatureParser::parse($line);
        } catch (InvalidArgumentException $e) {
            fwrite(STDERR, "skipping unparseable function line in {$file} ({$e->getMessage()}): {$line}\n");
            $skipped++;

            continue;
        }
        if (! $sig->hasExplicitId) {
            fwrite(STDERR, "skipping function without explicit id in {$file}: {$line}\n");
            $skipped++;

            continue;
        }
        $params = [];
        foreach ($sig->fields as $f) {
            if ($f['type'] === '#') {
                continue; // flag-mask word, not a wire param
            }
            $params[] = [
                'name' => $f['name'],
                'type' => $f['type'],
                'flag_word' => $f['flagWord'],
                'bit' => $f['bit'],
                'description' => '',
            ];
        }
        $methods[$sig->name] = [
            'id' => sprintf('0x%08x', $sig->id),
            'params' => $params,
            'return' => $sig->returnType,
            'docs' => "https://core.telegram.org/method/{$sig->name}",
            'errors' => [],
            'description' => '',
        ];
    }
}

// 2) invert errors.json {code: {MSG: [methods]}} -> method -> [MSG]
$errorsRaw = (array) json_decode((string) file_get_contents("{$root}/schema/sources/errors.json"), true);
$errorsLayer = (int) ($errorsRaw['layer'] ?? 0);
$tlLayer = $layer;
$layer = max($tlLayer, $errorsLayer);
foreach (($errorsRaw['errors'] ?? []) as $messages) {
    foreach ((array) $messages as $msg => $methodsList) {
        foreach ((array) $methodsList as $m) {
            if (is_string($m) && isset($methods[$m])) {
                $methods[$m]['errors'][] = (string) $msg;
            }
        }
    }
}

// 3) enrich with official doc descriptions (MadelineProto extracted.json)
$docsMap = [];
$docsPath = "{$root}/schema/sources/extracted.json";
if (file_exists($docsPath)) {
    $docsMap = (array) json_decode((string) file_get_contents($docsPath), true);
}
$escapeKey = static fn (string $s): string => str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $s);
$unescape = static fn (string $s): string => str_replace(['&lt;', '&gt;', '&amp;'], ['<', '>', '&'], $s);
foreach ($methods as $name => &$m) {
    $m['description'] = isset($docsMap["method_{$name}"]) ? $unescape((string) $docsMap["method_{$name}"]) : '';
    foreach ($m['params'] as $i => $p) {
        // conditional params are keyed WITH the flag prefix in extracted.json
        // (HTML-escaped): method_X_param_f_type_flags.N?Vector&lt;T&gt;
        $typePart = $p['flag_word'] !== null
            ? "{$p['flag_word']}.{$p['bit']}?{$p['type']}"
            : $p['type'];
        $key = "method_{$name}_param_{$p['name']}_type_{$escapeKey($typePart)}";
        $m['params'][$i]['description'] = isset($docsMap[$key]) ? $unescape((string) $docsMap[$key]) : '';
    }
    $m['errors'] = array_values(array_unique($m['errors']));
    sort($m['errors']);
}
unset($m);

// 3b) WIRE COVERAGE STAMP — the honesty field.
//
// A catalogue entry is a record of what Telegram's schema DECLARES, which is a
// broader question than whether this client can put the method on the wire.
// The catalogue is generated from `api.tl`/`mtproto.tl`; encoding goes through
// the mirror `TLRegistry` loads, `api_full.tl`/`mtproto_full.tl`. Those are
// different layers, so a slice of the catalogue is real-but-unreachable, plus a
// handful of .tl "functions" that were never host-addressable endpoints (generic
// request envelopes, `mtproto.tl` service requests).
//
// Without this stamp the artifact says a method exists and a host only learns
// otherwise from an encoder throw. With it, the artifact itself answers "can I
// call this" and "why not". The verdicts come from `WireSchemaCoverage`, the
// same classifier the runtime query seam uses, so the file and the code cannot
// tell two different stories — and `tests/Schema/CataloguedMethodsAreEncodableTest`
// asserts the stamped flags still agree with what genuinely encodes.
//
// TWO QUESTIONS, STAMPED SEPARATELY. `callable`/`blocked_by`/`reason` answer
// "can a HOST dial this by name" — and nothing is reported unsupported unless
// the host genuinely cannot send it. `envelope` is the separate shape fact:
// these are `{X:Type}` request WRAPPERS, real constructors that are really on
// the wire, but never things a host dials by name, because binding `query:!X`
// needs the wrapped request. `client_built` says which of them the client
// hand-builds itself. Collapsing those into one field is what once stamped
// `initConnection` "unsupported" while listing its concrete args in the very
// same record, with no way to say which of the two was actually true.
$callableCount = 0;
$envelopeCount = 0;
foreach ($methods as $name => &$m) {
    $verdict = WireSchemaCoverage::classify($name);
    $callable = $verdict === WireSchemaCoverage::CALLABLE;
    if ($callable) {
        $callableCount++;
    }
    $envelope = WireSchemaCoverage::isEnvelope($name);
    if ($envelope) {
        $envelopeCount++;
    }
    $m['wire'] = [
        'callable' => $callable,
        'blocked_by' => $callable ? null : $verdict,
        'reason' => WireSchemaCoverage::reason($name),
        'envelope' => $envelope,
        'envelope_reason' => WireSchemaCoverage::envelopeReason($name),
        'client_built' => WireSchemaCoverage::clientBuilt($name),
    ];
}
unset($m);

ksort($methods);
// Optional argv[1]: output directory (defaults to repo schema/; sources always read from repo schema/sources/)
$outDir = isset($argv[1]) && is_string($argv[1]) ? rtrim($argv[1], '/') : "{$root}/schema";
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}
file_put_contents(
    "{$outDir}/methods-mtproto.json",
    json_encode([
        '_generated' => true,
        'api' => 'mtproto',
        'layer' => $layer,
        'layers' => ['schema' => $tlLayer, 'errors' => $errorsLayer],
        'source' => 'tdesktop dev api.tl + mtproto.tl + core.telegram.org/api/errors.json + MadelineProto extracted.json',
        'wire' => [
            'callable_count' => $callableCount,
            'unsupported_count' => count($methods) - $callableCount,
            'envelope_count' => $envelopeCount,
            'mirror_files' => ['api_full.tl', 'mtproto_full.tl'],
            'mirror_layer' => WireSchemaCoverage::layerOf('api_full.tl'),
            'note' => 'A method\'s presence here records what Telegram\'s schema DECLARES. Whether THIS client can put '
                .'it on the wire is answered per method by its `wire` block; the two differ because the catalogue is '
                .'generated from a newer .tl layer than the wire mirror TLRegistry encodes against. Ask '
                .'MethodRegistry::isCallable()/unsupportedReason() for the authoritative answer. A per-method '
                .'`wire.envelope` is a SEPARATE fact, not an extra blocked verdict: it marks a {X:Type} request '
                .'wrapper that is a real constructor on the wire but is never dialed by name (`wire.client_built` '
                .'says whether the client hand-builds it itself).',
        ],
        'methods' => $methods,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
);
printf("methods-mtproto.json: %d methods, layer %d (%d skipped)\n", count($methods), $layer, $skipped);
