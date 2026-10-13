<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\Wire;

use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcErrorException;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PacketCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;

/**
 * receiveBatchResults() collects EVERY per-item rpc_error but throws only the
 * first one, so errors 2..N used to leave no trace whatsoever — no log, no
 * return value, no exception. A batch with two failing items reported exactly
 * one, and the caller had no way to know the second failure happened.
 *
 * These tests pin both halves of the fix:
 *  - the throw semantics are UNCHANGED (still the first error, still typed,
 *    still a hard batch failure), and
 *  - the remaining errors are now observable through the class's own PSR-3
 *    logger seam, carrying method + code + message for each.
 *
 * The second error in the primary test is a MIGRATE error on purpose:
 * DcMigrationException extends TelegramException rather than RpcErrorException,
 * so a naive instanceof read at the throw site would lose its method and code.
 * Capturing the wire facts at the source is what makes this case work.
 */
class BatchSuppressedRpcErrorLoggingTest extends TestCase
{
    private const LOG_CHANNEL = 'teleframe.mtproto.connection.batch.rpc_error';

    /** @return array{0: resource, 1: resource} */
    private function socketPair(): array
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $this->assertNotFalse($pair);
        stream_set_timeout($pair[0], 5);
        stream_set_timeout($pair[1], 5);

        return $pair;
    }

    /**
     * Base for pinned-message-id tests: within PacketCodec's "not too far in
     * the future" window (id>>32 < time()+300) yet far above any real clock
     * candidate, so nextMessageId() deterministically yields base+4, +8, +12.
     */
    private function pinnedMessageIdBase(): int
    {
        return ((time() + 290) << 32) & ~3;
    }

    /** Pins the random session_id to the id the seed helper encrypts with. */
    private function pinnedConn(EncryptedConnection $conn): EncryptedConnection
    {
        (new \ReflectionProperty(EncryptedConnection::class, 'sessionId'))->setValue($conn, 0x5E5510A1);

        return $conn;
    }

    /** Pins lastMessageId so the batch's inner req_msg_ids are predictable. */
    private function idPinnedConn(EncryptedConnection $conn): EncryptedConnection
    {
        (new \ReflectionProperty(EncryptedConnection::class, 'lastMessageId'))->setValue($conn, $this->pinnedMessageIdBase());

        return $conn;
    }

    /**
     * Fake telegram pre-buffers one encrypted (server->client) framed response
     * so the single-threaded callBatch() write+read cannot deadlock.
     */
    private function seedFakeServerResponse($serverSock, string $authKey, string $payload): void
    {
        fwrite($serverSock, FrameCodec::wrapAbridgedPayload(PacketCodec::encryptPacket(
            payload: $payload,
            authKey: $authKey,
            sessionId: 0x5E5510A1,
            serverSalt: 0x4242,
            seqNo: 1,
            toServer: false
        )));
    }

    /**
     * Server -> client naked msg_container whose entries carry server-style
     * msg_ids (odd — from the server's clock, not ours).
     *
     * @param  list<array{0: int, 1: string}>  $entries  [msg_id, body] pairs
     */
    private static function nakedResultContainer(array $entries): string
    {
        $bin = pack('V', 0x73F1F8DC).pack('V', count($entries));
        foreach ($entries as $i => [$msgId, $body]) {
            $bin .= pack('P', $msgId)
                .pack('V', ($i + 1) * 2 - 1)
                .pack('V', strlen($body))
                .$body;
        }

        return $bin;
    }

    private static function rpcErrorResult(int $reqMsgId, int $code, string $message): string
    {
        return TLEncoder::encodeObject('rpc_result', [
            'req_msg_id' => $reqMsgId,
            'result' => ['_' => 'rpc_error', 'error_code' => $code, 'error_message' => $message],
        ]);
    }

    /** @return list<array<string, mixed>> the batch.rpc_error debug records */
    private static function suppressedRecords(ArrayLogger $logger): array
    {
        return array_values(array_filter(
            $logger->records,
            static fn (array $r): bool => $r['message'] === self::LOG_CHANNEL
        ));
    }

    /**
     * THE REGRESSION: two items fail. The first is thrown (existing, load-bearing
     * semantics) and the second is no longer silently dropped — it reaches the
     * logger with its method, code and message.
     */
    public function test_two_failing_items_throw_the_first_and_log_the_second(): void
    {
        [$clientSock, $serverSock] = $this->socketPair();
        $authKey = random_bytes(256);
        $logger = new ArrayLogger;
        $session = new SessionData(dcId: 2, authKey: $authKey);
        $conn = $this->idPinnedConn($this->pinnedConn(new EncryptedConnection($session, $clientSock, 0, $logger)));
        $pin = $this->pinnedMessageIdBase();

        $this->seedFakeServerResponse($serverSock, $authKey, self::nakedResultContainer([
            // first item fails with a documented rpc_error
            [$pin + 4, self::rpcErrorResult($pin + 4, 400, 'QUERY_TOO_LOUD')],
            // second item ALSO fails — this one used to vanish with no trace
            [$pin + 8, self::rpcErrorResult($pin + 8, 303, 'USER_MIGRATE_4')],
        ]));

        $thrown = null;
        try {
            $conn->callBatch([
                'first_fail' => TLEncoder::encodeObject('help.getConfig', []),
                'second_fail' => TLEncoder::encodeObject('help.getNearestDc', []),
            ]);
            $this->fail('expected TelegramException on batch rpc_error');
        } catch (TelegramException $e) {
            $thrown = $e;
        }

        // --- throw semantics UNCHANGED: still the FIRST error, still typed ---
        $this->assertInstanceOf(RpcErrorException::class, $thrown);
        $this->assertStringContainsString('QUERY_TOO_LOUD', $thrown->getMessage());
        $this->assertStringContainsString('help.getConfig', $thrown->getMessage());
        $this->assertSame(400, $thrown->getCode());
        $this->assertStringNotContainsString('USER_MIGRATE_4', $thrown->getMessage(), 'first error only is thrown');

        // --- the second error is now OBSERVABLE ---
        $records = self::suppressedRecords($logger);
        $this->assertCount(1, $records, 'exactly one suppressed error for a two-failure batch');

        $record = $records[0];
        $this->assertSame('debug', $record['level']);
        $this->assertSame(self::LOG_CHANNEL, $record['message']);

        $context = $record['context'];
        $this->assertSame('help.getNearestDc', $context['method'], 'method of the SECOND failing item');
        $this->assertSame(303, $context['code']);
        $this->assertSame('USER_MIGRATE_4', $context['message']);
        $this->assertSame(2, $context['suppressed_index'], 'position within the batch errors');
        $this->assertSame(2, $context['batch_error_count']);
        $this->assertSame(1, $context['thrown_index']);

        $conn->close();
        fclose($serverSock);
    }

    /**
     * Three failing items: errors 2 AND 3 are both surfaced, each with its own
     * method — and the resolved types differ (rpc_error / FLOOD_WAIT / MIGRATE),
     * proving the capture does not depend on the resolver's class hierarchy.
     */
    public function test_three_failing_items_log_every_error_after_the_first(): void
    {
        [$clientSock, $serverSock] = $this->socketPair();
        $authKey = random_bytes(256);
        $logger = new ArrayLogger;
        $session = new SessionData(dcId: 2, authKey: $authKey);
        $conn = $this->idPinnedConn($this->pinnedConn(new EncryptedConnection($session, $clientSock, 0, $logger)));
        $pin = $this->pinnedMessageIdBase();

        $this->seedFakeServerResponse($serverSock, $authKey, self::nakedResultContainer([
            [$pin + 4, self::rpcErrorResult($pin + 4, 400, 'QUERY_TOO_LOUD')],
            [$pin + 8, self::rpcErrorResult($pin + 8, 420, 'FLOOD_WAIT_17')],
            [$pin + 12, self::rpcErrorResult($pin + 12, 303, 'USER_MIGRATE_4')],
        ]));

        try {
            $conn->callBatch([
                'a' => TLEncoder::encodeObject('help.getConfig', []),
                'b' => TLEncoder::encodeObject('help.getNearestDc', []),
                'c' => TLEncoder::encodeObject('help.getConfig', []),
            ]);
            $this->fail('expected TelegramException on batch rpc_error');
        } catch (TelegramException $e) {
            $this->assertStringContainsString('QUERY_TOO_LOUD', $e->getMessage());
            $this->assertSame(400, $e->getCode());
        }

        $records = self::suppressedRecords($logger);
        $this->assertCount(2, $records, 'both non-first errors are surfaced');

        $this->assertSame('FLOOD_WAIT_17', $records[0]['context']['message']);
        $this->assertSame('help.getNearestDc', $records[0]['context']['method']);
        $this->assertSame(420, $records[0]['context']['code']);
        $this->assertSame(2, $records[0]['context']['suppressed_index']);
        $this->assertSame(3, $records[0]['context']['batch_error_count']);

        $this->assertSame('USER_MIGRATE_4', $records[1]['context']['message']);
        $this->assertSame('help.getConfig', $records[1]['context']['method']);
        $this->assertSame(303, $records[1]['context']['code']);
        $this->assertSame(3, $records[1]['context']['suppressed_index']);
        $this->assertSame(3, $records[1]['context']['batch_error_count']);

        $conn->close();
        fclose($serverSock);
    }

    /**
     * NO BEHAVIOUR CHANGE for a single failing item: the exception is the same
     * error, and nothing extra is logged — there is no suppressed error to
     * report, so the fix is silent here.
     */
    public function test_single_failing_item_behaves_exactly_as_before(): void
    {
        [$clientSock, $serverSock] = $this->socketPair();
        $authKey = random_bytes(256);
        $logger = new ArrayLogger;
        $session = new SessionData(dcId: 2, authKey: $authKey);
        $conn = $this->idPinnedConn($this->pinnedConn(new EncryptedConnection($session, $clientSock, 0, $logger)));
        $pin = $this->pinnedMessageIdBase();

        $this->seedFakeServerResponse($serverSock, $authKey, self::nakedResultContainer([
            [$pin + 4, self::rpcErrorResult($pin + 4, 400, 'QUERY_TOO_LOUD')],
        ]));

        try {
            $conn->callBatch(['boom' => TLEncoder::encodeObject('help.getConfig', [])]);
            $this->fail('expected TelegramException on batch rpc_error');
        } catch (TelegramException $e) {
            $this->assertInstanceOf(RpcErrorException::class, $e);
            $this->assertStringContainsString('QUERY_TOO_LOUD', $e->getMessage());
            $this->assertStringContainsString('help.getConfig', $e->getMessage());
            $this->assertSame(400, $e->getCode());
        }

        $this->assertSame([], self::suppressedRecords($logger), 'a single failure logs no suppressed-error record');

        $conn->close();
        fclose($serverSock);
    }

    /**
     * A mixed batch — one failure among successes — must still throw (an
     * rpc_error is never smuggled through as a result), and the single error is
     * fully described by the exception alone, so nothing extra is logged.
     */
    public function test_mixed_batch_with_one_failure_still_throws_and_logs_nothing_extra(): void
    {
        [$clientSock, $serverSock] = $this->socketPair();
        $authKey = random_bytes(256);
        $logger = new ArrayLogger;
        $session = new SessionData(dcId: 2, authKey: $authKey);
        $conn = $this->idPinnedConn($this->pinnedConn(new EncryptedConnection($session, $clientSock, 0, $logger)));
        $pin = $this->pinnedMessageIdBase();

        $this->seedFakeServerResponse($serverSock, $authKey, self::nakedResultContainer([
            [$pin + 4, self::rpcErrorResult($pin + 4, 400, 'QUERY_TOO_LOUD')],
            [$pin + 8, TLEncoder::encodeObject('rpc_result', [
                'req_msg_id' => $pin + 8,
                'result' => ['_' => 'nearestDc', 'country' => 'DE', 'this_dc' => 2, 'nearest_dc' => 2],
            ])],
        ]));

        try {
            $conn->callBatch([
                'boom' => TLEncoder::encodeObject('help.getConfig', []),
                'ok' => TLEncoder::encodeObject('help.getNearestDc', []),
            ]);
            $this->fail('expected TelegramException on batch rpc_error');
        } catch (TelegramException $e) {
            $this->assertSame(400, $e->getCode());
        }

        $this->assertSame([], self::suppressedRecords($logger));

        $conn->close();
        fclose($serverSock);
    }
}
