<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\Wire;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\PlainConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class PlainConnectionTest extends TestCase
{
    /**
     * Official sample handshake (core.telegram.org/mtproto/samples-auth_key),
     * first request on the wire (transport header stripped):
     * 0000000000000000 (auth_key_id) 78F404006170466A (message_id)
     * 14000000 (length=20) F18E7EBE (req_pq_multi) nonce.
     */
    public function test_build_plain_envelope_matches_official_transcript_request(): void
    {
        $nonce = hex2bin('51A1143FC7A3666BE4BE54D6890A02DC');
        $payload = TLEncoder::encodeObject('req_pq_multi', ['nonce' => $nonce]);
        $transcriptMsgId = unpack('P', hex2bin('78F404006170466A'))[1];

        $envelope = PlainConnection::buildPlainEnvelope($payload, (int) $transcriptMsgId);

        $this->assertSame(
            '0000000000000000'
                .'78f404006170466a'
                .'14000000'
                .'f18e7ebe'
                .'51a1143fc7a3666be4be54d6890a02dc',
            bin2hex($envelope)
        );
    }

    public function test_parse_plain_envelope_returns_body_and_rejects_malformed_frames(): void
    {
        $body = TLEncoder::encodeObject('req_pq_multi', ['nonce' => str_repeat("\x42", 16)]);
        $frame = PlainConnection::buildPlainEnvelope($body, 0x6A4670610004F478);
        $this->assertSame($body, PlainConnection::parsePlainEnvelope($frame));

        $badKeyId = "\x01".str_repeat("\x00", 7).substr($frame, 8);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('auth_key_id');
        PlainConnection::parsePlainEnvelope($badKeyId);
    }

    public function test_parse_plain_envelope_rejects_length_mismatch_and_oversize(): void
    {
        $body = 'abcd';
        $frame = PlainConnection::buildPlainEnvelope($body, 0x6A4670610004F478);

        try {
            PlainConnection::parsePlainEnvelope(substr($frame, 0, 21)); // body shorter than length
            $this->fail('expected RuntimeException for length mismatch');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('length', $e->getMessage());
        }

        try {
            $oversize = str_repeat("\x00", 8).pack('P', 1).pack('V', 3 * 1024 * 1024).'abcd';
            PlainConnection::parsePlainEnvelope($oversize);
            $this->fail('expected RuntimeException for oversized length');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('length', $e->getMessage());
        }
    }

    /**
     * Deterministic in-memory transport: the fake server pre-seeds its
     * envelope-wrapped response into the socket-pair buffer, so the
     * single-threaded request() write+read cannot deadlock; the framed
     * request is then read back from the server side and unwrapped.
     */
    public function test_request_wraps_and_unwraps_plain_envelope_over_socket_pair(): void
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $this->assertNotFalse($pair);
        [$clientSock, $serverSock] = $pair;

        $conn = new PlainConnection($clientSock);

        // fake telegram: pre-buffer one envelope-wrapped response
        fwrite($serverSock, FrameCodec::wrapAbridgedPayload(
            PlainConnection::buildPlainEnvelope('handshake-payload!!!', 0x6A4670610004F478)
        ));

        $response = $conn->request('handshake-payload!!!');
        $this->assertSame('handshake-payload!!!', $response);

        // the request itself must have arrived framed and envelope-wrapped
        $this->assertSame('handshake-payload!!!', PlainConnection::parsePlainEnvelope(
            FrameCodec::receiveAbridgedMessage($serverSock)
        ));

        $conn->close();
        fclose($serverSock);
    }

    public function test_connect_writes_intermediate_init_byte_over_loopback(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertNotFalse($server);
        $addr = stream_socket_get_name($server, false);
        [$host, $port] = explode(':', $addr);

        $conn = PlainConnection::connect($host, (int) $port, timeout: 5.0);

        $client = stream_socket_accept($server, 5.0);
        $this->assertNotFalse($client);
        $this->assertSame("\xef", fread($client, 1)); // 0xef init byte

        $conn->close();
        fclose($client);
        fclose($server);
    }

    public function test_close_is_idempotent(): void
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $this->assertNotFalse($pair);
        [$clientSock, $serverSock] = $pair;

        $conn = new PlainConnection($clientSock);
        $conn->close();
        $this->assertFalse(is_resource($conn->socket));
        $this->expectOutputString(''); // set before the guarded call: second close must emit nothing
        $conn->close();

        fclose($serverSock);
        $this->addToAssertionCount(1); // second close did not throw
    }

    public function test_plain_connection_defaults_to_null_logger(): void
    {
        $conn = new PlainConnection(fopen('php://memory', 'r+'));
        $prop = new \ReflectionProperty(PlainConnection::class, 'logger');
        $this->assertInstanceOf(NullLogger::class, $prop->getValue($conn));
    }

    public function test_set_logger_is_fluent(): void
    {
        $conn = new PlainConnection(fopen('php://memory', 'r+'));
        $logger = new ArrayLogger;
        $this->assertSame($conn, $conn->setLogger($logger));
        $prop = new \ReflectionProperty(PlainConnection::class, 'logger');
        $this->assertSame($logger, $prop->getValue($conn));
    }

    /**
     * The full plain-handshake lifecycle over a socket pair: request() emits
     * request-sent and response-received events with structured byte context
     * (metadata only — the payload itself is never logged).
     */
    public function test_request_logs_plain_handshake_events_over_socket_pair(): void
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $this->assertNotFalse($pair);
        [$clientSock, $serverSock] = $pair;

        $logger = new ArrayLogger;
        $conn = new PlainConnection($clientSock, $logger);

        $reqPayload = str_repeat('R', 16); // abridged frame: envelope payload must be a multiple of 4
        $resPayload = str_repeat('S', 16);
        fwrite($serverSock, FrameCodec::wrapAbridgedPayload(
            PlainConnection::buildPlainEnvelope($resPayload, 0x6A4670610004F478)
        ));

        $this->assertSame($resPayload, $conn->request($reqPayload));
        $conn->close();

        $sent = $this->recordsOf($logger, 'teleframe.mtproto.plain.init.request.sent');
        $this->assertCount(1, $sent);
        $this->assertSame('debug', $sent[0]['level']);
        $this->assertSame(strlen($reqPayload), $sent[0]['context']['bytes']);

        $received = $this->recordsOf($logger, 'teleframe.mtproto.plain.init.response.received');
        $this->assertCount(1, $received);
        $this->assertSame('debug', $received[0]['level']);
        $this->assertSame(strlen($resPayload), $received[0]['context']['bytes']);

        $close = $this->recordsOf($logger, 'teleframe.mtproto.plain.close');
        $this->assertCount(1, $close);
        $this->assertSame('debug', $close[0]['level']);

        // instance-level events never fire: they belong to the connect() seam
        $this->assertCount(0, $this->recordsOf($logger, 'teleframe.mtproto.plain.connect.start'));

        fclose($serverSock);
    }

    public function test_connect_logs_lifecycle_events_over_loopback(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertNotFalse($server);
        $addr = stream_socket_get_name($server, false);
        [$host, $port] = explode(':', $addr);

        $logger = new ArrayLogger;
        $conn = PlainConnection::connect($host, (int) $port, timeout: 5.0, logger: $logger);

        $client = stream_socket_accept($server, 5.0);
        $this->assertNotFalse($client);
        fread($client, 1); // drain the 0xef init byte

        $start = $this->recordsOf($logger, 'teleframe.mtproto.plain.connect.start');
        $this->assertCount(1, $start);
        $this->assertSame('debug', $start[0]['level']);
        $this->assertSame($host, $start[0]['context']['host']);
        $this->assertSame((int) $port, $start[0]['context']['port']);
        $this->assertSame(5.0, $start[0]['context']['timeout']);

        $open = $this->recordsOf($logger, 'teleframe.mtproto.plain.connect.open');
        $this->assertCount(1, $open);
        $this->assertSame('debug', $open[0]['level']);

        $init = $this->recordsOf($logger, 'teleframe.mtproto.plain.init.byte.sent');
        $this->assertCount(1, $init);
        $this->assertSame('debug', $init[0]['level']);

        $conn->close();
        fclose($client);
        fclose($server);
    }

    public function test_connect_failure_logs_handshake_failed_and_rethrows(): void
    {
        $logger = new ArrayLogger;

        try {
            PlainConnection::connect('127.0.0.1', 1, timeout: 2.0, logger: $logger); // nothing listens here
            $this->fail('unreachable endpoint must throw');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('127.0.0.1:1', $e->getMessage());
        }

        $failed = $this->recordsOf($logger, 'teleframe.mtproto.plain.handshake.failed');
        $this->assertCount(1, $failed);
        $this->assertSame('error', $failed[0]['level']);
        $this->assertSame('127.0.0.1', $failed[0]['context']['host']);
        $this->assertSame(1, $failed[0]['context']['port']);
        $this->assertArrayHasKey('error', $failed[0]['context']);
        $this->assertArrayHasKey('exception', $failed[0]['context']);
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
}
