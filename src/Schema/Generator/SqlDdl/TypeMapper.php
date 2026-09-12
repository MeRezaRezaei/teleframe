<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator\SqlDdl;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;

/**
 * TL type → exact SQL type (§2 of the SQL-extraction plan).
 *
 * map() returns null when the field must stay inside tl_data JSONB
 * (nested ctors, Peer as object, vectors, nat bits). Everything else gets
 * a concrete Postgres DDL fragment derived from the parsed TlParam — the
 * declared .tl type, never a hand-stated guess.
 *
 * Special rule: a `ref` whose base is a Peer type (peerUser/peerChat/
 * peerChannel and friends) is stored as its canonical peer long → BIGINT
 * (spec: canonical peer longs via PeerIdTool::userLong/chatLong/channelLong).
 */
final class TypeMapper
{
    /**
     * @return array{0:string,1:string}|null [pg ddl type, kind] or null (tl_data)
     */
    public static function map(string $field, TlParam $p): ?array
    {
        $kind = $p->kind();

        if ($kind === 'scalar') {
            return self::scalar($field, $p->baseType());
        }

        // flags.N?true (and bare true): a flag bit → boolean, default false.
        if ($kind === 'true') {
            return ['BOOLEAN NOT NULL DEFAULT false', 'flag'];
        }

        if ($kind === 'ref') {
            $base = $p->baseType();
            $isPeer = $base === 'Peer' || str_starts_with($base, 'Peer');
            if ($isPeer) {
                // Canonical peer long (all Peer variants collapse to one long space)
                return ['BIGINT', 'peer-long'];
            }
            if ($base === 'Bool') {
                // boolTrue/boolFalse ctor refs; under a flag carrier absence == false
                return $p->conditional() !== null
                    ? ['BOOLEAN NOT NULL DEFAULT false', 'flag']
                    : ['BOOLEAN', 'bool'];
            }
            return null; // nested constructor → tl_data
        }

        return null; // vector / nat / generic → tl_data
    }

    /** @return array{0:string,1:string}|null */
    private static function scalar(string $field, string $base): ?array
    {
        switch ($base) {
            case 'int':
                return ['INTEGER', 'int'];
            case 'long':
                return ['BIGINT', 'long'];
            case 'double':
                return ['DOUBLE PRECISION', 'double'];
            case 'bytes':
                return ['BYTEA', 'bytes'];
            case 'int128':
                return ['BYTEA(16)', 'bytes'];
            case 'int256':
                return ['BYTEA(32)', 'bytes'];
            case 'string':
                return ['VARCHAR(' . StringLimits::limitFor($field) . ')', 'string'];
            default:
                return null;
        }
    }
}