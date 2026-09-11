<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Queries;

use Illuminate\Support\Facades\DB;

/**
 * Full-text search on tf_messages. Uses Postgres tsvector + GIN index
 * when available, with a LIKE fallback for SQLite (tests).
 */
final class MessageSearchQuery
{
    /**
     * Search messages by query string, returning results ranked by relevance.
     *
     * @return array<int, object>
     */
    public function search(int $accountId, string $query, ?int $peerId = null, int $limit = 50): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            return $this->searchPgsql($accountId, $query, $peerId, $limit);
        }

        return $this->searchLike($accountId, $query, $peerId, $limit);
    }

    private function searchPgsql(int $accountId, string $query, ?int $peerId, int $limit): array
    {
        $builder = DB::table('tf_messages')
            ->where('account_id', $accountId)
            ->whereRaw('search_vector @@ plainto_tsquery(\'english\', ?)', [$query])
            ->select('*', DB::raw("ts_rank(search_vector, plainto_tsquery('english', ?)) AS rank", [$query]))
            ->orderByDesc('rank')
            ->limit($limit);

        if ($peerId !== null) {
            $builder->where('peer_id', $peerId);
        }

        return $builder->get()->all();
    }

    private function searchLike(int $accountId, string $query, ?int $peerId, int $limit): array
    {
        $builder = DB::table('tf_messages')
            ->where('account_id', $accountId)
            ->where('message_text', 'LIKE', '%' . $query . '%')
            ->limit($limit);

        if ($peerId !== null) {
            $builder->where('peer_id', $peerId);
        }

        return $builder->get()->all();
    }
}
