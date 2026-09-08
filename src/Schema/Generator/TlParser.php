<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlConstructor;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlMethod;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * Clean-room TL scheme parser (spec D7 — no AGPL code, public grammar only).
 *
 * Accepted file shapes (ground truth: danog/schemas TL_telegram_v227.tl, TL_mtproto_v1.tl, TL_secret.tl):
 *  - "// comment" lines; "// LAYER N" sets the layer when caller passes layer=0.
 *  - "---types---" / "---functions---" section headers; types is the default section.
 *  - "===N===" section markers (secret scheme cumulative layers): later sections win.
 *  - Generic/templated definitions (params like {X:Type}, rhs like "Vector t" or "[ t ]"):
 *    parsed, crc-verified, marked generic via params; generators skip them.
 *  - Constructor ids are hex, may be shorter than 8 digits (leading zeros dropped).
 */
final class TlParser
{
    public static function parseFile(string $path, int $layer = 0, bool $strict = false): TlScheme
    {
        if (!is_file($path)) {
            throw new TlParseException("scheme file not found: {$path}");
        }
        return self::parseString((string) file_get_contents($path), $layer, $path, $strict);
    }

    public static function parseString(string $tl, int $layer = 0, string $file = '<string>', bool $strict = false): TlScheme
    {
        if ($layer === 0) {
            $layer = self::layerFromComment($tl);
        }
        $scheme = new TlScheme($layer);
        $mode = 'types';

        foreach (explode("\n", $tl) as $lineNo => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '//')) {
                continue;
            }
            if ($line === '---types---' || $line === '---functions---') {
                $mode = $line === '---functions---' ? 'functions' : 'types';
                continue;
            }
            if (str_starts_with($line, '===') && str_ends_with($line, '===')
                && substr($line, 3, -3) !== '' && ctype_digit(substr($line, 3, -3))) {
                continue; // cumulative secret-layer section marker; last-wins applies below
            }
            self::parseDefinition($line, $mode, $scheme, $file, $lineNo + 1, $strict);
        }
        return $scheme;
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

    private static function parseDefinition(string $line, string $mode, TlScheme $scheme, string $file, int $lineNo, bool $strict = false): void
    {
        $where = "{$file}:{$lineNo}";
        if (!str_ends_with($line, ';')) {
            throw new TlParseException("missing ';' at {$where}: {$line}");
        }
        $body = substr($line, 0, -1);
        $eq = strrpos($body, ' = ');
        if ($eq === false) {
            throw new TlParseException("missing '= Type' at {$where}: {$line}");
        }
        $lhs = trim(substr($body, 0, $eq));
        $rhs = trim(substr($body, $eq + 3));

        // Builtins like 'vector {t:Type} # [ t ] = Vector t;' carry no '#id'
        // (the '#' there is a spread marker, not an id).
        $hash = strpos($lhs, '#');
        $withId = $hash === false ? null : self::splitIdAndParams($lhs, $hash);
        if ($withId !== null) {
            [$name, $id, $paramStr] = $withId;
        } else {
            $sp = strpos($lhs, ' ');
            $name = $sp === false ? $lhs : substr($lhs, 0, $sp);
            $paramStr = $sp === false ? '' : trim(substr($lhs, $sp + 1));
            if (!self::isLowerName($name)) {
                throw new TlParseException("missing or bad '#id' at {$where}: {$line}");
            }
            $id = 0;
        }

        if ($name === '' || !self::isCtorName($name)) {
            throw new TlParseException("bad constructor name at {$where}: {$line}");
        }
        if (!self::isResultType($rhs)) {
            throw new TlParseException("bad result type '{$rhs}' at {$where}: {$line}");
        }

        $params = [];
        $generic = false;
        if ($paramStr !== '') {
            foreach (explode(' ', $paramStr) as $token) {
                if ($token === '') {
                    continue;
                }
                $genericArg = self::genericArgName($token);
                if ($genericArg !== null) {
                    $generic = true;
                    $params[] = new TlParam($genericArg, $token, $token);
                    continue;
                }
                if (str_contains($token, ':')) {
                    [$pName, $pType] = explode(':', $token, 2);
                    if ($pName === '' || $pType === '') {
                        throw new TlParseException("bad param token '{$token}' at {$where}");
                    }
                    $params[] = new TlParam($pName, $pType, $token);
                    continue;
                }
                if ($generic || $token === '#' || str_starts_with($token, '[')) {
                    $generic = true;
                    $params[] = new TlParam('__generic__', $token, $token);
                    continue;
                }
                throw new TlParseException("bad param token '{$token}' at {$where}");
            }
        }

        if ($mode === 'functions') {
            $def = new TlMethod($name, $id, $params, $rhs);
        } else {
            $def = new TlConstructor($name, $id, $params, $rhs);
        }
        if ($id !== 0) {
            $computed = hash('crc32b', TlCanon::normalize($def->canonical()));
            if ($computed !== sprintf('%08x', $id)) {
                if ($strict) {
                    throw new TlParseException(sprintf(
                        'crc32 mismatch at %s: stated %s computed %s for "%s"',
                        $where,
                        sprintf('%08x', $id),
                        $computed,
                        $def->canonical(),
                    ));
                }
                // Telegram's internal hashing is not fully reproducible for every
                // historical definition; stated file ids are the wire truth (the
                // reference clients trust them too). Record and continue.
                $scheme->crcMismatches[$def->name] = [
                    'stated' => sprintf('%08x', $id),
                    'computed' => $computed,
                ];
            }
        }

        if ($def instanceof TlMethod) {
            $scheme->addMethod($def);
        } else {
            if ($scheme->types()[$def->resultType] ?? null) {
                $scheme->removeConstructor($def->name);
            }
            $scheme->addConstructor($def);
        }
    }

    /**
     * Regex-free split of the "name#hexid params" grammar branch.
     * Returns [name, id, paramStr], or null when the lhs carries no valid id token.
     *
     * @return array{0:string,1:int,2:string}|null
     */
    private static function splitIdAndParams(string $lhs, int $hash): ?array
    {
        $name = substr($lhs, 0, $hash);
        if (!self::isCtorName($name)) {
            return null;
        }
        $tail = substr($lhs, $hash + 1);
        $hexLen = strspn($tail, '0123456789abcdefABCDEF');
        if ($hexLen === 0) {
            return null;
        }
        if ($hexLen === strlen($tail)) {
            return [$name, (int) hexdec($tail), ''];
        }
        if ($tail[$hexLen] !== ' ') {
            return null;
        }
        return [$name, (int) hexdec(substr($tail, 0, $hexLen)), trim(substr($tail, $hexLen + 1))];
    }

    /** Regex-free [a-z][a-zA-Z0-9_.]* check (constructor/method name). */
    private static function isCtorName(string $s): bool
    {
        if ($s === '' || !ctype_lower($s[0])) {
            return false;
        }
        return strspn($s, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_.') === strlen($s);
    }

    /** Regex-free [a-z][a-z0-9_.]* check (lowercase-only name, id-less branch). */
    private static function isLowerName(string $s): bool
    {
        if ($s === '' || !ctype_lower($s[0])) {
            return false;
        }
        return strspn($s, 'abcdefghijklmnopqrstuvwxyz0123456789_.') === strlen($s);
    }

    /** Regex-free [a-zA-Z][a-zA-Z0-9_.]* check (type name). */
    private static function isTypeName(string $s): bool
    {
        if ($s === '' || !ctype_alpha($s[0])) {
            return false;
        }
        return strspn($s, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_.') === strlen($s);
    }

    /** Regex-free result-type check: TypeName | 'Vector t' | 'Vector<TypeName>'. */
    private static function isResultType(string $s): bool
    {
        if ($s === 'Vector t') {
            return true;
        }
        if (str_starts_with($s, 'Vector<') && str_ends_with($s, '>') && strlen($s) > 8) {
            return self::isTypeName(substr($s, 7, -1));
        }
        return self::isTypeName($s);
    }

    /** Regex-free "{X:Type}" generic-arg check; returns the arg name or null. */
    private static function genericArgName(string $token): ?string
    {
        if (strlen($token) < 8 || !str_starts_with($token, '{') || !str_ends_with($token, ':Type}')) {
            return null;
        }
        $name = substr($token, 1, -6);
        return ctype_alpha($name) ? $name : null;
    }
}
