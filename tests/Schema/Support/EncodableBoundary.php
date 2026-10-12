<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Support;

use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;

/**
 * An INDEPENDENT re-derivation of the catalogue's dial-by-name boundary, read
 * straight from the committed artefacts.
 *
 * WHY A SECOND IMPLEMENTATION
 * ===========================
 * `WireSchemaCoverage` is what production reports, so asking it what the
 * boundary is and then asserting it equals itself proves nothing. If the
 * classifier's loader rule were wrong, every test that consulted it would
 * agree with the wrongness. So the families are recomputed here, from the
 * files the loader actually reads, under the SAME admissibility rule — written
 * a second time so the two can be compared.
 *
 * The rule, stated once so both sides implement the same thing:
 *
 *   - The CATALOGUE is the `---functions---` section of `sources/api.tl` plus
 *     the whole of `sources/mtproto.tl`, because that is what
 *     `bin/generate-method-schema.php` parses. A TYPE is not a method.
 *   - The WIRE MIRROR is every id-bearing, non-brace line of
 *     `sources/api_full.tl` + `sources/mtproto_full.tl`, which is exactly
 *     `TLRegistry::mirrorFileLines()`. Brace-bearing generic wrappers are
 *     skipped by the loader, which is why an envelope is never "in the mirror".
 *   - A catalogued name IN the mirror is callable. Otherwise: a `{X:Type}` line
 *     is an ENVELOPE; a name from `mtproto.tl` is a SERVICE request; anything
 *     else is the LAYER GAP.
 *
 * String functions only, no regex. A regex here would be free to disagree with
 * the loader in ways the comparison above could not explain; `tests/` is
 * outside the `src/` zero-regex rule, but matching the loader is the point.
 *
 * This is a TEST helper, not a source module: it lives under `tests/` and is
 * never autoloaded by a host.
 */
final class EncodableBoundary
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /**
     * The whole derivation.
     *
     * @return array{
     *     envelope: list<string>,
     *     service: list<string>,
     *     'not-in-wire-mirror': list<string>,
     *     catalogue: int,
     *     callable: int
     * }
     */
    public static function derive(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $catalogue = self::catalogueIndex();
        $mirror = self::wireMirrorIndex();

        $envelope = [];
        $service = [];
        $gap = [];
        foreach ($catalogue as $name => $entry) {
            if (isset($mirror[$name])) {
                continue;
            }
            if (str_contains($entry['line'], '{X:Type}')) {
                $envelope[] = $name;

                continue;
            }
            if ($entry['file'] === 'mtproto.tl') {
                $service[] = $name;

                continue;
            }
            $gap[] = $name;
        }

        sort($envelope);
        sort($service);
        sort($gap);

        return self::$cache = [
            'envelope' => $envelope,
            'service' => $service,
            'not-in-wire-mirror' => $gap,
            'catalogue' => self::catalogue(),
            'callable' => self::catalogue() - count($envelope) - count($service) - count($gap),
        ];
    }

    /**
     * constructor name => ['file' => string, 'line' => string], for every name
     * the packaged catalogue advertises, resolved back to the .tl function
     * line it was generated from.
     *
     * Driven by the CATALOGUE ARTEFACT, not by raw .tl lines: the .tl files
     * also declare types and bare built-in names (`ipPort`, `int`, `string`,
     * `tlsBlock*`), and those are not methods. Starting from the artefact is
     * what keeps a TYPE out of the boundary — which is exactly the mistake
     * production makes impossible, because it only ever classifies names
     * `MethodRegistry` already vouched for.
     *
     * Only the `---functions---` sections count, mirroring the generator: a
     * name with no such line is a defect and throws rather than being silently
     * classified.
     *
     * @return array<string, array{file: string, line: string}>
     */
    public static function catalogueIndex(): array
    {
        $lines = [];
        foreach (['api.tl', 'mtproto.tl'] as $file) {
            foreach (self::functionLines($file) as $line) {
                $name = self::name($line);
                if ($name === '' || isset($lines[$name])) {
                    continue;
                }
                $lines[$name] = ['file' => $file, 'line' => $line];
            }
        }

        $index = [];
        foreach (array_keys(self::methods('methods-mtproto.json')) as $name) {
            $name = (string) $name;
            if (! isset($lines[$name])) {
                throw new \RuntimeException(
                    "Catalogued method [{$name}] is in methods-mtproto.json but in no ---functions--- section of "
                    .'api.tl or mtproto.tl, so the boundary cannot be derived for it. Re-run '
                    .'php bin/generate-method-schema.php.',
                );
            }
            $index[$name] = $lines[$name];
        }

        return $index;
    }

    /**
     * Lines of a .tl source that sit in a `---functions---` section.
     *
     * `mtproto.tl` has more than one such section, so the flag toggles rather
     * than switching on once — same rule the generator and
     * `WireSchemaCoverage::functionLines()` apply.
     *
     * @return list<string>
     */
    private static function functionLines(string $file): array
    {
        $inFunctions = false;
        $out = [];
        foreach (self::lines($file) as $line) {
            $line = trim($line);
            if ($line === '---functions---') {
                $inFunctions = true;

                continue;
            }
            if ($line === '---types---') {
                $inFunctions = false;

                continue;
            }
            if (! $inFunctions || $line === '' || $line[0] === '/' || str_starts_with($line, '---')) {
                continue;
            }
            $out[] = rtrim($line, ';');
        }

        return $out;
    }

    /**
     * constructor name => true, for every id-bearing non-generic mirror line.
     *
     * @return array<string, true>
     */
    public static function wireMirrorIndex(): array
    {
        $index = [];
        foreach (['api_full.tl', 'mtproto_full.tl'] as $file) {
            foreach (self::lines($file) as $line) {
                $line = trim($line, " \t;");
                if ($line === '' || $line[0] === '/' || str_starts_with($line, '---')) {
                    continue;
                }
                if (! str_contains($line, '#') || str_contains($line, '{')) {
                    continue;
                }
                $index[self::name($line)] = true;
            }
        }

        return $index;
    }

    /**
     * How many methods the packaged mtproto catalogue declares. Read from the
     * committed artefact, not from the .tl files, so the "823" the prose quotes
     * is the same 823 a host's registry sees.
     */
    public static function catalogue(): int
    {
        return count((array) self::methods('methods-mtproto.json'));
    }

    public static function botApiCatalogue(): int
    {
        return count((array) self::methods('methods-botapi.json'));
    }

    /**
     * The layer `schema-manifest.json` stamps, or 0 when it stamps none. The
     * boundary's layer gap is meaningless unless the catalogue's layer is known,
     * so this is part of what the boundary is derived from.
     */
    public static function manifestLayer(): int
    {
        $decoded = json_decode(
            (string) file_get_contents(SchemaArtifacts::path('schema-manifest.json')),
            true,
        );

        return (int) (is_array($decoded) ? ($decoded['layer'] ?? 0) : 0);
    }

    /** Test seam: drop the memoised derivation. */
    public static function flush(): void
    {
        self::$cache = null;
    }

    /** @return array<string, mixed> */
    private static function methods(string $file): array
    {
        $decoded = json_decode((string) file_get_contents(SchemaArtifacts::path($file)), true);
        if (! is_array($decoded) || ! is_array($decoded['methods'] ?? null)) {
            throw new \RuntimeException("Malformed schema artifact [{$file}]: missing methods map.");
        }

        return $decoded['methods'];
    }

    /**
     * @return list<string>
     */
    private static function lines(string $file): array
    {
        $path = SchemaArtifacts::source($file);
        if (! is_file($path)) {
            throw new \RuntimeException("Committed TL source [{$path}] is missing; the boundary cannot be derived.");
        }

        return file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    }

    /**
     * First space-delimited token of a .tl line, `#id` stripped.
     */
    private static function name(string $line): string
    {
        $line = ltrim($line);
        $space = strpos($line, ' ');
        $token = $space === false ? $line : substr($line, 0, $space);
        $hash = strpos($token, '#');

        return $hash === false ? $token : substr($token, 0, $hash);
    }
}
