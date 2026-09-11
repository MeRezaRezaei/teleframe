<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Queries;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\Queries\MessageSearchQuery;

class MessageSearchQueryTest extends \MeRezaRezaei\Teleframe\Tests\Schema\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('tf_messages')) {
            Schema::create('tf_messages', function ($table) {
                $table->bigIncrements('id');
                $table->bigInteger('constructor_id');
                $table->bigInteger('account_id');
                $table->bigInteger('peer_id');
                $table->bigInteger('message_id');
                $table->text('tl_data');
                $table->text('message_text')->nullable();
                $table->timestamps();
            });
        }

        DB::table('tf_messages')->insert([
            [
                'peer_id' => 1001, 'message_id' => 1, 'account_id' => 1,
                'constructor_id' => 0, 'tl_data' => '{}',
                'message_text' => 'hello world',
                'created_at' => now(),
            ],
            [
                'peer_id' => 1001, 'message_id' => 2, 'account_id' => 1,
                'constructor_id' => 0, 'tl_data' => '{}',
                'message_text' => 'the quick brown fox',
                'created_at' => now(),
            ],
            [
                'peer_id' => 1001, 'message_id' => 3, 'account_id' => 1,
                'constructor_id' => 0, 'tl_data' => '{}',
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
            'peer_id' => 1001, 'message_id' => 10, 'account_id' => 2,
            'constructor_id' => 0, 'tl_data' => '{}',
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
