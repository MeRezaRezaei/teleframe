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

        // tf_messages comes from the migrated real DDL (IngestTestCase):
        // bigInteger id PK + message_id/peer_id/date NOT NULL + tl_data.
        DB::table('tf_messages')->insert([
            [
                'id' => 1, 'peer_id' => 1001, 'message_id' => 1, 'account_id' => 1,
                'constructor_id' => 0, 'date' => now()->timestamp, 'tl_data' => '{}',
                'message_text' => 'hello world',
                'created_at' => now(),
            ],
            [
                'id' => 2, 'peer_id' => 1001, 'message_id' => 2, 'account_id' => 1,
                'constructor_id' => 0, 'date' => now()->timestamp, 'tl_data' => '{}',
                'message_text' => 'the quick brown fox',
                'created_at' => now(),
            ],
            [
                'id' => 3, 'peer_id' => 1001, 'message_id' => 3, 'account_id' => 1,
                'constructor_id' => 0, 'date' => now()->timestamp, 'tl_data' => '{}',
                'message_text' => 'hello there friend',
                'created_at' => now(),
            ],
        ]);
    }

    public function test_search_finds_matching_messages(): void
    {
        $results = (new MessageSearchQuery())->search(1, 'hello');
        $this->assertCount(2, $results);
    }

    public function test_search_scopes_to_account(): void
    {
        DB::table('tf_messages')->insert([
            'id' => 10, 'peer_id' => 1001, 'message_id' => 10, 'account_id' => 2,
            'constructor_id' => 0, 'date' => now()->timestamp, 'tl_data' => '{}',
            'message_text' => 'hello from other account',
            'created_at' => now(),
        ]);

        $results = (new MessageSearchQuery())->search(1, 'hello');
        $this->assertCount(2, $results); // only account 1
    }

    public function test_search_with_peer_filter(): void
    {
        $results = (new MessageSearchQuery())->search(1, 'hello', peerId: 1001);
        $this->assertCount(2, $results);
    }
}
