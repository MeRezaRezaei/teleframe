<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\ParsedSignature;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSignatureParser;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlConstructor;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlMethod;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * Input adaptor (plan Task 2): feeds the fork metamodel from this
 * package's committed schema/sources/*.tl (the owner's full v227 mirror:
 * TL_telegram_v227.tl + TL_mtproto_v1.tl + TL_secret.tl) via teleframe's
 * own TLSignatureParser; teleproto's partial vendored sources remain a
 * documented fallback.
 *
 * Line protocol mirrors the sources: "//" comments (incl. "// LAYER N"),
 * "---types---"/"---functions---" section switches (default types),
 * MadelineProto "===N===" layer fences (skipped), then one definition
 * per line.
 *
 * Out-of-grammar lines are handled explicitly, never silently:
 *  - core builtins ("int ? = Int;", "int128 4*[ int ] = Int128;", ...) are
 *    skipped — mtproto.tl marks them "no need to gen";
 *  - generic combinators ("vector {t:Type} ...", "invokeWithLayer#... {X:Type}
 *    query:!X = X;") fall back to a manual generic-filler mapping (fork
 *    semantics: fillers excluded from params(), generators skip them);
 *  - anything else the tokenizer rejects fails loudly with file:line.
 *
 * Ids: explicit "#hex" ids are kept as wire truth (crc verified against
 * TlCanon, mismatches recorded like the fork parser did); id-less lines get
 * the TlCanon crc32 — computed, never invented.
 */
final class TeleframeSchemeLoader
{
    private const CORE_TYPE_NAMES = ['int', 'long', 'double', 'string', 'int128', 'int256'];

    public static function parseFile(string $path): TlScheme
    {
        if (!is_file($path)) {
            throw new TlParseException("scheme file not found: {$path}");
        }
        return self::parseString((string) file_get_contents($path), $path);
    }

    /** "// LAYER N" comment scan over a file (0 when absent). */
    public static function layerFromFile(string $path): int
    {
        if (!is_file($path)) {
            return 0;
        }
        return self::layerFromComment((string) file_get_contents($path));
    }

    public static function parseString(string $tl, string $file = '<string>'): TlScheme
    {
        $scheme = new TlScheme(self::layerFromComment($tl));
        $mode = 'types';

        foreach (explode("\n", $tl) as $lineNo => $rawLine) {
            $line = trim($rawLine);
            if ($line === '' || str_starts_with($line, '//')) {
                continue;
            }
            // MadelineProto multi-schema layer separators ("===8===", "===17===", ...):
            // section fences between layered fragments of one file — no TL content.
            if (str_starts_with($line, '===') && str_ends_with($line, '===')) {
                continue;
            }
            if ($line === '---types---' || $line === '---functions---') {
                $mode = $line === '---functions---' ? 'functions' : 'types';
                continue;
            }
            self::parseDefinition($line, $mode, $scheme, $file, $lineNo + 1);
        }
        return $scheme;
    }

    /** Default sources dir: config override, else this package's committed mirror. */
    public static function defaultSourcesDir(): string
    {
        $configured = self::configuredSourcesDir();
        if ($configured !== null) {
            return $configured;
        }
        // this package's committed copies of the owner's full layer mirror
        // (schema/sources/TL_telegram_v227.tl + TL_mtproto_v1.tl + TL_secret.tl)
        $packageSources = dirname(__DIR__, 3) . '/schema/sources';
        if (glob($packageSources . '/*.tl') !== []) {
            return $packageSources;
        }
        // teleframe IS the engine now — the vendored-mirror fallbacks are gone.
        return $packageSources;
    }

    private static function configuredSourcesDir(): ?string
    {
        if (!function_exists('config')) {
            return null;
        }
        try {
            $value = config('teleframe.schema_sources');
        } catch (\Throwable) {
            return null; // config() helper present but no app container bound
        }
        return is_string($value) && $value !== '' ? $value : null;
    }

    /** Regex-free "// LAYER N" comment scan (first match wins). */
    private static function layerFromComment(string $tl): int
    {
        foreach (explode("\n", $tl) as $raw) {
            $line = trim($raw);
            if (!str_starts_with($line, '//')) {
                continue;
            }
            $rest = ltrim(substr($line, 2));
            if (!str_starts_with($rest, 'LAYER')) {
                continue;
            }
            $tail = substr($rest, 5);
            if ($tail === '' || !ctype_space($tail[0])) {
                continue;
            }
            $num = trim($tail);
            if ($num !== '' && ctype_digit($num)) {
                return (int) $num;
            }
        }
        return 0;
    }

    private static function parseDefinition(string $line, string $mode, TlScheme $scheme, string $file, int $lineNo): void
    {
        $where = "{$file}:{$lineNo}";

        if (self::isCoreBuiltin($line)) {
            return; // "int ? = Int;" etc. — core wire types, no need to gen
        }
        if (str_contains($line, '{t:Type}') || str_contains($line, '{X:Type}')) {
            self::addGeneric($line, $mode, $scheme, $where);
            return;
        }

        try {
            $parsed = TLSignatureParser::parse(rtrim($line, ';'));
        } catch (InvalidArgumentException $e) {
            throw new TlParseException("teleframe tokenizer rejected {$where}: {$line} ({$e->getMessage()})", 0, $e);
        }
        self::addParsed($parsed, $mode, $scheme, $where);
    }

    /** Core builtin shapes: no "#id" and the first token is a core type name. */
    private static function isCoreBuiltin(string $line): bool
    {
        if (str_contains($line, '#')) {
            return false;
        }
        $space = strpos($line, ' ');
        $name = $space === false ? $line : substr($line, 0, $space);
        return in_array($name, self::CORE_TYPE_NAMES, true);
    }

    /**
     * Map one ParsedSignature into the fork metamodel. Conditional fields
     * (flagWord+bit) rebuild the byte-exact "name:flags.N?T" token so
     * canonical() crc stays verifiable against the source file.
     */
    private static function addParsed(ParsedSignature $parsed, string $mode, TlScheme $scheme, string $where): void
    {
        $params = [];
        foreach ($parsed->fields as $field) {
            $raw = $field['flagWord'] !== null
                ? $field['flagWord'] . '.' . $field['bit'] . '?' . $field['type']
                : $field['type'];
            $token = $field['name'] . ':' . $raw;
            $params[] = new TlParam($field['name'], $raw, $token);
        }

        if ($mode === 'functions') {
            $def = new TlMethod($parsed->name, $parsed->id, $params, $parsed->returnType);
        } else {
            $def = new TlConstructor($parsed->name, $parsed->id, $params, $parsed->returnType);
        }

        $id = $parsed->hasExplicitId ? $parsed->id : self::crc($def->canonical());
        if (!$parsed->hasExplicitId) {
            // Id-less lines (mtproto.tl tls* family): recompute via the ported
            // TlCanon pipeline — never invent.
            if ($def instanceof TlMethod) {
                $scheme->addMethod(new TlMethod($def->name, $id, $def->allParams(), $def->returnType));
            } else {
                $scheme->addConstructor(new TlConstructor($def->name, $id, $def->allParams(), $def->resultType));
            }
            return;
        }

        $computed = hash('crc32b', TlCanon::normalize($def->canonical()));
        if ($computed !== sprintf('%08x', $id)) {
            // Same policy as the fork parser: stated ids are the wire truth;
            // record and continue.
            $scheme->crcMismatches[$def->name] = [
                'stated' => sprintf('%08x', $id),
                'computed' => $computed,
            ];
        }

        if ($def instanceof TlMethod) {
            $scheme->addMethod($def);
        } else {
            $scheme->addConstructor($def);
        }
    }

    /**
     * Generic combinators ("{t:Type}"/"{X:Type}", "!X", bare Vector t) are
     * outside the tokenizer grammar; map them with fork semantics: generic
     * tokens become filler params, named "x:type" tokens become real params,
     * the return type is the verbatim rhs.
     */
    private static function addGeneric(string $line, string $mode, TlScheme $scheme, string $where): void
    {
        $eq = strpos($line, ' = ');
        if ($eq === false || !str_ends_with($line, ';')) {
            throw new TlParseException("bad generic definition at {$where}: {$line}");
        }
        $lhs = substr($line, 0, $eq);
        $rhs = substr($line, $eq + 3, -1);

        // "#hexid" is an id; a bare "#" (no hex behind it) is a filler param.
        $hash = strpos($lhs, '#');
        $hasId = false;
        $id = 0;
        $name = null;
        $tail = '';
        if ($hash !== false && strspn($lhs, '0123456789abcdefABCDEF', $hash + 1) > 0) {
            $hexLen = strspn($lhs, '0123456789abcdefABCDEF', $hash + 1);
            $hasId = true;
            $id = (int) hexdec(substr($lhs, $hash + 1, $hexLen));
            $name = trim(substr($lhs, 0, $hash));
            $tail = trim(substr($lhs, $hash + 1 + $hexLen));
        } else {
            $space = strpos($lhs, ' ');
            $name = $space === false ? $lhs : substr($lhs, 0, $space);
            $tail = $space === false ? '' : trim(substr($lhs, $space + 1));
        }
        if ($name === '') {
            throw new TlParseException("bad generic name at {$where}: {$line}");
        }

        $params = [];
        if ($tail !== '') {
            foreach (explode(' ', $tail) as $token) {
                if ($token === '') {
                    continue;
                }
                $colon = strpos($token, ':');
                if ($colon !== false && $colon > 0 && $colon + 1 < strlen($token)) {
                    $params[] = new TlParam(substr($token, 0, $colon), substr($token, $colon + 1), $token);
                    continue;
                }
                $params[] = new TlParam('__generic__', $token, $token);
            }
        }

        $canonical = implode(' ', array_filter([$name, $tail, '= ' . $rhs], static fn (string $p): bool => $p !== ''));
        if ($mode === 'functions') {
            $def = new TlMethod($name, $hasId ? $id : self::crc($canonical), $params, $rhs);
            $scheme->addMethod($def);
            if ($hasId) {
                self::recordCrcMismatch($scheme, $def->name, $id, $def->canonical());
            }
        } else {
            $def = new TlConstructor($name, $hasId ? $id : self::crc($canonical), $params, $rhs);
            $scheme->addConstructor($def);
            if ($hasId) {
                self::recordCrcMismatch($scheme, $def->name, $id, $def->canonical());
            }
        }
    }

    private static function recordCrcMismatch(TlScheme $scheme, string $name, int $statedId, string $canonical): void
    {
        $computed = hash('crc32b', TlCanon::normalize($canonical));
        if ($computed !== sprintf('%08x', $statedId)) {
            $scheme->crcMismatches[$name] = [
                'stated' => sprintf('%08x', $statedId),
                'computed' => $computed,
            ];
        }
    }

    /** Ported TlCanon crc pipeline: crc32b over the normalized canonical form. */
    private static function crc(string $canonical): int
    {
        return (int) hexdec(hash('crc32b', TlCanon::normalize($canonical)));
    }
}
