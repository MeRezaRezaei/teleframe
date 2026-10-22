<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Support;

use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSignatureParser;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;

/**
 * Builds a synthesised argument tree for ANY TL constructor, so a test can
 * assert a method really encodes rather than merely "has a signature".
 *
 * Why this exists: a signature being registered is necessary but not
 * sufficient to say a method is callable — the encoder has to resolve every
 * nested object type down to a concrete constructor it can serialise. This
 * helper walks that whole tree the way a host's real values would, and fails
 * loudly on a type no constructor satisfies, so a test never passes by giving
 * up early.
 *
 * The constructor index is built from the SAME mirror files TLRegistry loads
 * (`api_full.tl`, `mtproto_full.tl`), which keeps the fixture honest: it can
 * only offer a nested value the encoder could really produce. No reflection
 * into registry internals, so a refactor of TLRegistry's storage cannot
 * silently change what these tests measure.
 */
final class EncodableArgs
{
    /** Scalar leaf values, by TL type. `true` carries no bytes. */
    private const SCALARS = [
        'int' => 0,
        'long' => 0,
        'double' => 0.0,
        'int128' => '0',
        'int256' => '0',
        'string' => '',
        'bytes' => '',
        'true' => '',
    ];

    /** Types that are not constructors and need no value of their own. */
    private const BARE = ['#', 'flags', 'Type', 'X'];

    private const MAX_DEPTH = 4;

    /** @var array<string, string> TL type => constructor name that satisfies it */
    private static ?array $byReturnType = null;

    /**
     * Args for a top-level method: every non-flag field populated, every
     * conditional field left absent (bit clear) so the encoder takes the
     * "flag clear" path it must support for every method.
     *
     * @return array<string, mixed>|null null when some field type cannot be resolved
     */
    public static function forConstructor(string $constructor): ?array
    {
        $fields = self::fieldsOf($constructor);
        if ($fields === null) {
            return null;
        }

        $args = [];
        foreach ($fields as [$name, $type]) {
            $value = self::value($type, 0);
            if ($value === null) {
                return null;
            }
            $args[$name] = $value;
        }

        return $args;
    }

    /**
     * A value for one TL type, recursing into constructors.
     *
     * @return mixed null when the type is not encodable (so the caller can
     *               report it rather than assert something weaker)
     */
    public static function value(string $type, int $depth = 0): mixed
    {
        if (isset(self::SCALARS[$type])) {
            return self::SCALARS[$type];
        }
        if ($type === 'Vector' || str_starts_with($type, 'Vector<')) {
            return []; // an empty vector is well-formed and needs no element
        }
        if (in_array($type, self::BARE, true) || $depth > self::MAX_DEPTH) {
            return null;
        }

        $constructor = self::byReturnType()[$type] ?? null;
        if ($constructor === null) {
            return null;
        }

        $nested = self::forConstructor($constructor);
        if ($nested === null) {
            return null;
        }

        return ['_' => $constructor] + $nested;
    }

    /**
     * Every type the fixture could not satisfy, collected across a run. Lets a
     * test say WHICH type is unresolvable instead of just "it failed".
     *
     * @var array<string, true>
     */
    private static array $unresolved = [];

    /** @return list<string> */
    public static function unresolvedTypes(): array
    {
        return array_keys(self::$unresolved);
    }

    public static function noteUnresolved(string $type): void
    {
        self::$unresolved[$type] = true;
    }

    /**
     * TL type => a constructor in the wire mirror that returns it.
     *
     * @return array<string, string>
     */
    private static function byReturnType(): array
    {
        if (self::$byReturnType !== null) {
            return self::$byReturnType;
        }

        $map = [];
        foreach (['api_full.tl', 'mtproto_full.tl'] as $file) {
            try {
                $path = SchemaArtifacts::source($file);
            } catch (\Throwable) {
                continue;
            }
            if (! is_file($path)) {
                continue;
            }
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $raw) {
                $line = trim($raw, " \t;");
                if ($line === '' || $line[0] === '/' || str_starts_with($line, '---')) {
                    continue;
                }
                if (! str_contains($line, '#') || str_contains($line, '{')) {
                    continue; // the loader skips these, so they cannot resolve a value
                }
                try {
                    $return = TLSignatureParser::parse($line)->returnType;
                } catch (\Throwable) {
                    continue;
                }
                if (! is_string($return) || $return === '' || isset($map[$return])) {
                    continue;
                }
                $name = substr($line, 0, (int) strpos($line, '#'));
                $map[$return] = $name;
            }
        }

        return self::$byReturnType = $map;
    }

    /** Test seam: forget the memoised index and unresolved-type log. */
    public static function flush(): void
    {
        self::$byReturnType = null;
        self::$unresolved = [];
    }

    /**
     * Every non-flag, non-conditional field of a registered constructor, as
     * `[name, type]` pairs. Resolved through `TLRegistry::signatureOf()` —
     * the same public closure the encoder uses, so the fixture cannot invent a
     * field the encoder would not see.
     *
     * @return list<array{0: string, 1: string}>|null null when the constructor is not registered
     */
    private static function fieldsOf(string $constructor): ?array
    {
        try {
            $parsed = TLRegistry::signatureOf($constructor);
        } catch (\Throwable) {
            return null;
        }

        $fields = [];
        foreach ($parsed->fields as $field) {
            if ($field['type'] === '#' || $field['type'] === 'flags') {
                continue;
            }
            if ($field['flagWord'] !== null) {
                continue;
            }
            $fields[] = [$field['name'], $field['type']];
        }

        return $fields;
    }
}
