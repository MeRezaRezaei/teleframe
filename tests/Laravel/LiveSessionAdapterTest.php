<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Tests;

use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use PHPUnit\Framework\TestCase;

class LiveSessionAdapterTest extends TestCase
{
    public function test_session_export_and_import_string(): void
    {
        $session = new SessionData(
            dcId: 2,
            authKey: random_bytes(256),
            serverTimeDelta: -15,
            seqNo: 5,
            userId: 987654321
        );

        $exportedString = $session->exportString();
        $this->assertIsString($exportedString);

        $imported = SessionData::importString($exportedString);

        $this->assertEquals(2, $imported->dcId);
        $this->assertEquals($session->authKey, $imported->authKey);
        $this->assertEquals(-15, $imported->serverTimeDelta);
        $this->assertEquals(987654321, $imported->userId);
    }

    public function test_session_adapter_loads_from_database_record(): void
    {
        $accountRecord = [
            'id' => 123456789,
            'api_id' => 11111,
            'api_hash' => 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4',
            'dc_id' => 2,
            'auth_key' => base64_encode(str_repeat("\x42", 256)),
            'server_time_delta' => -10,
            'seq_no' => 4,
        ];

        $session = SessionData::fromArray($accountRecord);

        $client = new Client(
            apiId: $accountRecord['api_id'],
            apiHash: $accountRecord['api_hash'],
            session: $session
        );

        $this->assertEquals(2, $client->getSession()->dcId);
        $this->assertEquals(str_repeat("\x42", 256), $client->getSession()->authKey);
        $this->assertEquals(-10, $client->getSession()->serverTimeDelta);

        // Perform RPC call with session
        $response = $client->call('help.getConfig');
        $this->assertEquals('rpc_result', $response['_']);
        $this->assertEquals('help.getConfig', $response['method']);
        $this->assertEquals(2, $response['dc_id']);
    }

    public function test_direct_tcp_socket_to_telegram_dc(): void
    {
        $dcIp = Client::DC_IPS[2]; // 149.154.167.51
        $port = Client::DEFAULT_PORT; // 443

        // W-36: keep the connect bound to 1s (was 3.0) so a dropped-packet
        // network cannot stall CI for the full default connect timeout; the
        // test still marks itself skipped when the DC is unreachable.
        $socket = @fsockopen($dcIp, $port, $errno, $errstr, 1.0);
        if ($socket) {
            $this->assertIsResource($socket);
            fclose($socket);
        } else {
            $this->markTestSkipped("Direct internet TCP access to Telegram DC ({$dcIp}:{$port}) unavailable in test environment.");
        }
    }
}
