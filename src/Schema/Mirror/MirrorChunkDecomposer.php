<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

/**
 * Chunk decomposer — the "big update" ingest gate (truth model, cycle 5).
 *
 * Owner verbatim 2026-09-14: "at session register we are going to ask
 * teelgram to send all updates as a chunck and if we can ingest the big
 * update the full shcema is correct this is the way i can make sure my app
 * is working".
 *
 * The big update is the `updates.getDifference` payload: a batch carrying
 * new messages, users and chats alongside the sync state. Decomposing the
 * whole chunk — every fact of every kind — through the mirror is the schema
 * correctness gate: zero FK clues across the chunk means the schema holds;
 * FK clues point at the wrong ingest path (the gradual loop, cycles 2–4).
 *
 * Pure: reads one decoded chunk array, returns rows + clues, no DB, no wire.
 */
final class MirrorChunkDecomposer
{
    public function __construct(
        private readonly MirrorFactDecomposer $facts,
        private readonly MirrorCatalog $catalog,
    ) {}

    /**
     * Decompose a decoded `updates.Difference` payload into mirror rows.
     *
     * @param  int  $accountId  session account id (our scoping key)
     * @param  array<string, mixed>  $chunk  decoded updates.Difference object
     * @return array{
     *     rows: list<array{table: string, row: array<string, int|string|null>}>,
     *     clues: list<string>,
     * }
     */
    public function decomposeChunk(int $accountId, array $chunk): array
    {
        $rows = [];
        $clues = [];

        // new_messages: Vector<Message> → tf_messages (incl. tf_messages_service split).
        foreach ($chunk['new_messages'] ?? [] as $message) {
            if (! is_array($message) || ! isset($message['_'])) {
                $clues[] = 'new_messages: entry without a constructor — cannot place the fact';

                continue;
            }
            $result = $this->decomposeByCtor('tf_messages', $accountId, $message);
            $rows = [...$rows, ...$result['rows']];
            $clues = [...$clues, ...$result['clues']];
        }

        // users: Vector<User> → tf_users (user / userEmpty).
        foreach ($chunk['users'] ?? [] as $user) {
            if (! is_array($user) || ! isset($user['_'])) {
                $clues[] = 'users: entry without a constructor — cannot place the fact';

                continue;
            }
            $result = $this->decomposeByCtor('tf_users', $accountId, $user);
            $rows = [...$rows, ...$result['rows']];
            $clues = [...$clues, ...$result['clues']];
        }

        // chats: Vector<Chat|Channel> → tf_chats (chat / channel ctors).
        foreach ($chunk['chats'] ?? [] as $chat) {
            if (! is_array($chat) || ! isset($chat['_'])) {
                $clues[] = 'chats: entry without a constructor — cannot place the fact';

                continue;
            }
            $result = $this->decomposeByCtor('tf_chats', $accountId, $chat);
            $rows = [...$rows, ...$result['rows']];
            $clues = [...$clues, ...$result['clues']];
        }

        // new_encrypted_messages / other_updates carry facts too, but their
        // constructors are update wrappers (ephemeral by catalog classification)
        // or encrypted payloads — both outside the mirror's fact surface today.
        // They are intentionally NOT decomposed here (absent fact → no row).

        return ['rows' => $rows, 'clues' => $clues];
    }

    /**
     * Decompose a payload against a target table, accepting the catalog's own
     * splitter-derived siblings (tf_messages_service) transparently.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     rows: list<array{table: string, row: array<string, int|string|null>}>,
     *     clues: list<string>,
     * }
     */
    private function decomposeByCtor(string $tfName, int $accountId, array $payload): array
    {
        $entry = $this->catalog->table($tfName);
        $ctor = (string) ($payload['_'] ?? '');

        if (in_array($ctor, $entry->ctors, true)) {
            return $this->facts->decompose($tfName, $accountId, $payload);
        }

        // Not a ctor of the primary table: try the catalog's own splitter
        // sibling (e.g. messageService → tf_messages_service).
        $sibling = $this->catalog->tableNames();
        $service = $tfName.'_service';
        if (in_array($service, $sibling, true)) {
            $serviceEntry = $this->catalog->table($service);
            if (in_array($ctor, $serviceEntry->ctors, true)) {
                return $this->facts->decompose($service, $accountId, $payload);
            }
        }

        return [
            'rows' => [],
            'clues' => ["{$tfName}: constructor '{$ctor}' not found in catalog — cannot place the fact"],
        ];
    }
}
