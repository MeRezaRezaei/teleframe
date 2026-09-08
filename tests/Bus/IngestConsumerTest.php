<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;
use MeRezaRezaei\Teleframe\Teleclient;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;

/**
 * Plan Task 2 end-to-end: stream entries → consumer group read → route
 * decision (default ingest into sqlite truth / forward to target stream)
 * → ack, with poison payloads dead-lettered. Reuses the P2 ingest boot
 * (IngestTestCase + RunsMigrations) so default-path rows land in real
 * migrated tables.
 */
final class IngestConsumerTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const USER_ID = 501558149;

    private ArrayRedis $redis;

    private IngestConsumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new ArrayRedis();
        $this->consumer = new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
        );
    }

    public function test_empty_batch_reports_zero(): void
    {
        $stats = $this->consumer->consumeOnce();

        self::assertSame(['processed' => 0, 'forwarded' => 0], $stats);
    }

    public function test_end_to_end_default_ingest_forward_and_poison_dead_letter(): void
    {
        $table = new RouteTable($this->redis);
        $table->set('updateNewMessage*', 'tg:target:messages');

        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);

        // 1) ingestable user update → default path → sqlite truth
        $sink->handle($this->userUpdate(), (string) self::ACCOUNT);

        // 2) routed update → forwarded verbatim to the target stream
        $routed = ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 7]];
        $sink->handle($routed, (string) self::ACCOUNT);

        // 3) poison payload → dead-letter + ack
        $this->redis->xadd(StreamSchema::STREAM, '*', [
            'account_id' => (string) self::ACCOUNT,
            'update' => 'this is not json',
            'ts' => (string) time(),
        ]);

        $stats = $this->consumer->consumeOnce();

        self::assertSame(['processed' => 3, 'forwarded' => 1], $stats);

        // Default path stored the user under the tenant
        $client = $this->app->make(Teleclient::class);
        $user = $client->user(self::ACCOUNT, self::USER_ID);
        self::assertNotNull($user);
        self::assertSame('Reza', $user->currentInstance->first_name);

        // Forwarded entry kept the original payload untouched
        $targets = $this->redis->streamEntries('tg:target:messages');
        self::assertCount(1, $targets);
        self::assertSame((string) self::ACCOUNT, $targets[0][1]['account_id']);
        self::assertSame($routed, StreamSchema::decode($targets[0][1]['update'])['update']);

        // Poison landed on the dead-letter stream, not in truth
        $dead = $this->redis->streamEntries(StreamSchema::DL);
        self::assertCount(1, $dead);
        self::assertSame('this is not json', $dead[0][1]['update']);

        // The whole batch is acked — nothing stays pending for a retry
        $pending = $this->redis->xreadgroup(
            StreamSchema::GROUP,
            StreamSchema::CONSUMER,
            [StreamSchema::STREAM => 10],
            '0',
        );
        self::assertSame([], $pending);

        // A second pass has nothing new to do
        self::assertSame(['processed' => 0, 'forwarded' => 0], $this->consumer->consumeOnce());
    }

    public function test_persistent_ingest_failure_dead_letters_on_the_third_cycle_and_unwedges_the_group(): void
    {
        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);

        // Decodes fine, but the deep constructor is unknown to this build —
        // ingest() throws deterministically on every attempt.
        $sink->handle(
            ['_' => 'updateNewMessage', 'message' => ['_' => 'messageDefinitelyNotInThisBuild', 'id' => 7]],
            (string) self::ACCOUNT,
        );

        // A healthy entry appended after the throwing one must not be
        // stranded behind it in the same batch.
        $sink->handle($this->userUpdate(), (string) self::ACCOUNT);

        // Cycle 1: the throwing entry stays pending; the healthy tail lands
        self::assertSame(['processed' => 1, 'forwarded' => 0], $this->consumer->consumeOnce());
        self::assertSame([], $this->redis->streamEntries(StreamSchema::DL));

        $client = $this->app->make(Teleclient::class);
        self::assertSame('Reza', $client->user(self::ACCOUNT, self::USER_ID)?->currentInstance?->first_name);

        // Cycle 2: retry throws again — still pending, still no dead-letter
        self::assertSame(['processed' => 0, 'forwarded' => 0], $this->consumer->consumeOnce());
        self::assertSame([], $this->redis->streamEntries(StreamSchema::DL));

        // Cycle 3: third consecutive strike → dead-lettered with the
        // original payload plus reason/error, acked, group free to move
        self::assertSame(['processed' => 1, 'forwarded' => 0], $this->consumer->consumeOnce());

        $dead = $this->redis->streamEntries(StreamSchema::DL);
        self::assertCount(1, $dead);
        self::assertSame('ingest-failed', $dead[0][1]['reason']);
        self::assertStringContainsString('messageDefinitelyNotInThisBuild', $dead[0][1]['error']);
        self::assertSame(
            ['_' => 'updateNewMessage', 'message' => ['_' => 'messageDefinitelyNotInThisBuild', 'id' => 7]],
            StreamSchema::decode($dead[0][1]['update'])['update'],
        );
        self::assertSame((string) self::ACCOUNT, $dead[0][1]['account_id']);

        // Nothing stays pending for this consumer
        self::assertSame([], $this->redis->xreadgroup(
            StreamSchema::GROUP,
            StreamSchema::CONSUMER,
            [StreamSchema::STREAM => 10],
            '0',
        ));

        // The group keeps moving after the wedge is broken
        $sink->handle($this->userUpdate(), (string) self::ACCOUNT);
        self::assertSame(['processed' => 1, 'forwarded' => 0], $this->consumer->consumeOnce());
    }

    public function test_transient_ingest_path_failure_is_retried_without_dead_lettering(): void
    {
        $attempts = 0;
        $stored = [];
        $consumer = new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
            static function (TlInstanceModel $root, int $accountId) use (&$attempts, &$stored): void {
                $attempts++;
                if ($attempts < 3) {
                    throw new \RuntimeException('transient hiccup after the row committed');
                }
                $stored[] = $accountId;
            },
        );

        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);
        $sink->handle($this->userUpdate(), (string) self::ACCOUNT);

        // Two transient failures: the entry is retried, never dead-lettered
        self::assertSame(['processed' => 0, 'forwarded' => 0], $consumer->consumeOnce());
        self::assertSame(['processed' => 0, 'forwarded' => 0], $consumer->consumeOnce());
        self::assertSame([], $this->redis->streamEntries(StreamSchema::DL));

        // Third attempt succeeds → handled, acked, hook observed the landing
        self::assertSame(['processed' => 1, 'forwarded' => 0], $consumer->consumeOnce());
        self::assertSame([self::ACCOUNT], $stored);
        self::assertSame([], $this->redis->streamEntries(StreamSchema::DL));

        self::assertSame([], $this->redis->xreadgroup(
            StreamSchema::GROUP,
            StreamSchema::CONSUMER,
            [StreamSchema::STREAM => 10],
            '0',
        ));
    }

    public function test_on_stored_fires_for_default_path_only(): void
    {
        $table = new RouteTable($this->redis);
        $table->set('updateNewMessage*', 'tg:target:messages');

        $seen = [];
        $consumer = new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
            static function (TlInstanceModel $root, int $accountId) use (&$seen): void {
                $seen[] = [get_class($root), $accountId];
            },
        );

        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);
        $sink->handle($this->userUpdate(), (string) self::ACCOUNT);
        $sink->handle(['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 9]], (string) self::ACCOUNT);

        $consumer->consumeOnce();

        self::assertSame([[TlUserUser::class, self::ACCOUNT]], $seen);
    }


    public function test_routed_entries_fan_into_the_on_routed_seam(): void
    {
        $table = new RouteTable($this->redis);
        $table->set('updateNewMessage*', 'tg:target:messages');
        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);
        $sink->handle(['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 9]], (string) self::ACCOUNT);

        $routed = [];
        $consumer = new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
            null,
            static function (array $update, int $accountId) use (&$routed): void {
                $routed[] = [$update['_'], $accountId];
            },
        );

        $stats = $consumer->consumeOnce();

        self::assertSame(['processed' => 1, 'forwarded' => 1], $stats);
        self::assertSame([['updateNewMessage', self::ACCOUNT]], $routed);
        self::assertCount(1, $this->redis->streamEntries('tg:target:messages'));
    }

    /**
     * @return array<string, mixed>
     */
    private function userUpdate(): array
    {
        return [
            '_' => 'user',
            'flags' => (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3),
            'id' => self::USER_ID,
            'access_hash' => -5988024083302710253,
            'first_name' => 'Reza',
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
        ];
    }
}
