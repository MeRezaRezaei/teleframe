<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use RuntimeException;

/**
 * Bijective, prefix-free TL ↔ PHP/SQL name mapping (spec §4.7).
 */
final class Naming
{
    /** SQL/Laravel-reserved or awkward words: param columns get the tl_ prefix. */
    private const RESERVED = [
        'id', 'type', 'default', 'order', 'operator', 'limit', 'offset', 'from',
        'unique', 'check', 'references', 'table', 'user', 'size', 'value',
        'index', 'primary', 'key', 'constraint', 'exclude', 'long', 'share',
        'all', 'and', 'any', 'asc', 'authorization', 'both', 'case', 'cast',
        'character', 'collate', 'column', 'current', 'desc', 'distinct', 'do',
        'end', 'except', 'false', 'for', 'grant', 'group', 'having', 'in',
        'intersect', 'into', 'leading', 'not', 'null', 'only', 'or', 'placing',
        'select', 'some', 'then', 'to', 'trailing', 'true', 'union', 'using',
        'variadic', 'when', 'window', 'with',
    ];

    /** PG identifier limit is 63 bytes; long names are shortened with a stable hash tail. */
    public static function fit(string $name): string
    {
        if (strlen($name) <= 58) {
            return $name;
        }
        return substr($name, 0, 45) . '_' . substr(sha1($name), 0, 12);
    }

    /** Drop the ctor namespace when it repeats the type namespace: (messages.Dialogs, messages.dialogsSlice) → dialogsSlice */
    public static function dedupeNamespace(string $tlType, string $ctorName): string
    {
        $typeNs = str_contains($tlType, '.') ? substr($tlType, 0, (int) strrpos($tlType, '.')) : null;
        $ctorNs = str_contains($ctorName, '.') ? substr($ctorName, 0, (int) strrpos($ctorName, '.')) : null;
        if ($typeNs !== null && $ctorNs === $typeNs) {
            return substr($ctorName, (int) strrpos($ctorName, '.') + 1);
        }
        return $ctorName;
    }

    public static function constructorTable(string $tlType, string $ctorName): string
    {
        $ctor = self::dedupeNamespace($tlType, $ctorName);
        return self::fit('tl_' . self::snake($tlType) . '_' . self::snake($ctor));
    }

    public static function childTable(string $parentTable, string $param): string
    {
        return self::fit($parentTable . '__' . self::snake($param));
    }

    public static function column(string $param): string
    {
        $snake = self::snake($param);
        return in_array($snake, self::RESERVED, true) ? 'tl_' . $snake : $snake;
    }

    public static function model(string $tlType): string
    {
        return 'Tl' . self::pascal($tlType);
    }

    public static function ctorModel(string $tlType, string $ctorName): string
    {
        return 'Tl' . self::pascal($tlType) . self::pascal(self::dedupeNamespace($tlType, $ctorName));
    }

    public static function dataClass(string $ctorOrMethodName): string
    {
        $pascal = self::pascal($ctorOrMethodName);
        return str_contains($ctorOrMethodName, '.')
            ? 'Tl' . $pascal . 'Data'
            : $pascal . 'Data';
    }

    public static function abstractDataClass(string $tlType): string
    {
        return 'Tl' . self::pascal($tlType) . 'AbstractData';
    }

    /** Postgres column type for a scalar/nat/true/ref param (vectors: child tables). */
    public static function dbType(TlParam $p, bool $precision = false): string
    {
        if ($p->kind() === 'vector' || $p->kind() === 'generic') {
            throw new \InvalidArgumentException('param "' . $p->name . '" is a vector/generic — use a child table, not a column');
        }
        return match ($p->baseType()) {
            'int' => 'integer',
            'long' => 'bigint',
            'int128' => $precision ? 'numeric(39,0)' : 'numeric',
            'int256' => $precision ? 'numeric(78,0)' : 'numeric',
            'double' => 'double',
            'string' => 'text',
            'bytes' => 'binary',
            '#' => 'bigint',
            'true' => 'boolean',
            default => 'bigint', // ref — Telegram native ID (not a UUID)
        };
    }

    public static function cast(TlParam $p): string
    {
        return match ($p->baseType()) {
            'int' => 'int',
            'long' => 'int',
            'int128', 'int256' => 'string',
            'double' => 'float',
            'string' => 'string',
            'bytes' => 'string',
            '#' => 'int',
            'true' => 'bool',
            default => 'int', // ref — Telegram native ID
        };
    }

    /**
     * @param list<string> $names
     * @throws RuntimeException on duplicates (spec §7.3 V3)
     */
    public static function assertUnique(array $names, string $kind): void
    {
        $seen = [];
        foreach ($names as $n) {
            if (isset($seen[$n])) {
                throw new RuntimeException("naming collision ({$kind}): {$n} generated twice");
            }
            $seen[$n] = true;
        }
    }

    /** dotted CamelCase → snake_case: 'messages.dialogsSlice' → 'messages_dialogs_slice' */
    public static function snake(string $dotted): string
    {
        $parts = explode('.', $dotted);
        $snaked = array_map(
            static fn (string $s): string => strtolower(self::camelToSnake($s)),
            $parts,
        );
        return implode('_', $snaked);
    }

    /** Regex-free camel→snake: '_' before every uppercase letter that is not the first character. */
    private static function camelToSnake(string $s): string
    {
        $out = '';
        foreach (str_split($s) as $i => $ch) {
            if ($i > 0 && $ch >= 'A' && $ch <= 'Z') {
                $out .= '_';
            }
            $out .= $ch;
        }
        return $out;
    }

    /** dotted/snake name → PascalCase path: 'messages.dialogsSlice', 'msgs_state_info' → 'MessagesDialogsSlice', 'MsgsStateInfo' */
    public static function pascal(string $dotted): string
    {
        $dotted = str_replace('_', '.', $dotted);
        $parts = explode('.', $dotted);
        return implode('', array_map(
            static fn (string $s): string => ucfirst($s),
            $parts,
        ));
    }
}
