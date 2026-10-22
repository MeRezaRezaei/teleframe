<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\RelayServer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketRelayServer;
use PHPUnit\Framework\TestCase;

/**
 * Tests for sponsor channel support on MTProto proxy servers.
 *
 * The sponsor channel is advertised to connecting clients so Telegram apps
 * can display it in proxy settings (sponsor channel promotion).
 */
class SponsorChannelTest extends TestCase
{
    public function test_relay_server_stores_and_returns_sponsor_channel(): void
    {
        $server = new RelayServer(
            listenHost: '127.0.0.1',
            port: 443,
            sponsorChannel: '@mychannel',
        );

        $this->assertSame('@mychannel', $server->getSponsorChannel());
    }

    public function test_relay_server_defaults_sponsor_channel_to_null(): void
    {
        $server = new RelayServer(
            listenHost: '127.0.0.1',
            port: 443,
        );

        $this->assertNull($server->getSponsorChannel());
    }

    public function test_relay_server_accepts_empty_string_as_no_sponsor(): void
    {
        $server = new RelayServer(
            listenHost: '127.0.0.1',
            port: 443,
            sponsorChannel: '',
        );

        $this->assertSame('', $server->getSponsorChannel());
    }

    public function test_websocket_relay_server_stores_and_returns_sponsor_channel(): void
    {
        $server = new WebSocketRelayServer(
            listenHost: '127.0.0.1',
            port: 443,
            sponsorChannel: '@sponsor',
        );

        $this->assertSame('@sponsor', $server->getSponsorChannel());
    }

    public function test_websocket_relay_server_defaults_sponsor_channel_to_null(): void
    {
        $server = new WebSocketRelayServer(
            listenHost: '127.0.0.1',
            port: 443,
        );

        $this->assertNull($server->getSponsorChannel());
    }

    public function test_sponsor_channel_with_telegram_username_format(): void
    {
        $channels = ['@channel', '@t_channel_2024', '@a'];

        foreach ($channels as $channel) {
            $server = new RelayServer(
                listenHost: '127.0.0.1',
                port: 443,
                sponsorChannel: $channel,
            );

            $this->assertSame($channel, $server->getSponsorChannel(), "Failed for channel: {$channel}");
        }
    }
}
