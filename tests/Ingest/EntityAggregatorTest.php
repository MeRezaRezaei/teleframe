<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;

/**
 * Entity aggregation on the domain surface: resolve the domain row for a
 * referenced entity (user/chat/channel) by (tenant, telegram id); null
 * when absent. One table per domain (TlUser, TlChat, TlChannel) — no
 * per-constructor models, no currentInstance relation. Domain rows are
 * tenant-scoped via the composite PK (id, account_id).
 */
final class EntityAggregatorTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const OTHER_ACCOUNT = 8;

    private const USER_ID = 501558149;

    private const CHANNEL_ID = 1737473577;

    /**
     * @return array<string, mixed>
     */
    private static function userPayload(string $firstName = 'Reza'): array
    {
        return [
            '_' => 'user',
            'flags' => (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3),
            'id' => self::USER_ID,
            'access_hash' => -5988024083302710253,
            'first_name' => $firstName,
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function channelPayload(): array
    {
        return [
            '_' => 'channel',
            // verified | megagroup | access_hash
            'flags' => (1 << 7) | (1 << 8) | (1 << 13),
            'verified' => true,
            'megagroup' => true,
            'id' => self::CHANNEL_ID,
            'access_hash' => -7779317524312221622,
            'title' => 'Teleframe Café',
            'photo' => ['_' => 'chatPhotoEmpty'],
            'date' => 1712345678,
        ];
    }

    private function ingestUser(int $accountId = self::ACCOUNT, string $firstName = 'Reza'): TlUser
    {
        $root = (new UpdateIngestor())->ingest(self::userPayload($firstName), $accountId);
        assert($root instanceof TlUser);

        return $root;
    }

    public function test_finds_user_anchor_with_current_instance(): void
    {
        $written = $this->ingestUser();

        $anchor = (new EntityAggregator())->user(self::ACCOUNT, self::USER_ID);

        self::assertInstanceOf(TlUser::class, $anchor);
        self::assertSame((int) $written->id, (int) $anchor->id, 'resolves the row the ingestor wrote');
        self::assertSame(self::USER_ID, (int) $anchor->id, 'native Telegram id is the PK');
        self::assertSame(self::ACCOUNT, (int) $anchor->account_id);
        self::assertSame(0x31774388, (int) $anchor->constructor_id);

        // The domain row carries everything: extracted columns + full tl_data.
        self::assertSame('Reza', $anchor->first_name);
        self::assertSame('RezaRezaei', $anchor->username);
        self::assertSame('user', $anchor->tl_data['_']);
    }

    public function test_unknown_user_is_null(): void
    {
        $this->ingestUser();

        self::assertNull((new EntityAggregator())->user(self::ACCOUNT, 999999));
    }

    public function test_user_rows_are_tenant_scoped(): void
    {
        $this->ingestUser();

        // Composite PK (id, account_id): each tenant holds its own row for
        // the same Telegram entity — account 8 sees nothing until it ingests.
        $a = (new EntityAggregator())->user(self::ACCOUNT, self::USER_ID);
        self::assertNotNull($a);
        self::assertSame('Reza', $a->first_name);
        self::assertNull((new EntityAggregator())->user(self::OTHER_ACCOUNT, self::USER_ID));

        $b = $this->ingestUser(self::OTHER_ACCOUNT, 'Ali');
        $resolved = (new EntityAggregator())->user(self::OTHER_ACCOUNT, self::USER_ID);
        self::assertNotNull($resolved);
        self::assertSame(self::USER_ID, (int) $resolved->id, 'same native id');
        self::assertSame(self::OTHER_ACCOUNT, (int) $resolved->account_id, '... but a distinct tenant row');
        self::assertSame('Ali', $resolved->first_name);
        self::assertSame(2, TlUser::acrossAccounts()->count());
        self::assertSame((int) $b->id, self::USER_ID);
    }

    public function test_latest_constructor_wins_on_re_ingest(): void
    {
        $this->ingestUser(); // user#31774388 under account 7

        // userEmpty#d3bc4b7a for the SAME telegram id arrives later (account
        // deleted upstream) — upsert hits the same (id, account_id) row and
        // the discriminator follows the latest constructor.
        (new UpdateIngestor())->ingest([
            '_' => 'userEmpty',
            'id' => self::USER_ID,
        ], self::ACCOUNT);

        $anchor = (new EntityAggregator())->user(self::ACCOUNT, self::USER_ID);

        self::assertInstanceOf(TlUser::class, $anchor);
        self::assertSame(1, TlUser::query()->count(), 'same row, re-upserted');
        self::assertSame('userEmpty', $anchor->tl_data['_'], 'latest constructor wins');
    }

    public function test_deleted_users_do_not_resolve(): void
    {
        (new UpdateIngestor())->ingest([
            '_' => 'user',
            'flags' => (1 << 13), // deleted (flags.13)
            'deleted' => true,
            'id' => self::USER_ID,
            'access_hash' => -5988024083302710253,
            'first_name' => 'Reza',
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
        ], self::ACCOUNT);

        self::assertNull((new EntityAggregator())->user(self::ACCOUNT, self::USER_ID), 'latest row is deleted → null');
    }

    public function test_resolves_channel_anchors(): void
    {
        (new UpdateIngestor())->ingest(self::channelPayload(), self::ACCOUNT);

        $aggregator = new EntityAggregator();

        // channel/channelForbidden are `= Chat;` ctors routed to tf_channels
        // (spec §5) — they resolve through channel(), not chat().
        $channel = $aggregator->channel(self::ACCOUNT, self::CHANNEL_ID);
        self::assertInstanceOf(TlChannel::class, $channel);
        self::assertSame(self::CHANNEL_ID, (int) $channel->id);
        self::assertSame('Teleframe Café', $channel->title);
        self::assertTrue((bool) $channel->is_verified);
        self::assertTrue((bool) $channel->is_megagroup);

        self::assertNull($aggregator->chat(self::ACCOUNT, self::CHANNEL_ID), 'channel rows do not live in tf_chats');
        self::assertNull($aggregator->channel(self::ACCOUNT, 42), 'unknown channel id');
        self::assertNull($aggregator->channel(self::OTHER_ACCOUNT, self::CHANNEL_ID), 'tenant-scoped: other account sees nothing yet');
    }
}
