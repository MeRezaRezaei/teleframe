<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * Tokenizer + writer for the tf_message_search inverted index.
 *
 * WHY A TOKEN TABLE AND NOT A FULLTEXT INDEX
 *
 * The brief for this index offered FULLTEXT (MySQL) / tsvector (Postgres) or a
 * token table, and the token table is the only one of the three that is ONE
 * mechanism on every driver the project ships (MySQL 8.4, Postgres 17, SQLite
 * for the logic suite). The two native full-text stories are not merely
 * different in syntax, they are different in KIND:
 *
 *   - MySQL   FULLTEXT is an index type on a column; matching goes through
 *             MATCH()...AGAINST() and is subject to innodb_ft_min_token_size
 *             (3) and innodb_ft_stopword_list, so a two-letter token silently
 *             matches nothing on one host and everything on another.
 *   - Postgres has no FULLTEXT index at all. It needs a tsvector column, which
 *             is either a GENERATED ALWAYS AS ... STORED (Laravel Blueprint has
 *             no portable spelling) or a trigger function plus a per-row UPDATE
 *             trigger — dialect-specific DDL that a Schema::table() call cannot
 *             express and that no SQLite test run can ever exercise.
 *   - SQLite  has neither; it needs an FTS5 virtual table, which is a different
 *             kind of object (its own DDL dialect, its own triggers) and which
 *             the MySQL portability scanner has no vocabulary for.
 *
 * A trigger was rejected for the same reason as the native indexes: triggers are
 * the one place where the three dialects share nothing but the word, so a
 * trigger-based population path could only ever be verified on ONE of the three
 * drivers. Population is therefore ingest-side, in PHP, and is byte-identical
 * on all three.
 *
 * WHAT THE INDEX SERVES
 *
 * tf_message_search is (account_id, token, peer_type, peer_id, id) rows: one
 * per token of one message, each carrying the message's own peer-scoped key so
 * two dialogs' message 5 cannot collide. A query is tokenized by the SAME
 * tokenizer, and a message matches when it carries EVERY query token
 * (AND, not OR). That is the whole contract, and it is the contract the only
 * caller needs: search for "quick brown" must find "the quick brown fox", which
 * a column-anchored prefix index cannot do, because the phrase starts at the
 * fourth word. It is also what a substring LIKE was being asked for by mistake
 * — no caller searches for "ell" expecting "hello"; the tests that used to be
 * named "substrings" only ever exercised whole words.
 *
 * WHY PREFIX SEARCH STILL WORKS, AND WHY IT IS STILL INDEXED
 *
 * A token may be matched with `token LIKE 'foo%'`. That is a LEADING-ANCHORED
 * pattern, which is exactly the shape a B-tree CAN serve: the index on
 * (account_id, token) is scanned only over the 'foo%' range. This is the one
 * deliberate difference from the leading-wildcard defect being fixed — the
 * wildcard moved from the front of the pattern to the back, where an index can
 * follow it. See MessageSearchQuery for the plan assertion that proves it.
 *
 * THE TOKEN BUDGET, AND WHY IT IS NOT SYMMETRIC
 *
 * A cap on tokens per message is necessary (it bounds the row budget of one
 * message and the IN-list of one query) and the constant below is derived from
 * Telegram's own 4096-character message limit rather than picked. What the cap
 * must NEVER do is apply itself silently to a search query, and the two sides
 * are therefore NOT symmetric:
 *
 *   - the WRITER truncates at MAX_TOKENS and LOGS that it did;
 *   - the READER refuses a query past MAX_TOKENS outright, via an exception.
 *
 * An earlier revision of this file claimed the opposite — that the cap was
 * "applied to BOTH sides, which is symmetric: a query is rejected past the same
 * bound, so the two can never disagree about which tokens exist". No query was
 * ever rejected: MessageSearchQuery only ever refused an EMPTY token list, and a
 * 40-term query was quietly reduced to 32 and then executed. Because the AND is
 * `HAVING COUNT(DISTINCT token) = count($tokens)`, those 8 dropped terms were
 * 8 dropped CONSTRAINTS, so a message carrying only w1..w32 matched a query
 * demanding w1..w40. A claimed safety property that the code did not implement
 * is worse than no claim, which is why this note now describes what the two
 * sides actually do. See MessageSearchQuery for the reader half.
 *
 * TOKENIZATION IS DELIBERATELY ASCII-SEPARATOR-ONLY
 *
 * Zero-regex is enforced over src/Teleframe, so the tokenizer is strtr() over a
 * literal separator map plus explode(). The consequence is stated rather than
 * hidden: a non-ASCII separator (an em dash, a curly quote, CJK) is NOT a token
 * boundary, so "don't—stop" yields one token rather than two. Widening this
 * needs either a preg_split (banned here) or a hand-rolled UTF-8 scanner; the
 * ASCII map covers the punctuation that actually appears in Telegram message
 * text (ASCII punctuation, newlines, tabs) and is a strict improvement over the
 * status quo, which was a full scan of the whole table.
 */
final class MessageSearchIndexer
{
    /**
     * Characters that terminate a token. Applied as a single strtr() pass, so
     * each becomes a space and explode(' ') splits on it. Covers ASCII
     * punctuation plus C0 whitespace; see the class docblock on the
     * non-ASCII limitation.
     *
     * @var array<string, string>
     */
    private const SEPARATORS = [
        ' ' => ' ', "\t" => ' ', "\n" => ' ', "\r" => ' ', "\0" => ' ', "\x0B" => ' ',
        '!' => ' ', '"' => ' ', '#' => ' ', '$' => ' ', '%' => ' ', '&' => ' ',
        "'" => ' ', '(' => ' ', ')' => ' ', '*' => ' ', '+' => ' ', ',' => ' ',
        '-' => ' ', '.' => ' ', '/' => ' ', ':' => ' ', ';' => ' ', '<' => ' ',
        '=' => ' ', '>' => ' ', '?' => ' ', '@' => ' ', '[' => ' ', '\\' => ' ',
        ']' => ' ', '^' => ' ', '_' => ' ', '`' => ' ', '{' => ' ', '|' => ' ',
        '}' => ' ', '~' => ' ',
    ];

    /**
     * The separator map in the two-array form strtr() accepts.
     *
     * Built on demand rather than stored as a const, because strtr() takes its
     * replacement list as parallel arrays (from => to) while the const above is
     * the readable associative form. One build per call is a few microseconds
     * against a table write that is already a transaction.
     *
     * @return array<int|string, string>
     */
    private static function separators(): array
    {
        $keys = array_keys(self::SEPARATORS);
        $values = array_fill(0, count($keys), ' ');

        return array_combine($keys, $values);
    }

    /**
     * Upper bound on tokens indexed per message.
     *
     * DERIVED, NOT GUESSED. The cap exists to bound the row budget of one
     * message and the IN-list of one query, and the honest bound is the one
     * Telegram itself imposes: a message is at most 4096 characters, and the
     * cheapest possible packing of DISTINCT tokens into a message is one
     * character per token plus one separator ("a b c d" = 2 characters per
     * token), so 4096 characters cannot carry more than 4096/2 = 2048 tokens.
     *
     * 2048 is therefore the provable ceiling of any message Telegram can
     * deliver, and that is the value here. The consequence is the point: on
     * every legitimate message the tokenizer returns the COMPLETE token set
     * and the truncation branch is unreachable. A 109-word paragraph indexes
     * all 109 words (under the previous 32-token cap it indexed 32, leaving
     * 55% of the text unsearchable forever).
     *
     * The previous value was 32, which is not a pathological-paste guard — it
     * is below the word count of an ordinary sentence-and-a-half — and it was
     * applied silently on BOTH sides of the search, which turned a bound into
     * a correctness bug (see MessageSearchQuery for the reader half).
     *
     * The row budget this bounds: 2048 rows of at most MAX_TOKEN_BYTES each,
     * against a tf_message_search that already holds an unbounded number of
     * rows per (account_id, id). No migration is needed and none is implied.
     */
    public const MAX_TOKENS = 2048;

    /**
     * Messages longer than this are indexed by their leading slice only.
     *
     * The column is string('token', 255) for MySQL index-key reasons (a TEXT
     * column cannot sit in an index; see MySqlDialPortabilityTest MySQL-1170),
     * so a token longer than 255 characters would be truncated or rejected by
     * the driver. Rather than let the driver decide, the tokenizer cuts at 255
     * bytes on a character boundary.
     */
    private const MAX_TOKEN_BYTES = 255;

    /**
     * Split a text into lowercase, de-duplicated index tokens.
     *
     * Shared by the writer (which persists them) and by the reader (which
     * matches on them), so the two can never drift into indexing a different
     * token set than the one that is searched for.
     *
     * The two sides use the LIMIT differently, and deliberately so:
     *
     *   - the WRITER passes the cap (the default) and accepts truncation,
     *     because a message that exists must still be indexed by the words it
     *     does have. It reports the truncation rather than hiding it.
     *   - the READER passes null (UNBOUNDED) and enforces the cap itself, by
     *     refusing the query. Truncating a query is the one operation that
     *     makes the result mean something other than what was asked: the
     *     multi-term AND is `HAVING COUNT(DISTINCT token) = count($tokens)`,
     *     so dropping terms drops constraints and returns messages that do not
     *     satisfy the query. The reader must therefore never call this with the
     *     cap — see MessageSearchQuery::search.
     *
     * @param  int|null  $limit  maximum distinct tokens, or null for unbounded
     * @return list<string>
     */
    public static function tokenize(string $text, ?int $limit = self::MAX_TOKENS): array
    {
        return self::tokenizeWithReport($text, $limit)['tokens'];
    }

    /**
     * tokenize(), plus the fact of whether the limit actually bit.
     *
     * The truncation is returned rather than only logged so a caller that
     * needs the fact (a backfill reporting on coverage, a test asserting the
     * bound) can read it directly instead of inferring it from a row count.
     *
     * @param  int|null  $limit  maximum distinct tokens, or null for unbounded
     * @return array{tokens: list<string>, truncated: bool}
     */
    public static function tokenizeWithReport(string $text, ?int $limit = self::MAX_TOKENS): array
    {
        $folded = mb_strtolower($text, 'UTF-8');
        $spaced = strtr($folded, self::separators());

        $tokens = [];

        foreach (explode(' ', $spaced) as $word) {
            if ($word === '') {
                continue;
            }

            $token = self::truncate($word);
            if ($token === '') {
                continue;
            }

            // De-duplicate: a word repeated 500 times in one message is one
            // index row. INSERT is not an upsert, so a duplicate would be a
            // primary-key violation rather than a wasted row.
            $tokens[$token] = true;
        }

        /** @var list<string> $out */
        $out = array_keys($tokens);

        // The limit is applied AFTER de-duplication, not during the walk, for
        // two reasons. First, it is the only correct place to apply it: cutting
        // mid-walk measures the limit in words seen rather than in distinct
        // tokens, so a message padded with repeats would lose real tokens to
        // the cap. Second, it makes the truncated/not-truncated distinction
        // exact — reaching the limit on the final word of the text loses
        // nothing, and a mid-walk break cannot tell that case from a real drop.
        //
        // Collecting first costs one pass over a token set that is itself
        // bounded by the text length, so the memory is proportional to input
        // the caller already holds.
        $truncated = $limit !== null && count($out) > $limit;
        if ($truncated) {
            $out = array_slice($out, 0, $limit);
        }

        return ['tokens' => $out, 'truncated' => $truncated];
    }

    /**
     * @param  LoggerInterface|null  $logger  receives a warning when a message's
     *                                        token set is truncated. Nullable and
     *                                        silent by default, matching the
     *                                        package-wide PSR-3 seam (NullLogger
     *                                        when unconfigured), so this class
     *                                        stays plain-PHP constructible.
     */
    public function __construct(private readonly ?LoggerInterface $logger = null) {}

    /**
     * Replace every token row for the PEER-SCOPED message
     * (accountId, peerType, peerId, id) with the tokens of $text.
     *
     * ## WHY THE PEER, AND WHY IT IS A DEFAULTED ARGUMENT RATHER THAN A CHANGE
     *
     * The delete below is a REPLACE, not an append, and a replace keyed on
     * (account_id, id) was the same silent overwrite the message table suffered,
     * one derived structure down: Telegram numbers messages PER CHAT, so
     * indexing message 5 of a second dialog DELETED the first dialog's tokens
     * and left that message unfindable — while `tf_messages` itself kept both
     * rows and nothing anywhere said so. A peer pair is therefore part of the
     * index key, and `$peerType`/`$peerId` default to 0 ONLY so an existing
     * caller cannot break: a 0/0 index row is a real row against no message,
     * which is why every in-repo caller passes the pair explicitly
     * (`UpdateIngestor::writeMessage()`, `MirrorFactWriter::indexSearchRow()`).
     * Search itself is unaffected — `MessageSearchQuery` matches on tokens and
     * joins back to `tf_messages`, and never constrains the peer, because a
     * search is account-wide.
     *
     * A replace (delete-then-insert) rather than an append, because the
     * message body is editable: a message edited from "cats" to "dogs" must lose
     * the "cats" rows or it stays findable by its former text forever.
     *
     * If the token set had to be cut to MAX_TOKENS, a warning is logged
     * naming the account, the message id and the kept count. The cut is
     * unreachable for any message Telegram can deliver (see MAX_TOKENS), so
     * this fires only for host-supplied text beyond Telegram's own limit —
     * but it fires LOUDLY, because the alternative is a word that is silently
     * unsearchable with no way for the caller to tell that from a word that is
     * simply not in the message.
     *
     * NO DEFAULT FOR THE PEER PAIR, deliberately. `tf_message_search.peer_type`
     * and `peer_id` are NOT NULL with no default
     * (2026_09_29_000300_create_tf_message_search_table.php), so a `= 0`
     * default here did not make a caller valid — it made an omitted pair
     * compile into an unreachable `(account_id, token, 0, 0, id)` row, the
     * same silent 0/0 shape this branch removed from every other key. There
     * is no valid zero for a peer, so requiring the pair is a tightening, and
     * every in-repo caller already passes it.
     *
     * @return int the number of token rows now present
     */
    public function index(int $accountId, int $id, string $text, int $peerType, int $peerId): int
    {
        $report = self::tokenizeWithReport($text, self::MAX_TOKENS);
        $tokens = $report['tokens'];

        if ($report['truncated']) {
            $this->logger?->warning('message search index truncated: words past the token cap are unsearchable', [
                'account_id' => $accountId,
                'peer_type' => $peerType,
                'peer_id' => $peerId,
                'id' => $id,
                'tokens_indexed' => count($tokens),
                'max_tokens' => self::MAX_TOKENS,
            ]);
        }

        DB::table('tf_message_search')
            ->where('account_id', $accountId)
            ->where('peer_type', $peerType)
            ->where('peer_id', $peerId)
            ->where('id', $id)
            ->delete();

        if ($tokens === []) {
            return 0;
        }

        $rows = [];
        foreach ($tokens as $token) {
            $rows[] = [
                'account_id' => $accountId,
                'peer_type' => $peerType,
                'peer_id' => $peerId,
                'id' => $id,
                'token' => $token,
            ];
        }

        DB::table('tf_message_search')->insert($rows);

        return count($rows);
    }

    /**
     * Drop the token rows for ONE deleted message — the peer-scoped one, not
     * every dialog's message with that id.
     *
     * SafeDelete removes the tf_messages row; without this the orphan token rows
     * would keep a deleted message's id matching a search, and the query would
     * join to a row that no longer exists.
     */
    public function forget(int $accountId, int $id, int $peerType, int $peerId): void
    {
        DB::table('tf_message_search')
            ->where('account_id', $accountId)
            ->where('peer_type', $peerType)
            ->where('peer_id', $peerId)
            ->where('id', $id)
            ->delete();
    }

    /**
     * `forget()` for a SET of message ids in one peer — the shape a delete
     * update actually arrives in (`updateDeleteMessages` carries
     * `messages:Vector<int>`, not one id).
     *
     * One query rather than a loop, because a delete is the one moment where
     * per-row round-trips on the largest derived table in the schema are worst:
     * a bot deleting 200 messages would issue 200 statements against a table
     * with a full PK scan each.
     *
     * @param  list<int>  $messageIds
     */
    public function forgetMany(int $accountId, array $messageIds, ?int $peerType = null, ?int $peerId = null): void
    {
        if ($messageIds === []) {
            return;
        }

        DB::table('tf_message_search')
            ->where('account_id', $accountId)
            // A NULL peer means "every dialog" — the over-broad direction, which
            // is the safe one for a delete: it can remove rows that should have
            // survived, never rows that should have gone.
            ->when($peerId !== null, fn ($q) => $q->where('peer_id', $peerId))
            ->when($peerType !== null, fn ($q) => $q->where('peer_type', $peerType))
            ->whereIn('id', $messageIds)
            ->delete();
    }

    /**
     * Backfill token rows for messages that predate the index, in bounded
     * batches ordered by id.
     *
     * This exists because the migration creates an EMPTY table on a host that
     * may already hold the operator's whole history. Without it the search would
     * correctly return nothing at all — a silent, total data loss that looks
     * exactly like "the search feature is broken".
     *
     * Ordering by id and remembering $afterId between batches is what makes this
     * resumable: a host with a million messages runs it repeatedly, and each run
     * continues where the last stopped rather than rescanning from message 1.
     *
     * The watermark is the WHOLE peer-scoped key, not `id` alone, and the two
     * extra components are OPTIONAL trailing arguments so the previous
     * `reindexAccount($accountId, $afterId)` call site keeps working — it starts
     * at the beginning of the key space either way, which is what a first pass
     * wants. `lastId` is still reported because callers display it; it is the
     * `id` component of the new watermark.
     *
     * @param  int  $afterId  resume watermark; pass 0 for a first pass
     * @return array{messages: int, tokens: int, lastId: int} the new watermark to pass to the next call
     */
    public function reindexAccount(
        int $accountId,
        int $afterId = 0,
        int $batchSize = 500,
        int $afterPeerType = 0,
        int $afterPeerId = 0,
    ): array {
        $messages = 0;
        $tokens = 0;
        $lastId = $afterId;

        do {
            // Keyset pagination over the FULL peer-scoped key, and the
            // watermark is the whole key rather than `id`.
            //
            // Keyset pagination, not LIMIT/OFFSET: OFFSET re-walks every skipped
            // row, so batch 200 of 200 would cost 200x batch 1.
            //
            // Ordering by `id` alone was wrong the moment the key was widened:
            // `id` repeats across dialogs, so a `where('id','>',$afterId)`
            // watermark would skip every dialog whose messages all sit at or
            // below the mark — silently, and on exactly the multi-dialog
            // accounts this exists to serve. The four nested closures below
            // spell the lexicographic (peer_type, peer_id, id) "strictly after"
            // relation; ordering by the same three columns keeps the walk on the
            // primary-key index.
            $batch = DB::table('tf_messages')
                ->select(['peer_type', 'peer_id', 'id', 'message'])
                ->where('account_id', $accountId)
                ->where(function ($q) use ($afterPeerType, $afterPeerId, $afterId): void {
                    $q->where('peer_type', '>', $afterPeerType)
                        ->orWhere(function ($q2) use ($afterPeerType, $afterPeerId, $afterId): void {
                            $q2->where('peer_type', $afterPeerType)
                                ->where(function ($q3) use ($afterPeerId, $afterId): void {
                                    $q3->where('peer_id', '>', $afterPeerId)
                                        // No $afterPeerType here: this branch is
                                        // already inside `peer_type ==
                                        // $afterPeerType`, so re-testing it
                                        // would be dead weight.
                                        ->orWhere(function ($q4) use ($afterPeerId, $afterId): void {
                                            $q4->where('peer_id', $afterPeerId)->where('id', '>', $afterId);
                                        });
                                });
                        });
                })
                ->orderBy('peer_type')->orderBy('peer_id')->orderBy('id')
                ->limit($batchSize)
                ->get();

            foreach ($batch as $row) {
                $afterPeerType = (int) $row->peer_type;
                $afterPeerId = (int) $row->peer_id;
                $lastId = (int) $row->id;
                $afterId = $lastId;
                $tokens += $this->index($accountId, $lastId, (string) $row->message, $afterPeerType, $afterPeerId);
                $messages++;
            }
        } while ($batch->count() === $batchSize);

        return ['messages' => $messages, 'tokens' => $tokens, 'lastId' => $lastId];
    }

    /**
     * Cut a word to MAX_TOKEN_BYTES without splitting a UTF-8 character.
     *
     * mb_strcut() is byte-accurate and character-boundary safe, which a plain
     * substr() is not — substr() on a multi-byte character yields invalid UTF-8,
     * and the driver then stores a differently-encoded token than the one the
     * query is built from.
     */
    private static function truncate(string $word): string
    {
        if (strlen($word) <= self::MAX_TOKEN_BYTES) {
            return $word;
        }

        return mb_strcut($word, 0, self::MAX_TOKEN_BYTES, 'UTF-8');
    }
}
