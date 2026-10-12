<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Queries;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Queries\MessageQuery;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * Tests for MessageQuery typed builder against the curated tf_messages dial.
 *
 * The builder wraps the base query of the curated TfMessage model so the
 * whole surface (AccountScoped casts, peer_type/peer_id pair, date INTEGER,
 * composite PK (account_id, id)) is the thing under test.
 */
class MessageQueryTest extends IngestTestCase
{
    /**
     * Tenants 1 and 2.
     *
     * `setUp()` seeds four `tf_messages` rows — three under account 1 (the ones
     * every scope/order/date assertion reads) and one under account 2 — and
     * `test_for_account_scopes_by_account_id` inserts a fifth under account 1
     * specifically so the scoping assertion has a second tenant to exclude.
     * `tf_messages.account_id` is the Telegram user id, not a vault row id:
     * `MirrorIngesterFactory.php:160` writes the caller's Telegram id straight
     * into it, and the FK targets `telegram_accounts.user_id`. Both parents must
     * therefore exist, or the very fixture that proves tenant isolation is the
     * one that cannot be written.
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

        AccountContext::reset();

        // Curated tf_messages: composite PK (account_id, id), inline peer
        // pair (peer_type/peer_id), constructor TEXT, date INTEGER, flags.
        // Legacy columns (message_id/constructor_id/tl_data/message_text/
        // created_at) do not exist on this surface.
        DB::table('tf_messages')->insert([
            ['account_id' => 1, 'id' => 1, 'peer_type' => 2, 'peer_id' => 1001, 'constructor' => 'message', 'date' => now()->subHour(2)->timestamp, 'message' => 'hello'],
            ['account_id' => 1, 'id' => 2, 'peer_type' => 2, 'peer_id' => 1001, 'constructor' => 'message', 'date' => now()->subMinute(30)->timestamp, 'message' => 'world'],
            ['account_id' => 1, 'id' => 3, 'peer_type' => 2, 'peer_id' => 1001, 'constructor' => 'message', 'date' => now()->timestamp, 'message' => 'latest'],
            ['account_id' => 2, 'id' => 4, 'peer_type' => 2, 'peer_id' => 1001, 'constructor' => 'message', 'date' => now()->timestamp, 'message' => 'other'],
        ]);
    }

    /**
     * Build a MessageQuery targeting tf_messages via the curated TfMessage
     * model.
     */
    private function messages(): MessageQuery
    {
        $model = new TfMessage;

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

    public function test_for_peer_with_type_filters_peer_type_and_peer_id(): void
    {
        // Same peer_id but a different peer_type is a DIFFERENT peer.
        DB::table('tf_messages')->insert([
            'account_id' => 1, 'id' => 5, 'peer_type' => 1, 'peer_id' => 1001,
            'constructor' => 'message', 'date' => now()->timestamp, 'message' => 'user-based',
        ]);

        $results = $this->messages()->forAccount(1)->forPeer(1001)->get();
        $this->assertCount(4, $results); // single-arg keeps the broad filter

        $results = $this->messages()->forAccount(1)->forPeer(1001, 2)->get();
        $this->assertCount(3, $results); // type-2 only

        $results = $this->messages()->forAccount(1)->forPeer(1001, 1)->get();
        $this->assertCount(1, $results); // type-1 only
        $this->assertSame('user-based', $results[0]->message);
    }

    public function test_recent_returns_most_recent_first(): void
    {
        $results = $this->messages()->forAccount(1)->recent(2)->get();
        $this->assertCount(2, $results);
        $this->assertSame('latest', $results[0]->message);
        $this->assertSame('world', $results[1]->message);
    }

    public function test_since_filters_by_date(): void
    {
        $cutoff = now()->subMinutes(45)->timestamp;
        $results = $this->messages()->forAccount(1)->since($cutoff)->get();
        $this->assertCount(2, $results);
    }

    public function test_since_filters_the_wire_date_column_not_ingest_time(): void
    {
        // The filter dimension is the wire `date` (unix epoch). Rows with a
        // date below the cutoff must be excluded, and the rows that survive
        // must carry a date at/after the cutoff — proving `date` (not some
        // ingest/created_at timestamp) is the queried column.
        $cutoff = now()->subMinutes(45)->timestamp;

        $results = $this->messages()->forAccount(1)->since($cutoff)->get();

        $this->assertCount(2, $results);
        foreach ($results as $row) {
            $this->assertGreaterThanOrEqual($cutoff, $row->date, 'since() must filter the wire date column');
            $this->assertFalse(
                property_exists($row, 'created_at'),
                'tf_messages has no created_at column; since() must not assume one'
            );
        }
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
        $this->assertSame('world', $results[0]->message);
        $this->assertSame('latest', $results[1]->message);
    }
}
