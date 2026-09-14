<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumn;

/**
 * Fact decomposer — the mirror ingest write path (truth model, cycle 2).
 *
 * Owner verbatim 2026-09-14: "suppose we have the telgram data came from its
 * mtproto and they simple shoould be insertable into the fully nf 5 fully
 * migrated realational database and what ever forign key that fails gives us
 * a clue to the wrong path of ingesting we have".
 *
 * One decoded TL payload (['_' => constructor, field => value]) decomposes
 * into insertable rows for the parent table. The schema is the contract:
 * - every row carries (account_id, telegram-supplied key...) — we never invent
 *   identity (telegram-reliance invariant, cycle 1);
 * - booleans store 0/1; peer shapes expand to {field}_type / {field}_id;
 * - a constructor the catalog does not know, or a payload that cannot fill a
 *   keying/peer column, is reported as an ingest clue — NOT swallowed.
 *
 * Children decomposition (vector/union children tables) is the next cycle.
 */
final class MirrorFactDecomposer
{
    public function __construct(
        private readonly MirrorTableResolver $resolver,
        private readonly MirrorCatalog $catalog,
    ) {}

    /**
     * Decompose a decoded TL payload into insertable rows for a parent table.
     *
     * @param  int  $accountId  session account id (our scoping key)
     * @param  array<string, mixed>  $payload  decoded TL object: ['_' => ctor, field => value]
     * @return array{
     *     rows: list<array{table: string, row: array<string, int|string|null>}>,
     *     clues: list<string>,
     * }
     */
    public function decompose(string $tfName, int $accountId, array $payload): array
    {
        $entry = $this->catalog->table($tfName);
        $ctor = (string) ($payload['_'] ?? '');

        if (! in_array($ctor, $entry->ctors, true)) {
            return [
                'rows' => [],
                'clues' => ["{$tfName}: constructor '{$ctor}' is not in catalog ctors — cannot place the fact"],
            ];
        }

        $table = $this->resolver->resolveAll([$tfName])[0];
        $row = ['account_id' => $accountId, 'constructor' => $ctor];
        $clues = [];

        foreach ($table->columns as $column) {
            if ($column->peer) {
                $this->fillPeerHalf($column, $payload, $row, $clues, $tfName);

                continue;
            }

            $field = $column->name;

            if ($column->name === 'constructor') {
                continue; // discriminator already set
            }

            if ($column->name === 'account_id') {
                continue; // scoping key, set by the caller
            }

            if (in_array($column->name, $table->keyColumns, true)) {
                $value = $payload[$field] ?? null;
                if ($value === null) {
                    $clues[] = "{$tfName}: keying column '{$column->name}' missing from payload — cannot place the fact";

                    continue;
                }
                $row[$column->name] = $this->castValue($value, $column, $clues, $tfName, $column->name);

                continue;
            }

            if (in_array($column->name, $table->booleanColumns, true)) {
                $row[$column->name] = (int) (bool) ($payload[$field] ?? false);

                continue;
            }

            $value = $payload[$field] ?? null;
            if ($value !== null) {
                $row[$column->name] = $this->castValue($value, $column, $clues, $tfName, $column->name);
            }
        }

        return ['rows' => [['table' => $tfName, 'row' => $row]], 'clues' => $clues];
    }

    /**
     * Fill one half of a peer pair. A peer column pair ({field}_type + {field}_id,
     * both peer=true) reads from the single payload field {field} as an array:
     * ['_type'|'type' => int, '_id'|'id' => int]. Each half writes only its own
     * column so the (account_id, key) row stays complete.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, int|string|null>  $row
     * @param  list<string>  $clues
     */
    private function fillPeerHalf(MirrorColumn $column, array $payload, array &$row, array &$clues, string $tfName): void
    {
        $isType = str_ends_with($column->name, '_type');
        $field = $isType
            ? substr($column->name, 0, -5)
            : substr($column->name, 0, -3);

        $value = $payload[$field] ?? null;
        if (! is_array($value)) {
            $clues[] = "{$tfName}: peer column '{$field}' missing from payload — cannot place the fact";

            return;
        }

        $row[$column->name] = $isType
            ? (int) ($value['_type'] ?? $value['type'] ?? 0)
            : (int) ($value['_id'] ?? $value['id'] ?? 0);
    }

    /** @param list<string> $clues */
    private function castValue(mixed $value, MirrorColumn $column, array &$clues, string $tfName, string $field): int|string|null
    {
        if (is_int($value) || is_float($value)) {
            return (int) $value;
        }
        if (is_string($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return (int) $value;
        }

        $clues[] = "{$tfName}: column '{$field}' got an uncastable value (type ".get_debug_type($value).')';

        return null;
    }
}
