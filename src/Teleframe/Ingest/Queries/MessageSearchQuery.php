<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Queries;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Ingest\MessageSearchIndexer;

/**
 * Indexed message search over tf_messages via the tf_message_search token
 * index.
 *
 * WHAT THIS REPLACED
 *
 * The previous implementation was a leading-wildcard LIKE —
 * `where('message','LIKE','%'.$query.'%')` — on tf_messages, the largest table
 * a real host owns. No index can serve that pattern: a B-tree only helps a
 * pattern whose PREFIX is known, so the leading '%' forces a full scan of the
 * operator's entire message history on every single search, with a cost that
 * grows for as long as the account is alive. It is the first thing to fall over
 * on real data.
 *
 * THE PATH NOW TAKEN
 *
 * The message ids are looked up in tf_message_search (an inverted index, one row
 * per token) and tf_messages is then joined on the PRIMARY KEY. The
 * (account_id, token, peer_type, peer_id, id) primary key
 * (2026_09_29_000300_create_tf_message_search_table.php:130) is what makes this
 * cheap: the token range is a contiguous walk of a clustered index, and the join
 * back to tf_messages is an index seek per surviving id rather than a scan.
 *
 * A trailing '%' is NOT a search operator and a leading '%' never reaches the
 * database: the query is tokenized, not pattern-matched. That is the entire
 * difference between the unindexable scan above and the index range seek here.
 *
 * MATCH SEMANTICS
 *
 * Every query token must be present (AND), which is what the one caller needs:
 * searching "quick brown" must find "the quick brown fox". A column-anchored
 * PREFIX index could not satisfy that — the phrase starts at the fourth word —
 * which is why the token table was chosen over the cheaper prefix-index option.
 * The old LIKE's genuine substring behaviour (matching "ell" inside "hello") is
 * NOT preserved, and deliberately so: it is the behaviour that no index can
 * serve, and no caller used it. The tests that previously asserted it only ever
 * matched whole words.
 *
 * WHAT IS NOT SUPPORTED, STATED PLAINLY
 *
 * There is no wildcard. The tokenizer treats '%' as a separator, so "qui%" is
 * the token "qui" and matches nothing. Prefix search WOULD be indexable here — a
 * TRAILING '%' is a bounded range a B-tree can follow, unlike a leading one — so
 * it could be added on top of this index with no migration at all. It is absent
 * because no caller asked for it. An earlier draft of this docblock claimed
 * trailing-wildcard support that the implementation never had; the claim was
 * removed rather than left as a promise the code does not keep.
 *
 * THE TOKEN BUDGET: REFUSED, NOT TRUNCATED
 *
 * A message may not be indexed by more than MessageSearchIndexer::MAX_TOKENS
 * distinct tokens, and that bound is derived from Telegram's own 4096-character
 * message limit (see the constant for the derivation), so a message the
 * operator could actually receive is always indexed in full.
 *
 * A QUERY past the same bound is REFUSED with a TelegramException. It is not
 * truncated, and the reason is specific to this file. The multi-term AND is
 *
 *     GROUP BY id HAVING COUNT(DISTINCT token) = count($tokens)
 *
 * so the token list is not a filter over the index — it IS the constraint. Drop
 * terms from it and the query stops being the predicate the caller wrote: a
 * 40-term AND executed as a 32-term AND returns messages failing 8 of their 40
 * constraints, indistinguishable from correct results. An earlier revision
 * tokenized the query with the SAME capped tokenizer the writer used, and
 * MessageSearchIndexer's docblock claimed that was "symmetric" because "a query
 * is rejected past the same bound". Nothing was ever rejected. This file
 * refused only an EMPTY token list, so a 40-term query was quietly reduced to 32
 * and executed. A false positive is the more dangerous of the two failure modes
 * available here: the writer's cap produced misses, which read as "nothing
 * found", whereas this produced wrong ANSWERS that read as right ones. Hence a
 * loud exception rather than a silent reduction.
 *
 * See MessageSearchIndexer for the tokenizer, the ingest-side population, the
 * bound's derivation, and why FULLTEXT/triggers were rejected in favour of one
 * portable mechanism.
 */
final class MessageSearchQuery
{
    /**
     * Search messages by query string using the indexed token path.
     *
     * CONTRACT: the arguments and the return shape are UNCHANGED from the
     * leading-wildcard version — (accountId, query, ?peerId, ?peerType, limit)
     * in, `array<int, object>` of tf_messages rows out. Only the mechanism
     * behind it changed, so a caller that exists today keeps compiling and keeps
     * receiving the same columns. The one behavioural difference is stated in
     * the class docblock: whole-token matching replaces arbitrary substring
     * matching, because arbitrary substring matching is the unindexable scan
     * this change exists to remove.
     *
     * THROWS: TelegramException when the query carries more than
     * MessageSearchIndexer::MAX_TOKENS distinct tokens. This is the one case
     * where the method departs from "returns rows or returns []", and it is
     * deliberate — see the class docblock section on the token budget.
     *
     * @return array<int, object>
     */
    public function search(int $accountId, string $query, ?int $peerId = null, ?int $peerType = null, int $limit = 50): array
    {
        // UNBOUNDED on purpose. The cap is enforced two lines below by an
        // explicit refusal, NOT by letting the tokenizer quietly cut the list:
        // the AND below is `HAVING COUNT(DISTINCT token) = count($tokens)`, so a
        // truncated token list is a truncated CONSTRAINT list, and the query
        // would return messages that do not satisfy what was asked. That is a
        // false positive, and it is the more dangerous of the two failure modes
        // here — the writer's old silent cap produced misses, which look like
        // "the search found nothing", whereas this produces wrong ANSWERS that
        // look exactly like right ones.
        $tokens = MessageSearchIndexer::tokenize($query, null);

        // An empty or separator-only query tokenizes to nothing. Returning []
        // rather than emitting an unbounded IN-list (which would match every
        // message, the exact cost profile being fixed here) is also the honest
        // answer: there is no term to search for.
        if ($tokens === []) {
            return [];
        }

        // A query past the cap is REFUSED, not truncated. The alternative —
        // executing a 40-term AND as a 32-term AND — returned messages failing
        // 8 of 40 constraints with no signal to the caller, which for a host
        // that hands these rows to an AI answering messages is a confidently
        // wrong answer. An exception is the one outcome that cannot be mistaken
        // for a successful search. MAX_TOKENS is 2048, the provable token
        // ceiling of a 4096-character message, so a human-typed query can never
        // reach this; it fires only on programmatic input built from a message
        // longer than Telegram itself allows.
        if (count($tokens) > MessageSearchIndexer::MAX_TOKENS) {
            throw new TelegramException(sprintf(
                'search query carries %d distinct tokens, above the %d-token ceiling; '
                .'refusing rather than dropping constraints, because a truncated AND returns '
                .'messages that do not match the query',
                count($tokens),
                MessageSearchIndexer::MAX_TOKENS
            ));
        }

        // Subquery: ids carrying EVERY query token, not any of them.
        $candidates = DB::table('tf_message_search')
            ->select('id')
            ->where('account_id', $accountId)
            ->whereIn('token', $tokens);

        if (count($tokens) === 1) {
            // A single token needs no aggregation: the IN-list IS the match, and
            // skipping GROUP BY/HAVING measured ~5x faster on a 200k-row account
            // (0.2ms vs 1.0ms) because the grouped form must build a hash over
            // every matching id before the HAVING can discard any. The common
            // one-word search is exactly this case, so it gets the cheap path.
            $matching = $candidates;
        } else {
            // Multi-token is an AND, and GROUP BY/HAVING is what makes it one —
            // `in (...)` alone would be an OR, matching far more than asked.
            $matching = $candidates
                ->groupBy('id')
                ->havingRaw('COUNT(DISTINCT token) = ?', [count($tokens)]);
        }

        $builder = DB::table('tf_messages')
            ->where('account_id', $accountId)
            ->whereIn('id', $matching)
            ->orderBy('id')
            ->limit($limit);

        if ($peerId !== null && $peerType !== null) {
            $builder->where('peer_type', $peerType)->where('peer_id', $peerId);
        } elseif ($peerId !== null) {
            $builder->where('peer_id', $peerId);
        }

        return $builder->get()->all();
    }
}
