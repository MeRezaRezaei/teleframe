<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

/**
 * Canonical form of a TL definition for constructor-id (crc32) verification.
 *
 * This is the publicly documented MTProto scheme hashing behavior: the id is
 * crc32 over the definition with the '#hexid' removed and a fixed set of
 * normalizations applied (verified against Telegram's official scheme ids).
 *
 * Transformations, applied in order:
 *  1. ':bytes ' -> ':string '
 *  2. ';' removed
 *  3. '#hexid ' -> ' '  (the id itself, leaving separator space)
 *  4. ' name:flags.N?true' dropped (bare flag-presence params are not hashed)
 *  5. '<' -> ' ' and '>' -> ' '  (Vector<int> becomes 'Vector int')
 *  6. double space collapsed to single (once)
 *  7. leading/trailing space trimmed
 *  8. '?bytes ' -> '?string '
 *  9. '{' and '}' removed (generic type args)
 */
final class TlCanon
{
    public static function normalize(string $definition): string
    {
        $s = $definition;
        $s = str_replace(':bytes ', ':string ', $s);
        $s = str_replace(';', '', $s);
        $s = self::stripHexId($s);
        $s = self::stripFlagTrueParams($s);
        $s = str_replace(['<', '>'], ' ', $s);
        $s = str_replace('  ', ' ', $s);
        $s = trim($s);
        $s = str_replace('?bytes ', '?string ', $s);
        $s = str_replace(['{', '}'], '', $s);
        return $s;
    }

    /** Remove a leading '#<hexid>' token (regex-free): '#' + ≥1 lowercase hex digit + one space. */
    private static function stripHexId(string $s): string
    {
        $hash = strpos($s, '#');
        if ($hash === false) {
            return $s;
        }
        $len = strspn($s, '0123456789abcdef', $hash + 1);
        if ($len === 0 || ($s[$hash + 1 + $len] ?? '') !== ' ') {
            return $s; // e.g. a bare '#' nat param — no id here
        }
        return substr($s, 0, $hash) . ' ' . substr($s, $hash + 1 + $len + 1);
    }

    /** Remove flag-presence params "name:flags.N?true" (regex-free, whole space-separated tokens). */
    private static function stripFlagTrueParams(string $s): string
    {
        $keep = [];
        foreach (explode(' ', $s) as $i => $token) {
            if ($i > 0 && $token !== '' && self::isFlagTrueParam($token)) {
                continue; // regex form required a leading space — index 0 can never match
            }
            $keep[] = $token;
        }
        return implode(' ', $keep);
    }

    /** Regex-free "name:flags.<digits>?true" check. */
    private static function isFlagTrueParam(string $token): bool
    {
        if (!str_ends_with($token, '?true')) {
            return false;
        }
        $head = substr($token, 0, -5);
        $q = strpos($head, '?');
        if ($q !== false) {
            return false; // nested conditional — not the plain flag form
        }
        $colon = strpos($head, ':');
        if ($colon === false || $colon === 0) {
            return false;
        }
        $name = substr($head, 0, $colon);
        $type = substr($head, $colon + 1);
        if (strspn($name, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_') !== strlen($name)) {
            return false; // name must be [a-zA-Z0-9_]+ (non-empty: $colon >= 1; no ':': it is the first colon)
        }
        return str_starts_with($type, 'flags.') && ctype_digit(substr($type, 6));
    }
}
