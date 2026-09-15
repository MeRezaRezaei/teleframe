<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Queries;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Queries\MessageSearchQuery;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

class MessageSearchQueryTest extends IngestTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Curated tf_messages: composite PK (account_id, id), peer pair,
        // constructor TEXT, date INTEGER, message TEXT. Legacy columns
        // (message_id/constructor_id/tl_data/message_text/created_at) do not
        // exist on this surface.
        DB::table('tf_messages')->insert([
            [
                'account_id' => 1, 'id' => 1, 'peer_type' => 2, 'peer_id' => 1001,
                'constructor' => 'message', 'date' => now()->timestamp,
                'message' => 'hello world',
            ],
            [
                'account_id' => 1, 'id' => 2, 'peer_type' => 2, 'peer_id' => 1001,
                'constructor' => 'message', 'date' => now()->timestamp,
                'message' => 'the quick brown fox',
            ],
            [
                'account_id' => 1, 'id' => 3, 'peer_type' => 2, 'peer_id' => 1001,
                'constructor' => 'message', 'date' => now()->timestamp,
                'message' => 'hello there friend',
            ],
        ]);
    }

    public function test_search_finds_matching_messages(): void
    {
        $results = (new MessageSearchQuery)->search(1, 'hello');
        $this->assertCount(2, $results);
    }

    public function test_search_scopes_to_account(): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => 2, 'id' => 10, 'peer_type' => 2, 'peer_id' => 1001,
            'constructor' => 'message', 'date' => now()->timestamp,
            'message' => 'hello from other account',
        ]);

        $results = (new MessageSearchQuery)->search(1, 'hello');
        $this->assertCount(2, $results); // only account 1
    }

    public function test_search_with_peer_filter(): void
    {
        $results = (new MessageSearchQuery)->search(1, 'hello', peerId: 1001);
        $this->assertCount(2, $results);
    }
}
