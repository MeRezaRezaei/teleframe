<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator\Model;

/**
 * One TL parameter with parsed type info.
 *
 * Raw type grammar (clean-room, from the public TL spec):
 *   raw      := conditional? inner
 *   conditional := name '.' bit '?'
 *   inner    := 'Vector<' inner '>' | 'vector<' inner '>' | '%' ref | '!' ref | scalar | ref | '{' scalar '}'
 *
 * $token is the verbatim source token ("name:raw" or a generic filler like "#") —
 * canonical() uses it so crc32 recomputation is byte-exact against the scheme file.
 */
final class TlParam
{
    private const SCALARS = ['int', 'long', 'double', 'string', 'bytes', 'int128', 'int256', '#', 'true'];

    public readonly string $raw;
    public readonly bool $isFiller;
    /** @var ?array{0:string,1:int} */
    private readonly ?array $conditional;
    private readonly string $kind;
    private readonly string $base;
    private readonly bool $bare;
    private readonly bool $any;

    public function __construct(
        public readonly string $name,
        string $rawType,
        ?string $token = null,
    ) {
        $this->raw = $rawType;
        $this->isFiller = $token !== null && $token === $rawType && $name === '__generic__';
        $this->token = $token ?? ($name . ':' . $rawType);

        $rest = $rawType;
        $cond = null;
        // conditional prefix "carrier.bit?inner" (regex-free): split at first '?',
        // left side must be lowerName.digits, right side must be non-empty.
        $q = strpos($rest, '?');
        if ($q !== false && $q > 0) {
            $left = substr($rest, 0, $q);
            $inner = substr($rest, $q + 1);
            $dot = strpos($left, '.');
            if ($dot !== false && $inner !== '' && self::isLowerName(substr($left, 0, $dot))
                && ctype_digit(substr($left, $dot + 1))) {
                $cond = [substr($left, 0, $dot), (int) substr($left, $dot + 1)];
                $rest = $inner;
            }
        }
        $this->conditional = $cond;

        $bare = false;
        $any = false;
        if ($rest !== '' && $rest[0] === '%') {
            $bare = true;
            $rest = substr($rest, 1);
        }
        if ($rest !== '' && $rest[0] === '!') {
            $any = true;
            $rest = substr($rest, 1);
        }
        $this->bare = $bare;
        $this->any = $any;

        if ($this->isFiller) {
            $this->kind = 'generic';
            $this->base = $rawType;
            return;
        }

        // generic arg "{X:Type}" (regex-free): braces around letters + ':Type'.
        if (str_starts_with($rest, '{') && str_ends_with($rest, '}') && str_ends_with($rest, ':Type}')) {
            $genericName = substr($rest, 1, -6);
            if ($genericName !== '' && ctype_alpha($genericName)) {
                $this->kind = 'generic';
                $this->base = $genericName;
                return;
            }
        }

        // vector "Vector<inner>" / "vector<inner>" (regex-free; inner must be non-empty).
        foreach (['Vector<', 'vector<'] as $prefix) {
            $inner = str_starts_with($rest, $prefix) && str_ends_with($rest, '>') && strlen($rest) > strlen($prefix) + 1
                ? substr($rest, strlen($prefix), -1)
                : null;
            if ($inner !== null) {
                $this->kind = 'vector';
                $this->base = $inner;
                return;
            }
        }

        $stripped = $rest;
        if ($stripped !== '' && $stripped[0] === '{' && substr($stripped, -1) === '}' && !str_contains(substr($stripped, 1, -1), ':')) {
            $stripped = substr($stripped, 1, -1);
        }
        if ($stripped === '#') {
            $this->kind = 'nat';
            $this->base = '#';
            return;
        }
        if ($stripped === 'true') {
            $this->kind = 'true';
            $this->base = 'true';
            return;
        }
        if (in_array($stripped, self::SCALARS, true)) {
            $this->kind = 'scalar';
            $this->base = $stripped;
            return;
        }
        $this->kind = 'ref';
        $this->base = $stripped;
    }

    public readonly string $token;

    /** Regex-free [a-z][a-zA-Z0-9_]* check. */
    private static function isLowerName(string $s): bool
    {
        if ($s === '' || $s[0] < 'a' || $s[0] > 'z') {
            return false;
        }
        return strspn($s, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_') === strlen($s);
    }

    /** @return 'scalar'|'ref'|'vector'|'nat'|'true'|'generic' */
    public function kind(): string
    {
        return $this->kind;
    }

    /** Element type for vectors, target for refs, scalar name for scalars. Raw inner for nested vectors. */
    public function baseType(): string
    {
        return $this->base;
    }

    /** @return ?array{0:string,1:int} flag carrier name + bit, else null */
    public function conditional(): ?array
    {
        return $this->conditional;
    }

    public function isBare(): bool
    {
        return $this->bare;
    }

    public function isAny(): bool
    {
        return $this->any;
    }
}
