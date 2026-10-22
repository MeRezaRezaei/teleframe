<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Queries;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\MessageSearchIndexer;
use MeRezaRezaei\Teleframe\Ingest\Queries\MessageSearchQuery;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * Unicode NFC normalisation on the message search path.
 *
 * THE DEFECT THIS FILE EXISTS FOR. The tokeniser produced neither NFC nor
 * NFD, so a precomposed "é" (U+00E9) and a canonically-decomposed "é"
 * (U+0065 U+0301) became two different index tokens and neither matched the
 * other. UAX #15 states the property this violated: "If two strings x and y are
 * canonical equivalents, then toNFC(x) = toNFC(y)". The failure mode is a FALSE
 * NEGATIVE — a message that used to be findable silently stops being findable —
 * and it was invisible because MySQL's utf8mb4_unicode_ci WOULD have folded the
 * two forms in an equality test while SQLite's default BINARY collation does
 * not, so the same code passed on one engine and failed on another.
 *
 * WHY THE ASSERTIONS ARE ABOUT BYTES AND NOT ABOUT MATCHING. Asserting only
 * "the search finds it" would pass even if the writer and reader disagreed about
 * which spelling to store, as long as they disagreed consistently. The
 * truncation and byte-equality tests below pin the stored token itself, because
 * the invariant this file protects is that the writer's tokeniser and the
 * reader's tokeniser emit the SAME bytes — the property stated at
 * MessageSearchIndexer::tokenize().
 *
 * @see MessageSearchIndexer
 */
class MessageSearchNfcNormalisationTest extends IngestTestCase
{
    /**
     * Precomposed forms, written with explicit escapes so the intent survives a
     * copy-paste through anything that would helpfully "fix" the encoding.
     */
    private const PRECOMPOSED_CAFE = "caf\u{00E9}";   // café

    private const DECOMPOSED_CAFE = "cafe\u{0301}";   // cafe + COMBINING ACUTE ACCENT

    private const PRECOMPOSED_NAIVE = "na\u{00EF}ve";  // naïve

    private const DECOMPOSED_NAIVE = "nai\u{0308}ve";  // nai + COMBINING DIAERESIS

    /**
     * One tenant is enough; the cross-tenant assertions live in
     * MessageSearchQueryTest and are not what this file is about.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [1];
    }

    /**
     * @param  array{0: int, 1: int, 2: int, 3: int, 4: string}  $row
     *                                                                 [accountId, id, peerType, peerId, text]
     */
    private function plantMessage(int $accountId, int $id, int $peerType, int $peerId, string $text): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => $accountId, 'id' => $id,
            'peer_type' => $peerType, 'peer_id' => $peerId,
            'constructor' => 'message', 'date' => now()->timestamp,
            'message' => $text,
        ]);

        (new MessageSearchIndexer)->index($accountId, $id, $text, $peerType, $peerId);
    }

    // ---------------------------------------------------------------
    // 1. The defect itself: both directions of canonical equivalence.
    // ---------------------------------------------------------------

    public function test_decomposed_query_finds_precomposed_stored_text(): void
    {
        $this->plantMessage(1, 1, 2, 1001, 'the '.self::PRECOMPOSED_CAFE.' is open');

        $results = (new MessageSearchQuery)->search(1, self::DECOMPOSED_CAFE);

        $this->assertCount(1, $results, 'a decomposed query must find precomposed stored text');
    }

    public function test_precomposed_query_finds_decomposed_stored_text(): void
    {
        $this->plantMessage(1, 1, 2, 1001, 'the '.self::DECOMPOSED_CAFE.' is open');

        $results = (new MessageSearchQuery)->search(1, self::PRECOMPOSED_CAFE);

        $this->assertCount(1, $results, 'a precomposed query must find decomposed stored text');
    }

    public function test_every_canonically_equivalent_pair_indexes_one_token_set(): void
    {
        $precomposed = MessageSearchIndexer::tokenize(
            "caf\u{00E9} na\u{00EF}ve \u{00FC}ber",
            null
        );
        $decomposed = MessageSearchIndexer::tokenize(
            "cafe\u{0301} nai\u{0308}ve u\u{0308}ber",
            null
        );

        $this->assertSame($precomposed, $decomposed, 'canonically equivalent text must tokenise identically');
        $this->assertSame(
            ["caf\u{00E9}", "na\u{00EF}ve", "\u{00FC}ber"],
            $precomposed,
            'tokens must be stored in the PRECOMPOSED (NFC) spelling'
        );
    }

    // ---------------------------------------------------------------
    // 2. Truncation order — the part that is easy to get wrong.
    // ---------------------------------------------------------------

    /**
     * A token whose NFC form is far longer than MAX_TOKEN_BYTES (255).
     *
     * 200 precomposed "é" = 400 bytes; the same text written decomposed is 600
     * bytes. Both must survive the cut as the SAME 254-byte token.
     */
    public function test_token_exceeding_the_255_byte_cut_matches_across_forms(): void
    {
        $precomposed = str_repeat("\u{00E9}", 200);
        $decomposed = str_repeat("e\u{0301}", 200);

        // The decomposed TEXT is stored; the PRECOMPOSED query must find it.
        $this->plantMessage(1, 1, 2, 1001, $decomposed);

        $this->assertCount(
            1,
            (new MessageSearchQuery)->search(1, $precomposed),
            'past the 255-byte cut, canonically equivalent spellings must still match'
        );
    }

    /**
     * The truncation-order invariant, pinned at the byte level.
     *
     * Normalising AFTER the cut is the natural-looking ordering and it is
     * wrong: measured, a 255-byte cut of 200 precomposed "é" yields 254 bytes /
     * 127 characters, while the same cut of the decomposed spelling yields 255
     * bytes / 170 characters and ends on a DANGLING COMBINING MARK, because
     * mb_strcut() is character-boundary safe but not combining-sequence aware.
     * Normalising first collapses both spellings to one form, after which the
     * cut is a function of that one form.
     */
    public function test_writer_and_reader_agree_byte_for_byte_past_the_cut(): void
    {
        $fromPrecomposed = MessageSearchIndexer::tokenize(str_repeat("\u{00E9}", 200), null)[0];
        $fromDecomposed = MessageSearchIndexer::tokenize(str_repeat("e\u{0301}", 200), null)[0];

        $this->assertSame(
            $fromPrecomposed,
            $fromDecomposed,
            'the writer and the reader must cut the same bytes from canonically equivalent text'
        );

        $this->assertLessThanOrEqual(
            255,
            strlen($fromPrecomposed),
            'no token may exceed MAX_TOKEN_BYTES after the cut'
        );

        // And the cut must land on a CHARACTER boundary, not mid-sequence: the
        // final bytes are a complete precomposed "é" (c3 a9).
        $this->assertSame(
            'c3a9',
            bin2hex(substr($fromPrecomposed, -2)),
            'the cut must leave a complete character, never a trailing combining mark'
        );
    }

    // ---------------------------------------------------------------
    // 3. No regressions on the path that was already correct.
    // ---------------------------------------------------------------

    public function test_ascii_tokens_are_unchanged_by_normalisation(): void
    {
        $text = 'the quick brown fox 0123 !@#$%^&*()';

        $this->assertSame(
            ['the', 'quick', 'brown', 'fox', '0123'],
            MessageSearchIndexer::tokenize($text, null),
            'pure-ASCII tokenisation must be byte-identical to the pre-change behaviour'
        );
    }

    public function test_search_still_finds_plain_whole_words(): void
    {
        $this->plantMessage(1, 1, 2, 1001, 'hello world');

        $this->assertCount(1, (new MessageSearchQuery)->search(1, 'hello'));
        $this->assertCount(0, (new MessageSearchQuery)->search(1, 'hellow'));
    }

    public function test_normalisation_is_idempotent(): void
    {
        $token = MessageSearchIndexer::tokenize(self::DECOMPOSED_CAFE, null);

        $this->assertSame(
            $token,
            MessageSearchIndexer::tokenize($token[0], null),
            're-tokenising an already-normalised token must be a fixed point'
        );
    }

    // ---------------------------------------------------------------
    // 4. Case mapping that interacts with normalisation.
    // ---------------------------------------------------------------

    /**
     * mb_strtolower() REINTRODUCES a decomposed sequence, which is why
     * normalisation runs AFTER lowercasing rather than before it.
     *
     * mb_strtolower("\u{0130}") (U+0130, LATIN CAPITAL LETTER I WITH DOT ABOVE)
     * returns "i" + U+0307 — two code points, not one. A normalise-then-
     * lowercase pipeline would therefore emit a NON-canonical token after having
     * normalised; lowercase-then-NFC cannot, because the operation that
     * establishes canonicity is always the last of the two.
     */
    public function test_lowercasing_that_introduces_a_decomposed_sequence_is_normalised_afterwards(): void
    {
        $fromCapital = MessageSearchIndexer::tokenize("\u{0130}", null);
        $fromLower = MessageSearchIndexer::tokenize("i\u{0307}", null);

        $this->assertSame(
            $fromLower,
            $fromCapital,
            'U+0130 and its own lowercase must agree, or the search index is split on one character'
        );

        // NFC cannot recompose this pair (no precomposed code point exists for
        // "i" + COMBINING DOT ABOVE), so the token legitimately stays two code
        // points. What matters is that BOTH sides agree on that.
        $this->assertSame(2, mb_strlen($fromCapital[0], 'UTF-8'));
    }

    public function test_german_sharp_s_folds_consistently(): void
    {
        // U+1E9E (capital sharp s) lowercases to U+00DF, which is already NFC.
        $this->assertSame(
            MessageSearchIndexer::tokenize("\u{1E9E}", null),
            MessageSearchIndexer::tokenize("\u{00DF}", null)
        );

        $this->plantMessage(1, 1, 2, 1001, "die Stra\u{00DF}e");
        $this->assertCount(1, (new MessageSearchQuery)->search(1, "die Stra\u{1E9E}e"));
    }

    // ---------------------------------------------------------------
    // 5. The existing-data remedy.
    // ---------------------------------------------------------------

    /**
     * Rows written BEFORE normalisation must be repairable, and the repair is a
     * reindex — not read-time normalisation.
     *
     * The shape asserted here is the whole argument for choosing a reindex:
     * normalising the QUERY alone produces the precomposed spelling, which does
     * not equal the decomposed bytes already in the column, so the query would be
     * searching for a token that is guaranteed not to be there. Only the side
     * that STORES the bytes can be fixed, which is what this test drives.
     */
    public function test_reindex_rewrites_preexisting_unnormalised_tokens(): void
    {
        $decomposed = 'the '.self::DECOMPOSED_CAFE;

        DB::table('tf_messages')->insert([
            'account_id' => 1, 'id' => 7,
            'peer_type' => 2, 'peer_id' => 1001,
            'constructor' => 'message', 'date' => now()->timestamp,
            'message' => $decomposed,
        ]);

        // Simulate the PRE-FIX state exactly: the token as the old tokeniser
        // would have written it, i.e. the raw decomposed lowercase word.
        DB::table('tf_message_search')->insert([
            'account_id' => 1, 'peer_type' => 2, 'peer_id' => 1001,
            'id' => 7,
            'token' => mb_strtolower(self::DECOMPOSED_CAFE, 'UTF-8'),
        ]);

        // Pre-fix rows do NOT match a precomposed query — this is the silent
        // false negative the reindex exists to remove.
        $this->assertCount(
            0,
            (new MessageSearchQuery)->search(1, self::PRECOMPOSED_CAFE),
            'precondition: an un-reindexed row must not match the normalised query'
        );

        $report = (new MessageSearchIndexer)->reindexAccount(1, 0, 500);

        $this->assertSame(1, $report['messages']);
        $this->assertGreaterThan(0, $report['tokens']);

        $this->assertCount(
            1,
            (new MessageSearchQuery)->search(1, self::PRECOMPOSED_CAFE),
            'after the reindex the message must be findable again'
        );

        $stored = DB::table('tf_message_search')
            ->where('account_id', 1)
            ->where('id', 7)
            ->where('token', 'the')
            ->exists();

        $this->assertTrue($stored, 'the reindex must have rewritten the token rows in place');
    }

    public function test_reindex_is_idempotent_across_repeated_runs(): void
    {
        $this->plantMessage(1, 1, 2, 1001, 'the '.self::DECOMPOSED_CAFE);

        $first = (new MessageSearchIndexer)->reindexAccount(1, 0, 500);
        $afterFirst = DB::table('tf_message_search')->where('account_id', 1)->count();

        $second = (new MessageSearchIndexer)->reindexAccount(1, 0, 500);
        $afterSecond = DB::table('tf_message_search')->where('account_id', 1)->count();

        $this->assertSame($first['tokens'], $second['tokens'], 'a reindex must be idempotent');
        $this->assertSame($afterFirst, $afterSecond, 'row count must not drift between runs');
    }
}
