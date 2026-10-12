<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\New;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasNestedUpdateFixtures;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * Cross-account query patterns over the curated tf_messages dial (plan
 * Task 3.1): the same nested tree ingested into two accounts is queryable
 * across accounts by its canonical peer pair (peer_type/peer_id), while the
 * AccountScoped default keeps each account isolated and AccountContext::for()
 * scopes a block to one tenant. Consumes the canonical
 * HasNestedUpdateFixtures payloads.
 */
final class CrossAccountQueryTest extends IngestTestCase
{
    use HasNestedUpdateFixtures;

    /**
     * The tenants this fixture writes under: SEVEN and EIGHT.
     *
     * MEASURED, not assumed: `self::ACCOUNT` is `HasNestedUpdateFixtures::FIXTURE_ACCOUNT`
     * (= 7) and every `ingestTree()` call in this file is made twice — once under
     * `self::ACCOUNT` and once under the literal 8. Both ids are filtered on too
     * (`AccountContext::for(self::ACCOUNT)`, `TfMessage::forAccount(8)`), so the
     * pair is the whole tenancy this class reasons about.
     *
     * Neither was declared, and with foreign-key enforcement on (19 constraints) the
     * ingest helper died at the database rather than at the query surface under
     * test — all three tests, on the first `tf_updates` insert:
     *
     *   Illuminate\Database\QueryException: SQLSTATE[23000]: Integrity constraint
     *   violation: 19 FOREIGN KEY constraint failed (Connection: ingest,
     *   Database: :memory:, SQL: insert into "tf_updates" ("account_id", "seq",
     *   "position", "constructor") values (7, 0, 0, updateNewMessage))
     *
     * Note WHICH id fails first: 7, the default of `ingestTree()` — so declaring
     * only the literal would have moved the failure one line and hidden the other.
     * This class is the only place in the tree that queries the same peer pair from
     * two tenants at once, so it is the only place that needs two rows planted.
     *
     * The fix is HERE rather than in `seedIngestTenant`, because that helper's
     * contract is deliberate — "each test declares the tenants it means to use, so a
     * test that writes under an unplanted id fails loudly instead of being rescued."
     * This test was writing under unplanted ids, and the loud failure is the
     * mechanism working.
     */
    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT, self::SECOND_ACCOUNT];
    }

    private const ACCOUNT = self::FIXTURE_ACCOUNT;

    /**
     * The second tenant this class ingests the SAME peer pair under — the whole
     * point of the class. Named rather than left as the bare literal 8 at each
     * call site, because the declaration above has to be checkable against the
     * writes and a name is what makes that checkable by eye.
     */
    private const SECOND_ACCOUNT = 8;

    private const CHANNEL_ID = self::FIXTURE_CHANNEL_ID;

    /** peerChannel normalizes to peer_type 3 on the curated dial. */
    private const CHANNEL_PEER_TYPE = 3;

    private function ingestTree(int $accountId = self::ACCOUNT): void
    {
        $ingestor = new UpdateIngestor;
        $ingestor->ingest(self::channelPayload(), $accountId);
        $ingestor->ingest(self::userPayload(), $accountId);
        $ingestor->ingest(self::updateNewMessagePayload(), $accountId);
    }

    private function channelMessages(): Builder
    {
        return TfMessage::query()
            ->where('peer_type', self::CHANNEL_PEER_TYPE)
            ->where('peer_id', self::CHANNEL_ID);
    }

    public function test_shared_channel_message_visible_across_accounts_by_canonical_peer(): void
    {
        $this->ingestTree(self::ACCOUNT);
        $this->ingestTree(self::SECOND_ACCOUNT);

        $accounts = TfMessage::acrossAccounts()
            ->where('peer_type', self::CHANNEL_PEER_TYPE)
            ->where('peer_id', self::CHANNEL_ID)
            ->distinct()
            ->orderBy('account_id')
            ->pluck('account_id')
            ->all();

        self::assertSame([self::ACCOUNT, self::SECOND_ACCOUNT], $accounts);
    }

    public function test_account_context_isolates_default_scope(): void
    {
        $this->ingestTree(self::ACCOUNT);
        $this->ingestTree(self::SECOND_ACCOUNT);

        AccountContext::for(self::ACCOUNT, function (): void {
            self::assertSame(
                1,
                $this->channelMessages()->count(),
                'default scope must hide account 8\'s row',
            );
            self::assertSame(
                1,
                TfMessage::forAccount(self::SECOND_ACCOUNT)->where('peer_type', self::CHANNEL_PEER_TYPE)
                    ->where('peer_id', self::CHANNEL_ID)
                    ->count(),
                'forAccount(8) must expose exactly account 8\'s row',
            );
        });
    }

    public function test_account_context_for_code(): void
    {
        $this->ingestTree(self::ACCOUNT);
        $this->ingestTree(self::SECOND_ACCOUNT);

        $result = AccountContext::for(self::ACCOUNT, function (): int {
            $message = $this->channelMessages()->sole();

            self::assertSame(self::ACCOUNT, (int) $message->account_id);
            self::assertSame(self::CHANNEL_ID, (int) $message->peer_id);

            return $message->id;
        });

        self::assertSame(1186, $result, 'AccountContext::for must return the callback value');
    }
}
