<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\Wire;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\StreamSocket;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FrameCodecTest extends TestCase
{
    public function test_wrap_payload_uses_little_endian_length_prefix(): void
    {
        $wrapped = FrameCodec::wrapPayload('ABCD');
        $this->assertSame(4, unpack('V', substr($wrapped, 0, 4))[1]);
        $this->assertSame('ABCD', substr($wrapped, 4));
    }

    public function test_frame_round_trip_over_loopback(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $addr = stream_socket_get_name($server, false);
        [$host, $port] = explode(':', $addr);

        $client = StreamSocket::createConnection($host, (int) $port, timeout: 5.0);
        $accepted = stream_socket_accept($server, 5.0);

        FrameCodec::writeInit($client);
        FrameCodec::sendMessage($client, 'hello-transport');
        // server side: read 1 init byte, one length, echo the payload back
        fread($accepted, 1);
        $len = unpack('V', StreamSocket::readExact($accepted, 4))[1];
        $payload = StreamSocket::readExact($accepted, $len);
        FrameCodec::sendMessage($accepted, $payload);

        $this->assertSame('hello-transport', FrameCodec::receiveMessage($client));
        fclose($client);
        fclose($accepted);
        fclose($server);
    }

    public function test_write_init_emits_wire_init_send_event(): void
    {
        $logger = new ArrayLogger;
        [$clientSock, $serverSock] = $this->socketPair();

        FrameCodec::writeInit($clientSock, $logger);

        $init = $this->recordsOf($logger, 'teleframe.mtproto.wire.init.send');
        $this->assertCount(1, $init);
        $this->assertSame('debug', $init[0]['level']);
        $this->assertSame(1, $init[0]['context']['bytes']);

        fclose($clientSock);
        fclose($serverSock);
    }

    public function test_abridged_round_trip_emits_frame_send_and_receive_events(): void
    {
        [$clientSock, $serverSock] = $this->socketPair();
        $clientLogger = new ArrayLogger;
        $serverLogger = new ArrayLogger;
        $payload = 'abridged-payload';

        FrameCodec::sendAbridgedMessage($clientSock, $payload, $clientLogger);

        $send = $this->recordsOf($clientLogger, 'teleframe.mtproto.wire.frame.send');
        $this->assertCount(1, $send);
        $this->assertSame('debug', $send[0]['level']);
        $this->assertSame(strlen($payload), $send[0]['context']['length']);
        $this->assertSame(strlen($payload) + 1, $send[0]['context']['framed']);

        $this->assertSame($payload, FrameCodec::receiveAbridgedMessage($serverSock, $serverLogger));

        $receive = $this->recordsOf($serverLogger, 'teleframe.mtproto.wire.frame.receive');
        $this->assertCount(1, $receive);
        $this->assertSame('debug', $receive[0]['level']);
        $this->assertSame(strlen($payload), $receive[0]['context']['length']);

        fclose($clientSock);
        fclose($serverSock);
    }

    public function test_invalid_receive_length_emits_frame_invalid_event(): void
    {
        [$clientSock, $serverSock] = $this->socketPair();
        $logger = new ArrayLogger;

        fwrite($clientSock, "\x00"); // len4 = 0 -> len 0, out of range

        try {
            FrameCodec::receiveAbridgedMessage($serverSock, $logger);
            $this->fail('zero-length frame must throw');
        } catch (RuntimeException) {
            // expected: logging never swallows
        }

        $invalid = $this->recordsOf($logger, 'teleframe.mtproto.wire.frame.invalid');
        $this->assertCount(1, $invalid);
        $this->assertSame('error', $invalid[0]['level']);
        $this->assertSame(0, $invalid[0]['context']['length']);
        $this->assertGreaterThan(0, $invalid[0]['context']['max']);

        fclose($clientSock);
        fclose($serverSock);
    }

    public function test_odd_length_abridged_payload_emits_frame_malformed_event(): void
    {
        $logger = new ArrayLogger;
        $socket = fopen('php://temp', 'r+');
        $this->assertNotFalse($socket);

        try {
            FrameCodec::sendAbridgedMessage($socket, 'odd', $logger); // 3 bytes, not a multiple of 4
            $this->fail('odd-length abridged payload must throw');
        } catch (\InvalidArgumentException) {
            // expected: logging never swallows
        }

        $malformed = $this->recordsOf($logger, 'teleframe.mtproto.wire.frame.malformed');
        $this->assertCount(1, $malformed);
        $this->assertSame('error', $malformed[0]['level']);
        $this->assertSame(3, $malformed[0]['context']['length']);

        $send = $this->recordsOf($logger, 'teleframe.mtproto.wire.frame.send');
        $this->assertCount(0, $send, 'no send event for a frame that was never written');

        fclose($socket);
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
