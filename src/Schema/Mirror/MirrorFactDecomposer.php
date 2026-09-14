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

        return $this->decomposeChildren($tfName, $accountId, $row, $payload, $clues);
    }

    /**
     * Decompose a parent row's children into child-table rows.
     *
     * Every child table of a parent is keyed by (account_id, parent keyColumns
     * [+ position]) — the FK that links the child back to its parent. A child
     * row can only be placed when its parent key exists; a payload field that
     * carries a nested object/vector becomes child rows; a missing payload
     * field means the fact is absent (row existence = fact existence).
     *
     * @param  int  $accountId  scoping key for every produced row
     * @param  array<string, int|string|null>  $parentRow  the already-decomposed parent row
     * @return array{
     *     rows: list<array{table: string, row: array<string, int|string|null>}>,
     *     clues: list<string>,
     * }
     */
    public function decomposeChildren(string $tfName, int $accountId, array $parentRow, array $payload, array $carriedClues = []): array
    {
        $table = $this->resolver->resolveAll([$tfName])[0];
        $rows = [['table' => $tfName, 'row' => $parentRow]];
        $clues = $carriedClues;

        foreach ($table->children as $child) {
            if ($child->parentTf !== $tfName) {
                continue; // shared catalog node (tf_message_medias, tf_chats, ...): own context, own key
            }

            $plain = (string) substr($child->tfName, strlen($tfName) + 1);
            $value = $payload[$plain] ?? null;

            if ($value === null) {
                continue; // absent fact → no row (row existence = fact existence)
            }

            if ($child->positioned) {
                // Vector children: every element becomes one positioned row.
                if (! is_array($value) || array_is_list($value) === false && ! $this->isVectorList($value)) {
                    $clues[] = "{$child->tfName}: positioned child expects a list payload for '{$plain}'";

                    continue;
                }
                $items = $this->vectorItems($value);
                foreach ($items as $position => $item) {
                    $rows[] = [
                        'table' => $child->tfName,
                        'row' => $this->childRow($child, $accountId, $parentRow, $position, $item, $clues),
                    ];
                }

                continue;
            }

            $rows[] = [
                'table' => $child->tfName,
                'row' => $this->childRow($child, $accountId, $parentRow, null, is_array($value) ? $value : [$plain => $value], $clues),
            ];
        }

        return ['rows' => $rows, 'clues' => $clues];
    }

    /**
     * @param  int|null  $position  set for positioned (vector) children
     * @param  array<string, mixed>  $item  nested payload for this child
     * @param  list<string>  $clues
     * @return array<string, int|string|null>
     */
    private function childRow(MirrorTable $child, int $accountId, array $parentRow, ?int $position, array $item, array &$clues): array
    {
        $row = ['account_id' => $accountId];
        if ($child->constructor !== '') {
            $row['constructor'] = (string) ($item['_'] ?? $child->constructor);
        }

        foreach ($child->columns as $column) {
            if ($column->name === 'account_id') {
                continue;
            }
            if ($column->name === 'position') {
                $row[$column->name] = $position;

                continue;
            }
            if ($column->peer) {
                $this->fillPeerHalf($column, $item, $row, $clues, $child->tfName);

                continue;
            }
            if (in_array($column->name, $child->keyColumns, true)) {
                // Parent key travels into the child row — the FK that links them.
                if (array_key_exists($column->name, $parentRow)) {
                    $row[$column->name] = $parentRow[$column->name];
                } else {
                    $clues[] = "{$child->tfName}: parent key column '{$column->name}' missing from parent row — cannot link FK";
                }

                continue;
            }
            if ($column->name === 'constructor') {
                continue;
            }
            if (in_array($column->name, $child->booleanColumns, true)) {
                $row[$column->name] = (int) (bool) ($item[$column->name] ?? false);

                continue;
            }
            if (array_key_exists($column->name, $item)) {
                $row[$column->name] = $this->castValue($item[$column->name], $column, $clues, $child->tfName, $column->name);
            }
        }

        return $row;
    }

    /** A structurally-valid vector payload: list of arrays, or associative with numeric keys. */
    private function isVectorList(array $value): bool
    {
        foreach (array_keys($value) as $k) {
            if (! is_int($k)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<mixed> */
    private function vectorItems(array $value): array
    {
        return array_values($value);
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
