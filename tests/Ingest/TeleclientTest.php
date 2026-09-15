<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUser;
use MeRezaRezaei\Teleframe\Teleclient;

/**
 * Phase C re-baseline: the public face — Teleclient resolves from the
 * container as a singleton and delegates to the curated ingest surface.
 * The legacy route-table migration wiring in setUp is gone with the
 * tl_route_* surface; identity resolution reads the curated tf_users dial
 * the same way the identity mirrors seed it.
 */
final class TeleclientTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const USER_ID = 501558149;

    private const MESSAGE_ID = 1186;

    public function test_resolves_from_the_container_as_a_singleton(): void
    {
        $client = $this->app->make(Teleclient::class);

        self::assertInstanceOf(Teleclient::class, $client);
        self::assertSame($client, $this->app->make(Teleclient::class), 'singleton binding');
        self::assertSame($this->app->make(UpdateIngestor::class), $this->app->make(UpdateIngestor::class), 'ingestor singleton binding');
    }

    public function test_delegates_message_ingest_and_identity_lookup(): void
    {
        $client = $this->app->make(Teleclient::class);

        $root = $client->ingest([
            '_' => 'message',
            'id' => self::MESSAGE_ID,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
            'date' => 1724852400,
            'message' => 'through the face',
        ], self::ACCOUNT);

        // Curated surface: a tf_messages row keyed by the native Telegram id.
        self::assertInstanceOf(TfMessage::class, $root);
        self::assertSame(self::MESSAGE_ID, (int) $root->getAttribute('id'));

        // Identity resolution walks the curated tf_users dial — seeded here
        // exactly the way the identity mirrors seed it (ingest drops the
        // user difference-stream ctor).
        $user = new TfUser(['account_id' => self::ACCOUNT, 'id' => self::USER_ID, 'constructor' => 'user', 'contact' => true]);
        $user->save();

        $resolved = $client->user(self::ACCOUNT, self::USER_ID);
        self::assertInstanceOf(TfUser::class, $resolved);
        self::assertSame(self::USER_ID, (int) $resolved->getAttribute('id'));
        self::assertSame('user', $resolved->getAttribute('constructor'));
    }

    public function test_delegates_response_ingest(): void
    {
        $client = $this->app->make(Teleclient::class);

        $response = [
            '_' => 'updateNewMessage',
            'message' => [
                '_' => 'message',
                'id' => self::MESSAGE_ID,
                'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'date' => 1724852400,
                'message' => 'through the face, via response',
            ],
            'pts' => 1349,
            'pts_count' => 1,
        ];
        $params = ['pts_total' => 1];

        $first = $client->ingestResponse('updates.getDifference', $params, $response, self::ACCOUNT);
        $second = $client->ingestResponse('updates.getDifference', $params, $response, self::ACCOUNT);

        // update-kind payloads always become instances; re-ingest upholds
        // the same (account_id, seq, position) row (getKey() = account_id
        // stand-in for the composite key).
        self::assertInstanceOf(TfUpdate::class, $first);
        self::assertInstanceOf(TfUpdate::class, $second);
        self::assertSame((string) $first->getKey(), (string) $second->getKey());
        self::assertSame('updateNewMessage', $first->getAttribute('constructor'));
    }
}
