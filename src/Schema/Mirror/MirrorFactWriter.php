<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;

/**
 * Fact writer — inserts decomposed mirror rows into the relational DB,
 * catching FK constraint violations as ingest clues.
 *
 * Owner verbatim 2026-09-14: "what ever forign key that fails gives us a
 * clue to the wrong path of ingesting we have so it lets us gradually
 * making the real data happen and working".
 *
 * The writer does NOT swallow FK failures — it reports them as clue strings.
 * An ingest path that feeds a child before its parent triggers an FK clue;
 * re-ordering to parent-first fixes the path. This is the core of the
 * "gradually making the real data happen" loop.
 */
final class MirrorFactWriter
{
    /**
     * Insert a list of decomposed rows into the database.
     *
     * @param  list<array{table: string, row: array<string, int|string|null>}>  $rows
     * @return array{
     *     inserted: int,
     *     fkClues: list<string>,
     * }
     */
    public function write(ConnectionInterface $db, array $rows): array
    {
        $inserted = 0;
        $fkClues = [];

        foreach ($rows as ['table' => $table, 'row' => $row]) {
            try {
                $db->table($table)->insert($row);
                $inserted++;
            } catch (QueryException $e) {
                $message = $e->getMessage();
                if (str_contains($message, 'FOREIGN KEY') || str_contains($message, 'foreign key')) {
                    $fkClues[] = "{$table}: FK constraint failed — {$message}";
                } else {
                    throw $e;
                }
            }
        }

        return ['inserted' => $inserted, 'fkClues' => $fkClues];
    }
}
