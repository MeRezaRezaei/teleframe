<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Queries;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Queries\MessageQuery;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;

/**
 * Tests for MessageQuery typed builder. Creates a tf_messages table in-memory
 * and redirects the TlMessage model to it via setTable() so the builder
 * queries the test table instead of tl_message_message.
 */
class MessageQueryTest extends \MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // tf_messages comes from the migrated real DDL (IngestTestCase):
        // bigInteger id PK (no autoincrement — supply explicitly) + date NOT NULL.
        DB::table('tf_messages')->insert([
            ['id' => 1, 'peer_id' => 1001, 'message_id' => 1, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{}', 'message_text' => 'hello', 'date' => now()->subHour(2)->timestamp, 'created_at' => now()->subHour(2)],
            ['id' => 2, 'peer_id' => 1001, 'message_id' => 2, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{}', 'message_text' => 'world', 'date' => now()->subMinute(30)->timestamp, 'created_at' => now()->subMinute(30)],
            ['id' => 3, 'peer_id' => 1001, 'message_id' => 3, 'account_id' => 1, 'constructor_id' => 0, 'tl_data' => '{}', 'message_text' => 'latest', 'date' => now()->timestamp, 'created_at' => now()],
            ['id' => 4, 'peer_id' => 1001, 'message_id' => 1, 'account_id' => 2, 'constructor_id' => 0, 'tl_data' => '{}', 'message_text' => 'other', 'date' => now()->timestamp, 'created_at' => now()],
        ]);
    }

    /**
     * Build a MessageQuery targeting the tf_messages test table.
     */
    private function messages(): MessageQuery
    {
        $model = new TlMessage();
        $model->setTable('tf_messages');

        // Get the underlying QueryBuilder from the model's default query,
        // then wrap it in our typed MessageQuery.
        $query = $model->newQuery()->getQuery();
        $builder = new MessageQuery($query);
        $builder->setModel($model);

        return $builder;
    }

    public function test_for_account_scopes_by_account_id(): void
    {
        $results = $this->messages()->forAccount(1)->get();
        $this->assertCount(3, $results);
    }

    public function test_for_peer_filters_by_peer_id(): void
    {
        $results = $this->messages()->forAccount(1)->forPeer(1001)->get();
        $this->assertCount(3, $results);
    }

    public function test_recent_returns_most_recent_first(): void
    {
        $results = $this->messages()->forAccount(1)->recent(2)->get();
        $this->assertCount(2, $results);
        $this->assertSame('latest', $results[0]->message_text);
        $this->assertSame('world', $results[1]->message_text);
    }

    public function test_since_filters_by_timestamp(): void
    {
        $cutoff = now()->subMinutes(45)->timestamp;
        $results = $this->messages()->forAccount(1)->since($cutoff)->get();
        $this->assertCount(2, $results);
    }

    public function test_before_id_keyset_pagination(): void
    {
        $results = $this->messages()
            ->forAccount(1)
            ->forPeer(1001)
            ->beforeId(2, 2)
            ->get();
        $this->assertCount(1, $results);
    }

    public function test_after_id_keyset_pagination(): void
    {
        $results = $this->messages()
            ->forAccount(1)
            ->forPeer(1001)
            ->afterId(1, 2)
            ->get();
        $this->assertCount(2, $results);
        $this->assertSame('world', $results[0]->message_text);
        $this->assertSame('latest', $results[1]->message_text);
    }
}
