<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Queries;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\MessageSearchIndexer;
use MeRezaRezaei\Teleframe\Ingest\Queries\MessageSearchQuery;
use MeRezaRezaei\Teleframe\Ingest\SafeDelete;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * Behaviour of the token-indexed search.
 *
 * The plan assertions live in MessageSearchPlanTest — this file is the
 * correctness half, and it deliberately no longer claims that substring
 * matching is supported. Whole-token matching replaced it because a leading
 * '%' is unindexable by construction, and test_search_matches_whole_words_and
 * phrases is the assertion that records that trade.
 */
class MessageSearchQueryTest extends IngestTestCase
{
    /**
     * Tenants 1 and 2.
     *
     * `setUp()` indexes three messages under account 1, and
     * `test_search_scopes_to_account` inserts AND indexes a fourth under
     * account 2 with text that matches the account-1 query — that other-tenant
     * row is the whole mechanism of the scoping assertion. Rows go in through
     * `MessageSearchIndexer`, so both `tf_messages` and `tf_message_search` rows
     * carry `account_id` = the Telegram user id;
     * `MirrorIngesterFactory.php:160` writes the caller's Telegram id straight
     * into that column and the FK targets `telegram_accounts.user_id`. Only
     * account 1 is ever SEARCHED, but account 2 must still exist or the
     * exclusion has nothing to exclude.
     *
     * Production always plants the parent first:
     * `VaultAddAccountCommand.php:80` writes `user_id` at account-add time.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [1, 2];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Curated tf_messages: composite PK (account_id, id), peer pair,
        // constructor TEXT, date INTEGER, message TEXT. Legacy columns
        // (message_id/constructor_id/tl_data/message_text/created_at) do not
        // exist on this surface.
        //
        // Rows go in through MessageSearchIndexer because that is what the live
        // write path does — inserting tf_messages directly would leave the
        // search index empty and every assertion here would be vacuously green
        // against an index nothing populates.
        $indexer = new MessageSearchIndexer;

        $rows = [
            [1, 1, 2, 1001, 'hello world'],
            [1, 2, 2, 1001, 'the quick brown fox'],
            [1, 3, 2, 1001, 'hello there friend'],
        ];

        foreach ($rows as [$accountId, $id, $peerType, $peerId, $text]) {
            DB::table('tf_messages')->insert([
                'account_id' => $accountId, 'id' => $id,
                'peer_type' => $peerType, 'peer_id' => $peerId,
                'constructor' => 'message', 'date' => now()->timestamp,
                'message' => $text,
            ]);
            $indexer->index($accountId, $id, $text, $peerType, $peerId);
        }
    }

    public function test_search_finds_matching_messages(): void
    {
        $results = (new MessageSearchQuery)->search(1, 'hello');
        $this->assertCount(2, $results);
    }

    public function test_search_scopes_to_account(): void
    {
        $text = 'hello from other account';
        DB::table('tf_messages')->insert([
            'account_id' => 2, 'id' => 10, 'peer_type' => 2, 'peer_id' => 1001,
            'constructor' => 'message', 'date' => now()->timestamp,
            'message' => $text,
        ]);
        (new MessageSearchIndexer)->index(2, 10, $text, 2, 1001);

        $results = (new MessageSearchQuery)->search(1, 'hello');
        $this->assertCount(2, $results); // only account 1
    }

    public function test_search_with_peer_filter(): void
    {
        $results = (new MessageSearchQuery)->search(1, 'hello', peerId: 1001);
        $this->assertCount(2, $results);
    }

    public function test_search_with_peer_pair_filters_type_and_id(): void
    {
        // Same peer_id but different peer_type is a DIFFERENT peer.
        $text = 'hello from a user peer';
        DB::table('tf_messages')->insert([
            'account_id' => 1, 'id' => 4, 'peer_type' => 1, 'peer_id' => 1001,
            'constructor' => 'message', 'date' => now()->timestamp,
            'message' => $text,
        ]);
        (new MessageSearchIndexer)->index(1, 4, $text, 1, 1001);

        // Only the peer_type=2 rows match.
        $results = (new MessageSearchQuery)->search(1, 'hello', peerId: 1001, peerType: 2);
        $this->assertCount(2, $results);

        // Only the peer_type=1 row matches.
        $results = (new MessageSearchQuery)->search(1, 'hello', peerId: 1001, peerType: 1);
        $this->assertCount(1, $results);
        $this->assertSame('hello from a user peer', $results[0]->message);
    }

    public function test_search_matches_whole_words_and_phrases(): void
    {
        // "quick brown" starts at the FOURTH word, so this is the case a
        // column-anchored prefix index cannot serve and the reason the fix is a
        // token table rather than the cheaper LIKE 'foo%' option.
        $results = (new MessageSearchQuery)->search(1, 'quick brown');
        $this->assertCount(1, $results);
        $this->assertSame('the quick brown fox', $results[0]->message);
    }

    public function test_multi_token_search_is_an_and_not_an_or(): void
    {
        // "hello" matches 2 rows and "fox" matches 1; an OR would return 3.
        $results = (new MessageSearchQuery)->search(1, 'hello fox');
        $this->assertCount(0, $results);

        $results = (new MessageSearchQuery)->search(1, 'hello world');
        $this->assertCount(1, $results);
    }

    public function test_search_is_case_and_punctuation_insensitive(): void
    {
        // Both sides run through the same tokenizer, which is the only reason
        // this can work: "Hello, WORLD!" and "hello world" must produce the same
        // token set or the index would hold terms nobody searches for.
        $results = (new MessageSearchQuery)->search(1, 'HELLO, World!');
        $this->assertCount(1, $results);
        $this->assertSame('hello world', $results[0]->message);
    }

    public function test_wildcards_are_not_search_operators(): void
    {
        // NO prefix-search support, and that is the honest contract rather than
        // a bug: the tokenizer treats '%' as a separator, so "qui%" is the token
        // "qui" and matches nothing. An earlier draft of this test asserted that
        // "qui%" DID match "quick" and the class docblock advertised trailing-
        // wildcard support; the implementation never did that, so the claim was
        // removed rather than the test being bent to match the docblock.
        //
        // The trade is deliberate: prefix search WOULD be indexable (a trailing
        // '%' is a bounded range a B-tree can follow, unlike a leading one), so
        // it could be added on top of this index without any migration. It is
        // not here because no caller asked for it, and a search that silently
        // half-supports wildcards is worse than one with a stated contract.
        $this->assertCount(0, (new MessageSearchQuery)->search(1, 'qui%'));
        $this->assertCount(1, (new MessageSearchQuery)->search(1, 'quick'));
    }

    public function test_empty_and_separator_only_queries_return_nothing(): void
    {
        // Returning every message would be the exact unindexed cost profile
        // being fixed, so an empty term set short-circuits to [].
        $this->assertSame([], (new MessageSearchQuery)->search(1, ''));
        $this->assertSame([], (new MessageSearchQuery)->search(1, '   '));
        $this->assertSame([], (new MessageSearchQuery)->search(1, '!!!'));
    }

    public function test_search_respects_the_limit(): void
    {
        // Two messages match 'hello'; a limit of 1 must return exactly one.
        $this->assertCount(1, (new MessageSearchQuery)->search(1, 'hello', limit: 1));
        $this->assertCount(2, (new MessageSearchQuery)->search(1, 'hello', limit: 50));
    }

    public function test_edited_message_loses_its_former_tokens(): void
    {
        // The write path REPLACES tokens rather than appending, because
        // updateOrInsert can change a body. Without this, an edit from "cats" to
        // "dogs" would leave "cats" permanently findable.
        $indexer = new MessageSearchIndexer;
        $this->assertCount(1, (new MessageSearchQuery)->search(1, 'brown'));

        $indexer->index(1, 2, 'the quick red fox', 2, 1001);

        $this->assertCount(0, (new MessageSearchQuery)->search(1, 'brown'));
        $this->assertCount(1, (new MessageSearchQuery)->search(1, 'red'));
    }

    public function test_deleted_message_stops_matching(): void
    {
        // Message 1 is 'hello world'; message 3 is 'hello there friend' and
        // still matches 'hello', so the surviving match is asserted rather than
        // assumed away. What must vanish is the deleted message's OWN token.
        (new SafeDelete)->deleteMessages(1, [1]);

        $this->assertCount(1, (new MessageSearchQuery)->search(1, 'hello'));
        $this->assertCount(0, (new MessageSearchQuery)->search(1, 'world'));

        // And no orphan token row survives to match a search at all.
        $this->assertSame(0, DB::table('tf_message_search')
            ->where('account_id', 1)->where('id', 1)->count());
    }
}
