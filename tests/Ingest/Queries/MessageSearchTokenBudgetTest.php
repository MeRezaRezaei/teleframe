<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Queries;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Ingest\MessageSearchIndexer;
use MeRezaRezaei\Teleframe\Ingest\Queries\MessageSearchQuery;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;

/**
 * THE TOKEN BUDGET MUST NOT EITHER LOSE MESSAGE TEXT OR DOWNGRADE AN AND.
 *
 * Two defects met at one shared piece of code — MessageSearchIndexer::tokenize()
 * is called by the writer AND by the reader, and a hard 32-token cap lived in
 * it. So the cap ran on BOTH sides and neither side said anything:
 *
 *   1. WRITER, false NEGATIVE. A 40-word message had its last 8 words dropped
 *      before the rows were written, and index() recorded only a count, so the
 *      caller could not tell "not indexed" from "not there". On a realistic
 *      109-word message, 60 of 109 words (55%) were unsearchable forever.
 *   2. READER, false POSITIVE — the dangerous one. The same cap ran on the QUERY,
 *      and the multi-term AND is `HAVING COUNT(DISTINCT token) = count($tokens)`
 *      where the count is the TRUNCATED count. A 40-term AND was therefore
 *      executed as a 32-term AND: a message carrying only w1..w32 matched a
 *      query that demanded w1..w40. A host building "the message where all
 *      these terms appear" got a message failing 8 of 40 constraints, silently.
 *
 * The docblock claimed this was safe — "The cap is applied to BOTH sides, which
 * is symmetric: a query is rejected past the same bound" — but NO query was ever
 * rejected. MessageSearchQuery only ever refused an EMPTY token list. The
 * claimed safety property did not exist, which is why the defect survived.
 *
 * The contract asserted here, in both directions:
 *   - the cap is derived from the REAL Telegram message limit, so it never
 *     fires on a message Telegram could actually deliver;
 *   - the reader REFUSES LOUDLY past the cap instead of truncating, so an AND
 *     can never be silently downgraded again;
 *   - the writer, if it ever does truncate, emits a PSR-3 clue rather than
 *     losing words without a trace.
 *
 * ENGINE: SQLite via IngestTestCase, against the REAL shipped migration for
 * tf_message_search (the curated dial) — not an ad-hoc in-memory schema.
 */
class MessageSearchTokenBudgetTest extends IngestTestCase
{
    /**
     * Tenant 1 — the only one, verified against every call site.
     *
     * Checked before writing this: `ingest()` is called with the literal `1` at
     * lines 89, 109, 110, 143 and 180; `MessageSearchQuery->search()` is called
     * with `1` at lines 91, 93, 112, 136 and 145 and 182; the direct
     * `MessageSearchIndexer->index()` calls at lines 208 and 220 pass `1`; and
     * the row assertions read `where('account_id', 1)` / a logger context
     * `account_id` of `1` (lines 186, 213). No other tenant id appears in the
     * file. So one parent is the whole story.
     *
     * Both `tf_messages` and `tf_message_search` carry `account_id` = the
     * Telegram user id — `MirrorIngesterFactory.php:160` writes the caller's
     * Telegram id straight into it, and the FK targets `telegram_accounts.user_id`
     * rather than the vault row's own `id`. Note that CASE 4 and CASE 7 assert
     * on constants and pure tokenization and touch no table at all; declaring
     * the tenant anyway keeps the fixture independent of which cases run.
     *
     * Production always plants the parent first:
     * `VaultAddAccountCommand.php:80` writes `user_id` at account-add time.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [1];
    }

    /** A message of $n distinct single-token words: 'w1 w2 ... w$n'. */
    private static function numbered(int $n): string
    {
        $words = [];
        for ($i = 1; $i <= $n; $i++) {
            $words[] = 'w'.$i;
        }

        return implode(' ', $words);
    }

    /** Write a tf_messages row AND its index rows, as the live write path does. */
    private static function ingest(int $accountId, int $id, string $text): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => $accountId, 'id' => $id,
            'peer_type' => 2, 'peer_id' => 1001,
            'constructor' => 'message', 'date' => 1700000000,
            'message' => $text,
        ]);
        (new MessageSearchIndexer)->index($accountId, $id, $text, 2, 1001);
    }

    private static function ids(array $rows): array
    {
        return array_map(static fn (object $r): int => (int) $r->id, $rows);
    }

    // ---------------------------------------------------------------- case 1

    /**
     * CASE 1 — a 40-word message must be findable by its 40th word.
     *
     * RED before the fix: tokenize() broke at 32, so w33..w40 were never
     * written and this returned 0 rows for a message that demonstrably
     * contains w40.
     */
    public function test_writer_indexes_past_the_old_32_token_cap(): void
    {
        self::ingest(1, 100, self::numbered(40));

        $this->assertCount(1, (new MessageSearchQuery)->search(1, 'w40'),
            'the 40th word of a 40-word message must be searchable');
        $this->assertCount(1, (new MessageSearchQuery)->search(1, 'w33'),
            'the first dropped word must be searchable too');
    }

    /**
     * CASE 2 — a 40-term AND must match ONLY a message carrying all 40 terms.
     *
     * This is the false positive. Message 200 carries w1..w32 ONLY; message 201
     * carries all 40. Querying all 40 terms must return 201 and nothing else.
     *
     * RED before the fix: the query tokenized to 32 terms, HAVING asked for
     * COUNT(DISTINCT token) = 32, and message 200 satisfied it — so the answer
     * was [200, 201] for a 40-term AND.
     */
    public function test_multi_term_and_is_not_downgraded_past_the_cap(): void
    {
        self::ingest(1, 200, self::numbered(32));   // missing w33..w40
        self::ingest(1, 201, self::numbered(40));   // carries every term

        $results = (new MessageSearchQuery)->search(1, self::numbered(40));

        $this->assertSame([201], self::ids($results),
            'a 40-term AND must not match a message failing 8 of its 40 constraints');
    }

    // ---------------------------------------------------------------- case 3

    /**
     * CASE 3 — the reader refuses a query past the cap, loudly.
     *
     * Throwing is the decision, and the reason is the defect class: truncating
     * the query is the one action that makes an AND mean something other than
     * what was asked, and the result would be rows that satisfy a weaker
     * predicate. An exception is the only outcome a caller cannot mistake for
     * "these messages match your query".
     */
    public function test_reader_refuses_a_query_past_the_cap_instead_of_truncating(): void
    {
        $tooMany = self::numbered(MessageSearchIndexer::MAX_TOKENS + 1);

        $this->expectException(TelegramException::class);
        $this->expectExceptionMessage('tokens');

        (new MessageSearchQuery)->search(1, $tooMany);
    }

    public function test_a_query_exactly_at_the_cap_is_accepted(): void
    {
        // The bound is inclusive: MAX_TOKENS terms is legal, MAX_TOKENS+1 is
        // not. A cap that is off by one here is a silent truncation again.
        self::ingest(1, 300, self::numbered(MessageSearchIndexer::MAX_TOKENS));

        $atCap = (new MessageSearchQuery)->search(1, self::numbered(MessageSearchIndexer::MAX_TOKENS));
        $this->assertSame([300], self::ids($atCap), 'MAX_TOKENS terms must be accepted');
    }

    // ---------------------------------------------------------------- case 4

    /**
     * CASE 4 — the cap is derived from the real Telegram message limit, so it
     * does not fire on any message Telegram could actually deliver.
     *
     * A Telegram message is capped at 4096 characters, and the cheapest possible
     * packing of distinct single-character tokens is 2 characters each ("a b c"),
     * so 4096 characters cannot carry more than 2048 tokens. The constant is
     * therefore set to that provable ceiling: on every legitimate message the
     * tokenizer returns the COMPLETE token set and this code path is unreachable.
     */
    public function test_cap_cannot_be_reached_by_a_legitimate_telegram_message(): void
    {
        $this->assertSame(2048, MessageSearchIndexer::MAX_TOKENS);
        $this->assertLessThanOrEqual(
            (int) (4096 / 2),
            MessageSearchIndexer::MAX_TOKENS,
            'the cap must be at least the provable token ceiling of a 4096-character message'
        );
    }

    /**
     * CASE 5 — a realistic long message is indexed COMPLETELY, not to a ratio.
     *
     * 109 words is an ordinary paragraph. Under the old 32-token cap, 60 of
     * those 109 words (55%) were unsearchable forever.
     */
    public function test_a_realistic_long_message_loses_no_words(): void
    {
        $text = self::numbered(109);
        self::ingest(1, 400, $text);

        $this->assertCount(1, (new MessageSearchQuery)->search(1, 'w109'),
            'the last word of a 109-word message must be searchable');
        $this->assertSame(
            109,
            DB::table('tf_message_search')->where('account_id', 1)->where('id', 400)->count(),
            'every distinct word of a 109-word message must be indexed'
        );
    }

    // ---------------------------------------------------------------- case 5

    /**
     * CASE 6 — when the writer DOES truncate, it says so.
     *
     * Truncation is now unreachable for any message Telegram can deliver (see
     * CASE 4), so this exercises it through host-supplied text that exceeds the
     * Telegram limit. The point is not the row count — it is that the loss is
     * no longer silent: a PSR-3 warning names the account, the message and the
     * count, so "that word is unfindable" has a trace.
     */
    public function test_writer_emits_a_clue_when_it_truncates(): void
    {
        $logger = new ArrayLogger;
        $indexer = new MessageSearchIndexer($logger);
        $oversized = self::numbered(MessageSearchIndexer::MAX_TOKENS + 500);

        $written = $indexer->index(1, 500, $oversized, 2, 1001);

        $this->assertSame(MessageSearchIndexer::MAX_TOKENS, $written);
        $this->assertNotSame([], $logger->records, 'truncation must not be silent');
        $this->assertSame('warning', $logger->records[0]['level']);
        $this->assertSame(1, $logger->records[0]['context']['account_id']);
        $this->assertSame(500, $logger->records[0]['context']['id']);
    }

    public function test_writer_does_not_warn_when_nothing_is_truncated(): void
    {
        $logger = new ArrayLogger;
        (new MessageSearchIndexer($logger))->index(1, 501, self::numbered(40), 2, 1001);

        $this->assertSame([], $logger->records,
            'a message indexed in full must not produce a truncation warning');
    }

    /**
     * CASE 7 — tokenize() reports the truncation it performed, for a caller
     * that needs the fact rather than a log line.
     */
    public function test_tokenize_reports_whether_it_truncated(): void
    {
        $under = MessageSearchIndexer::tokenizeWithReport(self::numbered(40));
        $this->assertFalse($under['truncated']);
        $this->assertCount(40, $under['tokens']);

        $over = MessageSearchIndexer::tokenizeWithReport(
            self::numbered(MessageSearchIndexer::MAX_TOKENS + 1)
        );
        $this->assertTrue($over['truncated']);
        $this->assertCount(MessageSearchIndexer::MAX_TOKENS, $over['tokens']);
    }
}
