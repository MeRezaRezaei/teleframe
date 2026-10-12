<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\MTProto;

use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PacketCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\StreamSocket;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * PSR-3 logging seam through the deep MTProto wire layer (Client,
 * EncryptedConnection, StreamSocket): optional, silent by default,
 * metadata-only, and logging never swallows — exceptions always propagate.
 */
class LoggingSeamTest extends TestCase
{
    /** session_id the fake-server seed() helper encrypts with; decryptPacket enforces the echo. */
    private const SEED_SESSION_ID = 0x5E5510A1;

    public function test_client_defaults_to_null_logger(): void
    {
        $client = new Client;
        $prop = new \ReflectionProperty(Client::class, 'logger');
        $this->assertInstanceOf(NullLogger::class, $prop->getValue($client));
    }

    public function test_encrypted_connection_defaults_to_null_logger(): void
    {
        $session = new SessionData(dcId: 2, authKey: random_bytes(256));
        $conn = new EncryptedConnection($session);
        $prop = new \ReflectionProperty(EncryptedConnection::class, 'logger');
        $this->assertInstanceOf(NullLogger::class, $prop->getValue($conn));
    }

    public function test_set_logger_is_fluent(): void
    {
        $client = new Client;
        $logger = new ArrayLogger;
        $this->assertSame($client, $client->setLogger($logger));
        $prop = new \ReflectionProperty(Client::class, 'logger');
        $this->assertSame($logger, $prop->getValue($client));
    }

    public function test_connect_failure_logs_start_socket_connect_failed_and_rpc_failed_and_rethrows(): void
    {
        $session = new SessionData(dcId: 2, authKey: random_bytes(256));
        $logger = new ArrayLogger;
        $client = new class($session, $logger) extends Client
        {
            public function __construct(SessionData $session, LoggerInterface $logger)
            {
                parent::__construct(apiId: 1, apiHash: 'h', session: $session, live: true, logger: $logger);
            }

            /** @return array{0: string, 1: int} */
            protected function resolveHost(): array
            {
                return ['127.0.0.1', 1]; // connection-refused port: deterministic live failure
            }
        };

        try {
            $client->call('help.getNearestDc');
            $this->fail('dead endpoint must throw');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('127.0.0.1:1', $e->getMessage());
        }

        $messages = array_column($logger->records, 'message');
        $this->assertContains('teleframe.mtproto.connect.start', $messages);
        $this->assertContains('teleframe.mtproto.socket.connect', $messages, 'dial logger must be threaded into StreamSocket');
        $this->assertContains('teleframe.mtproto.connect.failed', $messages);
        $this->assertContains('teleframe.mtproto.rpc.failed', $messages);

        $failed = $this->recordsOf($logger, 'teleframe.mtproto.connect.failed')[0];
        $this->assertSame('error', $failed['level']);
        $this->assertSame(2, $failed['context']['dc']);
        $this->assertSame('127.0.0.1', $failed['context']['host']);
        $this->assertSame(1, $failed['context']['port']);
        $this->assertTrue(isset($failed['context']['exception']), 'failure context must name the exception class');

        $rpcFailed = $this->recordsOf($logger, 'teleframe.mtproto.rpc.failed')[0];
        $this->assertSame('error', $rpcFailed['level']);
        $this->assertSame('help.getNearestDc', $rpcFailed['context']['method']);

        $this->assertNull($this->connOf($client), 'failed connect must not cache a connection');
    }

    public function test_connection_call_logs_start_and_done_metadata(): void
    {
        [$clientSock, $serverSock] = $this->socketPair();
        $authKey = random_bytes(256);
        $session = new SessionData(dcId: 2, authKey: $authKey);
        $logger = new ArrayLogger;
        $conn = $this->idPinnedConn($this->pinnedConn(new EncryptedConnection($session, $clientSock, logger: $logger)));

        $this->seed($serverSock, $authKey, $this->cannedResult($this->nearestDc()));
        $this->assertSame($this->nearestDc(), $conn->call('help.getNearestDc'));

        $calls = $this->recordsOf($logger, 'teleframe.mtproto.connection.call');
        $this->assertCount(2, $calls); // start + done
        $this->assertSame('start', $calls[0]['context']['phase']);
        $this->assertSame('help.getNearestDc', $calls[0]['context']['method']);
        $this->assertSame('done', $calls[1]['context']['phase']);
        $this->assertSame(1, $calls[1]['context']['messages']);
        $this->assertGreaterThan(0, $calls[1]['context']['bytes']);
        $this->assertGreaterThanOrEqual(0, $calls[1]['context']['ms']);

        $conn->close();
        fclose($serverSock);
    }

    public function test_connection_failure_logs_connection_failed_and_rethrows(): void
    {
        [$clientSock, $serverSock] = $this->socketPair();
        $authKey = random_bytes(256);
        $session = new SessionData(dcId: 2, authKey: $authKey);
        $logger = new ArrayLogger;
        $conn = $this->idPinnedConn($this->pinnedConn(new EncryptedConnection($session, $clientSock, logger: $logger)));

        fclose($serverSock); // peer gone: the call hits EOF / write failure

        try {
            $conn->call('help.getNearestDc');
            $this->fail('dead peer must throw');
        } catch (RuntimeException) {
            // expected: logging never swallows
        }

        $failed = $this->recordsOf($logger, 'teleframe.mtproto.connection.failed');
        $this->assertCount(1, $failed);
        $this->assertSame('error', $failed[0]['level']);
        $this->assertSame('help.getNearestDc', $failed[0]['context']['method']);
        $this->assertTrue(isset($failed[0]['context']['exception']));

        $conn->close();
    }

    public function test_rpc_error_is_not_logged_as_connection_failure(): void
    {
        [$clientSock, $serverSock] = $this->socketPair();
        $authKey = random_bytes(256);
        $session = new SessionData(dcId: 2, authKey: $authKey);
        $logger = new ArrayLogger;
        $conn = $this->idPinnedConn($this->pinnedConn(new EncryptedConnection($session, $clientSock, logger: $logger)));

        $this->seed($serverSock, $authKey, TLEncoder::encodeObject('rpc_result', [
            'req_msg_id' => $this->pinnedMessageIdBase() + 4,
            'result' => ['_' => 'rpc_error', 'error_code' => 420, 'error_message' => 'SLOWMODE_WAIT_10'],
        ]));

        try {
            $conn->call('help.getNearestDc');
            $this->fail('rpc_error must throw a TelegramException');
        } catch (TelegramException $e) {
            $this->assertSame(420, $e->getCode());
        }

        $this->assertCount(0, $this->recordsOf($logger, 'teleframe.mtproto.connection.failed'), 'a business rpc_error is not a connection failure');
        $calls = $this->recordsOf($logger, 'teleframe.mtproto.connection.call');
        $this->assertSame('start', $calls[0]['context']['phase']);
        $this->assertCount(1, $calls, 'no done-phase record when the call throws');

        $conn->close();
        fclose($serverSock);
    }

    public function test_keepalive_logged_before_idle_rpc(): void
    {
        [$clientSock, $serverSock] = $this->socketPair();
        $authKey = random_bytes(256);
        $session = new SessionData(dcId: 2, authKey: $authKey);
        $logger = new ArrayLogger;
        $client = new Client(apiId: 1, apiHash: 'h', session: $session, live: true, logger: $logger);
        $conn = $this->idPinnedConn($this->pinnedConn(new SeededPingIdLoggingConnection($session, $clientSock)));
        $this->setConn($client, $conn);

        $this->seed($serverSock, $authKey, $this->cannedResult($this->nearestDc()));
        $this->assertSame($this->nearestDc(), $client->call('help.getNearestDc')); // conn live + recently active
        $this->decryptRequest($serverSock, $authKey); // drain the invokeWithLayer-wrapped init request

        // simulate 60s of silence: the next call must keepalive BEFORE the RPC
        $prop = new \ReflectionProperty(EncryptedConnection::class, 'lastActivity');
        $prop->setValue($conn, microtime(true) - 60.0);

        SeededPingIdLoggingConnection::$pingId = 5; // lazy keepalive ping must receive an echoing pong
        $this->seed($serverSock, $authKey, TLEncoder::encodeObject('pong', ['msg_id' => 7, 'ping_id' => 5]));
        $this->seed($serverSock, $authKey, $this->cannedResult($this->nearestDc(), $this->pinnedMessageIdBase() + 12));

        $this->assertSame($this->nearestDc(), $client->call('help.getConfig'));

        $keepalive = $this->recordsOf($logger, 'teleframe.mtproto.keepalive');
        $this->assertCount(1, $keepalive);
        $this->assertSame('debug', $keepalive[0]['level']);
        $this->assertGreaterThanOrEqual(45, $keepalive[0]['context']['idle_seconds']);

        $client->close();
        fclose($serverSock);
    }

    public function test_socket_read_failure_logs_read_failed(): void
    {
        [$a, $b] = $this->socketPair();
        fclose($b); // clean EOF: empty read
        $logger = new ArrayLogger;

        try {
            StreamSocket::read($a, 16, $logger);
            $this->fail('reading a closed peer must throw');
        } catch (RuntimeException) {
            // expected
        }

        $failed = $this->recordsOf($logger, 'teleframe.mtproto.socket.read_failed');
        $this->assertCount(1, $failed);
        $this->assertSame('error', $failed[0]['level']);
        $this->assertSame(16, $failed[0]['context']['length']);

        fclose($a);
    }

    public function test_socket_write_failure_logs_write_failed(): void
    {
        [$a, $b] = $this->socketPair();
        $logger = new ArrayLogger;
        stream_set_blocking($a, false); // peer gone + non-blocking: fwrite returns false, not a TypeError
        fclose($b);

        try {
            StreamSocket::write($a, 'x', $logger);
            $this->fail('writing to a vanished peer must throw');
        } catch (RuntimeException) {
            // expected
        }

        $failed = $this->recordsOf($logger, 'teleframe.mtproto.socket.write_failed');
        $this->assertCount(1, $failed);
        $this->assertSame('error', $failed[0]['level']);
        $this->assertSame(0, $failed[0]['context']['offset']);
        $this->assertSame(1, $failed[0]['context']['length']);

        fclose($a);
    }

    /**
     * @return array<string, mixed>
     */
    private static function nearestDc(): array
    {
        return ['_' => 'nearestDc', 'country' => 'DE', 'this_dc' => 2, 'nearest_dc' => 2];
    }

    /**
     * @return list<array{level: string, message: string, context: array<string, mixed>}>
     */
    private function recordsOf(ArrayLogger $logger, string $message): array
    {
        return array_values(array_filter(
            $logger->records,
            static fn (array $record): bool => $record['message'] === $message
        ));
    }

    /**
     * @return array{0: resource, 1: resource}
     */
    private function socketPair(): array
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $this->assertNotFalse($pair);
        stream_set_timeout($pair[0], 5);
        stream_set_timeout($pair[1], 5);

        return $pair;
    }

    private function seed($serverSock, string $authKey, string $payload): void
    {
        fwrite($serverSock, FrameCodec::wrapAbridgedPayload(PacketCodec::encryptPacket(
            payload: $payload,
            authKey: $authKey,
            sessionId: self::SEED_SESSION_ID,
            serverSalt: 0x4242,
            seqNo: 1,
            toServer: false
        )));
    }

    /**
     * @return array{server_salt: int, session_id: int, message_id: int, seq_no: int, payload: string}
     */
    private function decryptRequest($serverSock, string $authKey): array
    {
        return PacketCodec::decryptPacket(
            FrameCodec::receiveAbridgedMessage($serverSock),
            $authKey,
            fromServer: false
        );
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function cannedResult(array $result, ?int $reqMsgId = null): string
    {
        return TLEncoder::encodeObject('rpc_result', [
            'req_msg_id' => $reqMsgId ?? $this->pinnedMessageIdBase() + 4, // first id an id-pinned conn sends
            'result' => $result,
        ]);
    }

    private function pinnedMessageIdBase(): int
    {
        return ((time() + 290) << 32) & ~3;
    }

    private function pinnedConn(EncryptedConnection $conn): EncryptedConnection
    {
        (new \ReflectionProperty(EncryptedConnection::class, 'sessionId'))->setValue($conn, self::SEED_SESSION_ID);

        return $conn;
    }

    private function idPinnedConn(EncryptedConnection $conn): EncryptedConnection
    {
        (new \ReflectionProperty(EncryptedConnection::class, 'lastMessageId'))->setValue($conn, $this->pinnedMessageIdBase());

        return $conn;
    }

    private function setConn(Client $client, EncryptedConnection $conn): void
    {
        (new \ReflectionProperty(Client::class, 'conn'))->setValue($client, $conn);
    }

    private function connOf(Client $client): mixed
    {
        return (new \ReflectionProperty(Client::class, 'conn'))->getValue($client);
    }
}

/**
 * Deterministic ping_id seam: pongs must be pre-seeded into the socket before
 * the blocking ping() runs, so the id it will send has to be known up front.
 */
class SeededPingIdLoggingConnection extends EncryptedConnection
{
    public static int $pingId = 1;

    protected function newPingId(): int
    {
        return self::$pingId;
    }
}
