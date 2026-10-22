<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Connection;

use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcExceptionResolver;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PacketCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSerializer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\StreamSocket;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Encrypted MTProto 2.0 RPC connection (single in-flight query; sufficient for CLI/short jobs).
 *
 * First call is wrapped in invokeWithLayer(layer, initConnection(..., query)); later calls
 * send the bare constructor. Handles bad_server_salt (resend once with the fresh salt),
 * gzip_packed results, rpc_error -> TelegramException, and skips transient push messages.
 * call() demultiplexes strictly by req_msg_id: rpc_results answering OTHER requests
 * (e.g. batch siblings left unread after a mid-batch rpc_error throw) are skipped as
 * strays, never served as this call's answer. callBatch() packs N independent prebuilt
 * bodies into one naked msg_container (single round-trip) and demultiplexes the
 * per-request rpc_results by inner msg_id.
 *
 * Logging is optional and silent by default: a NullLogger is used when none is given.
 * Metadata only — session strings, auth keys and message payloads are NEVER logged.
 */
class EncryptedConnection
{
    public const LAYER = 227;

    /**
     * Transient push messages skipped while waiting for the rpc_result. Both
     * constructors are registered in TLRegistry, so their ids are looked up
     * there (msgs_ack msg_ids:Vector long = MsgsAck -> 0x62d6b459,
     * new_session_created first_msg_id:long unique_id:long server_salt:long
     * = NewSession -> 0x9ec20908) instead of being duplicated here.
     */
    private const MAX_TRANSIENT_MESSAGES = 3;

    /**
     * Generous cap on server-pushed update containers consumed while awaiting
     * an rpc_result. Unlike msgs_ack these can arrive in bursts (a busy
     * account flushing its update queue), so this is far larger than the
     * transient cap — it only exists to stop an adversarial/unbounded stream.
     */
    private const MAX_OUT_OF_BAND_UPDATES = 10_000;

    /**
     * Poison cap for stray rpc_results in call() and callBatch(): foreign
     * results (req_msg_id not ours) are skipped — at most this many, then the
     * stream is declared broken (poison-cap style, mirroring the batch frame
     * cap). One in-flight request can legitimately be preceded only by
     * leftovers of earlier batches/calls, which are few; 8 is far beyond any
     * honest sibling count.
     */
    protected const MAX_STRAY_RESULTS = 8;

    /**
     * Docs limits for one msg_container (core.telegram.org/mtproto/service_messages):
     * at most 1020 messages and 32 KB of container payload.
     */
    public const MAX_BATCH_MESSAGES = 1020;

    public const MAX_BATCH_CONTAINER_BYTES = 32768;

    /**
     * Bodies of the in-flight callBatch, key => bytes — kept per connection
     * (single in-flight batch, same contract as the single-call path) so a
     * batch rpc_error can name the failing key's method for the resolver.
     *
     * @var array<string, string>|null
     */
    protected ?array $batchRequestBodies = null;

    /** @var resource|null */
    protected $socket;

    protected int $serverSalt = 0;

    protected int $sessionId;

    protected bool $inited = false;

    protected int $lastMessageId = 0;

    protected int $contentCounter = -1;

    protected float $lastActivity = 0.0;

    protected LoggerInterface $logger;

    /**
     * @param  resource|null  $socket
     */
    final public function __construct(protected SessionData $session, $socket = null, protected int $apiId = 0, ?LoggerInterface $logger = null)
    {
        $this->socket = $socket;
        $this->logger = $logger ?? new NullLogger;
        $this->sessionId = (int) unpack('P', random_bytes(8))[1];
        $this->serverSalt = $session->serverSalt; // persisted salt survives reconnects
    }

    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param  array{type?: string, host?: string, port?: int, username?: string, password?: string}|null  $proxy
     * @param  bool  $tls  When true the TARGET stream is upgraded to TLS after the
     *                     (tunnelled) connect and verified against
     *                     StreamSocket::DEFAULT_TLS_PEER_NAME, never the proxy.
     */
    public static function connect(
        SessionData $session,
        string $host,
        int $port = 443,
        float $timeout = 10.0,
        ?LoggerInterface $logger = null,
        ?array $proxy = null,
        bool $tls = false,
        ?string $peerName = null,
        ?string $cafile = null
    ): static {
        try {
            $socket = StreamSocket::createConnection(
                host: $host,
                port: $port,
                proxy: $proxy,
                timeout: $timeout,
                tls: $tls,
                logger: $logger,
                cafile: $cafile,
                peerName: $peerName
            );
            FrameCodec::writeInit($socket);
        } catch (RuntimeException $e) {
            $logger?->error('teleframe.mtproto.connection.failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $e;
        }

        $logger?->debug('teleframe.mtproto.connection.connect', [
            'host' => $host,
            'port' => $port,
            'proxied' => $proxy !== null,
            'tls' => $tls,
        ]);

        return new static($session, $socket, logger: $logger);
    }

    /**
     * invokeWithLayer cannot go through TLEncoder (generic {X:Type} field), so it is
     * framed manually: id || layer:int || initConnection object.
     */
    public static function buildFirstQueryBody(int $layer, array $initConnectionArgs): string
    {
        $init = TLEncoder::encodeObject('initConnection', $initConnectionArgs);

        return TLSerializer::packInt(TLRegistry::id('invokeWithLayer'))
            .TLSerializer::packInt($layer)
            .$init;
    }

    /**
     * rpc_result's `result` arrives already decoded (Object field). If it is a
     * gzip_packed wrapper, inflate and decode the inner object; otherwise return as-is.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function unwrapResultIfGzipped(array $result): array
    {
        if (($result['_'] ?? '') !== 'gzip_packed') {
            return $result;
        }
        $inflated = gzdecode($result['packed_data']);
        if ($inflated === false) {
            throw new RuntimeException('EncryptedConnection: gzdecode failed');
        }
        $offset = 0;

        return TLDecoder::decodeObject($inflated, $offset);
    }

    /**
     * MTProto keepalive: ping_delay_disconnect with a fresh ping_id. The
     * server answers pong (same ping_id) and guarantees not to close the
     * connection for disconnect_delay seconds (MadelineProto PingLoop parity).
     * The returned pong must echo the sent ping_id — a mismatch (or a missing
     * ping_id) is a protocol violation and throws.
     *
     * @return array<string, mixed> the pong result (['_' => 'pong', 'msg_id' => ..., 'ping_id' => ...])
     */
    public function ping(int $disconnectDelay = 50): array
    {
        $pingId = $this->newPingId();
        $pong = $this->call('ping_delay_disconnect', [
            'ping_id' => $pingId,
            'disconnect_delay' => $disconnectDelay,
        ]);
        if (! hash_equals((string) $pingId, (string) ($pong['ping_id'] ?? ''))) {
            throw new RuntimeException(sprintf(
                'EncryptedConnection: pong ping_id mismatch (sent %d, received %s)',
                $pingId,
                isset($pong['ping_id']) ? var_export($pong['ping_id'], true) : '(missing)'
            ));
        }

        return $pong;
    }

    /**
     * Fresh random ping_id for a keepalive ping (overridable seam for tests:
     * pre-seeded pongs must know the id before the blocking call runs).
     */
    protected function newPingId(): int
    {
        return random_int(0, PHP_INT_MAX);
    }

    /**
     * Seconds since the last successful send/receive on this connection
     * (0.0 while no traffic has happened yet).
     */
    public function idleSeconds(): float
    {
        return $this->lastActivity === 0.0 ? 0.0 : max(0.0, microtime(true) - $this->lastActivity);
    }

    /**
     * Current server salt (last one seen from bad_server_salt / new_session_created),
     * also mirrored into the SessionData for persistence.
     */
    public function getServerSalt(): int
    {
        return $this->serverSalt;
    }

    /**
     * @return array<string, mixed> decoded result of the RPC call
     *
     * @throws TelegramException on rpc_error
     * @throws RuntimeException on transport/protocol failures
     */
    public function call(string $constructor, array $args = []): array
    {
        if (! is_resource($this->socket)) {
            throw new RuntimeException('EncryptedConnection: not connected');
        }

        $start = microtime(true);
        $this->logger->debug('teleframe.mtproto.connection.call', [
            'method' => $constructor,
            'phase' => 'start',
        ]);

        try {
            [$result, $bytes] = $this->runCall($constructor, $args);
        } catch (TelegramException $e) {
            // rpc_error / typed server outcomes: a business result, NOT a connection
            // failure — the connection stays usable, so nothing is logged as failed.
            throw $e;
        } catch (RuntimeException $e) {
            $this->logger->error('teleframe.mtproto.connection.failed', [
                'method' => $constructor,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $e;
        }

        $this->logger->debug('teleframe.mtproto.connection.call', [
            'method' => $constructor,
            'messages' => 1,
            'bytes' => $bytes,
            'ms' => (int) ((microtime(true) - $start) * 1000),
            'phase' => 'done',
        ]);

        return $result;
    }

    /**
     * The actual invokeWithLayer/bare constructor round-trip for ONE RPC call,
     * returned as [decoded result, bytes of the last sent packet] so the public
     * seam can time and size the call without re-running the protocol logic.
     *
     * @return array{0: array<string, mixed>, 1: int}
     */
    private function runCall(string $constructor, array $args): array
    {
        $body = $this->inited
            ? TLEncoder::encodeObject($constructor, $args)
            : self::buildFirstQueryBody(self::LAYER, [
                'api_id' => $this->apiId,
                'device_model' => 'Teleframe',
                'system_version' => PHP_OS.' PHP '.PHP_VERSION,
                'app_version' => '1.0.0',
                'system_lang_code' => 'en',
                'lang_pack' => '',
                'lang_code' => 'en',
                'query' => array_merge(['_' => $constructor], $args),
            ]);

        $maxAttempts = 2; // original + one bad_server_salt resend
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $this->inited = true;
            $msgId = $this->nextMessageId(); // get-then-use: the id below must be the one demuxed against
            $packet = PacketCodec::encryptPacket(
                payload: $body,
                authKey: $this->session->authKey,
                sessionId: $this->sessionId,
                serverSalt: $this->serverSalt,
                seqNo: $this->nextContentSeqNo(), // content messages require an odd, increasing seq_no
                serverTimeDelta: $this->session->serverTimeDelta,
                messageId: $msgId
            );
            $bytes = strlen($packet);
            FrameCodec::sendAbridgedMessage($this->socket, $packet);
            $this->touchActivity();

            $result = $this->receiveDecodedResponse($msgId);

            if (($result['_'] ?? '') === 'bad_server_salt') {
                $this->refreshServerSalt((int) $result['new_server_salt']);

                continue; // resend with the fresh salt and a fresh msg_id/seq_no
            }

            if (($result['_'] ?? '') === 'bad_msg_notification') {
                throw new RuntimeException(sprintf(
                    'EncryptedConnection: bad_msg_notification code %d for msg_id %s (seq %d)',
                    (int) ($result['error_code'] ?? 0),
                    (string) ($result['bad_msg_id'] ?? '?'),
                    (int) ($result['bad_msg_seqno'] ?? -1)
                ));
            }

            if (($result['_'] ?? '') !== 'rpc_result') {
                // The server answers ping_delay_disconnect with a BARE pong
                // service message (not an rpc_result) — terminal reply for pings.
                if (($result['_'] ?? '') === 'pong' && $constructor === 'ping_delay_disconnect') {
                    return [$result, $bytes];
                }
                throw new RuntimeException(sprintf(
                    "EncryptedConnection: unexpected response '%s'",
                    $result['_'] ?? '(no constructor)'
                ));
            }

            $inner = self::unwrapResultIfGzipped((array) $result['result']);
            if (($inner['_'] ?? '') === 'rpc_error') {
                throw RpcExceptionResolver::resolve(
                    (string) ($inner['error_message'] ?? 'UNKNOWN'),
                    (int) ($inner['error_code'] ?? 0),
                    $constructor
                );
            }

            return [$inner, $bytes];
        }
        throw new RuntimeException('EncryptedConnection: exhausted retries after bad_server_salt');
    }

    /**
     * Pure pre-flight bounds check for a batch: at most MAX_BATCH_MESSAGES
     * messages and MAX_BATCH_CONTAINER_BYTES of container payload (the exact
     * wire size: 8-byte container header + 16-byte per-message framing +
     * body bytes). Consumes no message ids/seqnos and does no I/O, so
     * callers can validate BEFORE touching a cached connection — bounds
     * failures are caller errors, not dead connections.
     *
     * @param  array<string, string>  $bodies  key => prebuilt request body bytes
     */
    public static function assertBatchWithinBounds(array $bodies): void
    {
        if (count($bodies) > self::MAX_BATCH_MESSAGES) {
            throw new RuntimeException(sprintf(
                'EncryptedConnection: batch of %d messages exceeds the container limit of %d',
                count($bodies),
                self::MAX_BATCH_MESSAGES
            ));
        }
        $bytes = 8 + 16 * count($bodies) + array_sum(array_map('strlen', $bodies));
        if ($bytes > self::MAX_BATCH_CONTAINER_BYTES) {
            throw new RuntimeException(sprintf(
                'EncryptedConnection: batch container of %d bytes exceeds the %d-byte limit',
                $bytes,
                self::MAX_BATCH_CONTAINER_BYTES
            ));
        }
    }

    /**
     * Sends N independent prebuilt request bodies inside ONE naked msg_container
     * (one encrypted packet, one round-trip) and demultiplexes the per-request
     * rpc_results by the container's inner msg_ids. Inner ids/seqnos come from
     * the same nextMessageId()/nextContentSeqNo() generators as single calls,
     * so call() and callBatch() interoperate on one connection.
     *
     * @param  array<string, string>  $bodies  key => prebuilt request body bytes
     * @return array<string, array<string, mixed>> key => decoded result, input order preserved
     *
     * @throws TelegramException on rpc_error (with the failing key's method context)
     * @throws RuntimeException on bounds violations, transport/protocol failures, poison streams
     */
    public function callBatch(array $bodies): array
    {
        if ($bodies === []) {
            return [];
        }
        self::assertBatchWithinBounds($bodies);
        if (! is_resource($this->socket)) {
            throw new RuntimeException('EncryptedConnection: not connected');
        }

        $start = microtime(true);
        $this->logger->debug('teleframe.mtproto.connection.batch', [
            'count' => count($bodies),
            'phase' => 'start',
        ]);

        $bytes = 0;
        try {
            $maxAttempts = 2; // original + one bad_server_salt resend
            $this->batchRequestBodies = $bodies;
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                $innerIds = [];
                $container = $this->encodeBatchContainer($bodies, $innerIds);

                $packet = PacketCodec::encryptPacket(
                    payload: $container,
                    authKey: $this->session->authKey,
                    sessionId: $this->sessionId,
                    serverSalt: $this->serverSalt,
                    seqNo: $this->nextContainerSeqNo(), // container = non-content-related: EVEN seq_no, counter not consumed
                    serverTimeDelta: $this->session->serverTimeDelta,
                    messageId: $this->nextMessageId() // outer id allocated last: strictly greater than every inner id
                );
                $bytes = strlen($packet);
                FrameCodec::sendAbridgedMessage($this->socket, $packet);
                $this->touchActivity();

                $results = $this->receiveBatchResults($innerIds);
                if ($results === null) {
                    continue; // bad_server_salt: salt refreshed, whole batch resent with fresh ids
                }

                $ordered = [];
                foreach (array_keys($bodies) as $key) {
                    $ordered[$key] = $results[$key];
                }

                $this->logger->debug('teleframe.mtproto.connection.batch', [
                    'count' => count($bodies),
                    'bytes' => $bytes,
                    'ms' => (int) ((microtime(true) - $start) * 1000),
                    'phase' => 'done',
                ]);

                return $ordered;
            }
            throw new RuntimeException('EncryptedConnection: exhausted retries after bad_server_salt');
        } catch (TelegramException $e) {
            // rpc_error: business outcome, the connection stays usable.
            throw $e;
        } catch (RuntimeException $e) {
            $this->logger->error('teleframe.mtproto.connection.failed', [
                'count' => count($bodies),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $e;
        } finally {
            // No batch is in flight anymore, success or failure — the field's
            // contract is "bodies of the in-flight callBatch" only.
            $this->batchRequestBodies = null;
        }
    }

    /**
     * Naked msg_container encoding — the write side of parseNakedContainer():
     * id:int32 + count:int32 + per message {msg_id:int64, seqno:int32,
     * body_len:int32, body}. Inner msg_ids are ≡ 0 (mod 4), strictly
     * increasing; every inner message is content (odd, increasing seq_no).
     *
     * @param  array<string, string>  $bodies
     * @param  array<int, string>  $innerIds  populated with inner msg_id => key
     */
    protected function encodeBatchContainer(array $bodies, array &$innerIds): string
    {
        $container = TLSerializer::packInt(0x73F1F8DC).TLSerializer::packInt(count($bodies));
        foreach ($bodies as $key => $body) {
            $msgId = $this->nextMessageId();
            $innerIds[$msgId] = $key;
            $container .= TLSerializer::packLong($msgId)
                .TLSerializer::packInt($this->nextContentSeqNo())
                .TLSerializer::packInt(strlen($body))
                .$body;
        }
        if (strlen($container) > self::MAX_BATCH_CONTAINER_BYTES) {
            throw new RuntimeException(sprintf(
                'EncryptedConnection: batch container of %d bytes exceeds the %d-byte limit',
                strlen($container),
                self::MAX_BATCH_CONTAINER_BYTES
            ));
        }

        return $container;
    }

    /**
     * Batch receive loop: reads frames until every pending inner msg_id has
     * its rpc_result. Unrelated pushes (msgs_ack, pong, bare
     * new_session_created, not-ours rpc_results, updateShort*) are skipped;
     * new_session_created refreshes the salt. Unknown req_msg_ids tally
     * against MAX_STRAY_RESULTS (call()-style skip-with-cap, never a
     * mid-drain throw). rpc_errors are collected and — only once EVERY
     * sibling has been drained — the first is rethrown as the batch outcome,
     * so no stale frame is ever left behind for the next call()/callBatch().
     * Returns null to signal bad_server_salt (salt refreshed — caller
     * resends). Poison protection: at most count(pending)*3 + 10 frames per
     * attempt.
     *
     * @param  array<int, string>  $pending  inner msg_id => key
     * @return array<string, array<string, mixed>>|null key => decoded result
     *
     * @throws TelegramException the first collected rpc_error, after the drain completes
     */
    protected function receiveBatchResults(array $pending): ?array
    {
        $results = [];
        $errors = [];
        $errorContexts = [];
        $strays = 0;
        $frameCap = count($pending) * 3 + 10;
        for ($framesRead = 0; $pending !== []; $framesRead++) {
            if ($framesRead >= $frameCap) {
                throw new RuntimeException(sprintf(
                    'EncryptedConnection: batch receive exceeded %d frames with %d result(s) still pending',
                    $frameCap,
                    count($pending)
                ));
            }

            $frame = FrameCodec::receiveAbridgedMessage($this->socket);
            $this->touchActivity();
            $this->assertNotTransportErrorFrame($frame);

            $payload = PacketCodec::decryptPacket($frame, $this->session->authKey, expectedSessionId: $this->sessionId)['payload'];
            $id = strlen($payload) >= 4 ? unpack('V', substr($payload, 0, 4))[1] : 0;

            if ($id === 0x73F1F8DC) {
                foreach (self::parseBareContainerMessages($payload) as $body) {
                    if ($this->absorbBatchBody($body, $pending, $results, $errors, $errorContexts, $strays) === 'bad_server_salt') {
                        return null;
                    }
                }

                continue;
            }

            $offset = 0;
            $decoded = TLDecoder::decodeObject($payload, $offset);
            $decoded = self::unwrapResultIfGzipped($decoded);
            if ($this->absorbBatchBody($decoded, $pending, $results, $errors, $errorContexts, $strays) === 'bad_server_salt') {
                return null;
            }
        }

        if ($errors !== []) {
            // Every sibling's rpc_result is drained by now — surface the
            // first rpc_error as the batch outcome, with the same typed
            // TelegramException (FLOOD_WAIT / MIGRATE) and failing-key method
            // context the pre-drain throw carried, minus the stranding of
            // unread frames. Connection stays usable.
            $this->logSuppressedBatchErrors($errors, $errorContexts);

            throw $errors[0];
        }

        return $results;
    }

    /**
     * Splits a received naked msg_container payload into its decoded inner
     * bodies WITHOUT any skip/transient semantics — the batch demux loop
     * (absorbBatchBody) makes its own per-body decisions, including salts.
     *
     * @return list<array<string, mixed>>
     */
    protected static function parseBareContainerMessages(string $payload): array
    {
        $messages = [];
        $offset = 8; // id + count
        $count = unpack('V', substr($payload, 4, 4))[1];
        for ($i = 0; $i < $count; $i++) {
            $offset += 12; // msg_id + seqno
            $bodyLen = unpack('V', substr($payload, $offset, 4))[1];
            $offset += 4;
            $bodyOffset = 0;
            $messages[] = self::unwrapResultIfGzipped(TLDecoder::decodeObject(substr($payload, $offset, $bodyLen), $bodyOffset));
            $offset += $bodyLen;
        }

        return $messages;
    }

    /**
     * Routes one decoded server body inside a batch receive. Mutates $pending
     * (unset on routed rpc_result) and $results. Returns 'bad_server_salt' to
     * request a whole-batch resend, null when the body was consumed or skipped.
     *
     * @param  array<string, mixed>  $body
     * @param  array<int, string>  $pending  msg_id => key (mutated)
     * @param  array<string, array<string, mixed>>  $results  key => decoded result (mutated)
     * @param  array<int, TelegramException>  $errors  deferred rpc_errors (mutated; first one rethrown after the drain)
     * @param  array<int, array{method: string|null, code: int, message: string}>  $errorContexts  per-error wire facts (mutated), parallel to $errors
     * @param  int  $strays  running stray rpc_result counter (mutated; skip-with-cap like call())
     */
    protected function absorbBatchBody(array $body, array &$pending, array &$results, array &$errors, array &$errorContexts, int &$strays): ?string
    {
        $name = (string) ($body['_'] ?? '');

        if ($name === 'bad_server_salt') {
            $this->refreshServerSalt((int) $body['new_server_salt']);

            return 'bad_server_salt';
        }

        if ($name === 'new_session_created') {
            $this->logger->debug('teleframe.mtproto.connection.stray', [
                'kind' => 'push',
                'constructor' => 'new_session_created',
            ]);
            $this->refreshServerSalt((int) ($body['server_salt'] ?? $this->serverSalt));

            return null; // transient push
        }

        if ($name === 'bad_msg_notification') {
            throw new RuntimeException(sprintf(
                'EncryptedConnection: bad_msg_notification code %d for msg_id %s (seq %d)',
                (int) ($body['error_code'] ?? 0),
                (string) ($body['bad_msg_id'] ?? '?'),
                (int) ($body['bad_msg_seqno'] ?? -1)
            ));
        }

        if ($name !== 'rpc_result') {
            $this->logger->debug('teleframe.mtproto.connection.stray', [
                'kind' => 'push',
                'constructor' => $name,
            ]);

            return null; // msgs_ack / pong / updateShort* / not-ours pushes: batch is request/response only
        }

        $reqId = (int) $body['req_msg_id'];
        if (! isset($pending[$reqId])) {
            // Mirror call()'s skip-with-cap stray handling (tallyStrayResult):
            // a result answering an id that is not in-flight is skipped under
            // MAX_STRAY_RESULTS — never a mid-drain throw, which would strand
            // this batch's siblings and poison the next call()/callBatch().
            $strays++;
            $this->logger->debug('teleframe.mtproto.connection.stray', [
                'kind' => 'rpc_result',
                'req_msg_id' => $reqId,
                'expected_msg_ids' => array_keys($pending),
            ]);
            if ($strays > self::MAX_STRAY_RESULTS) {
                throw new RuntimeException(sprintf(
                    'EncryptedConnection: more than %d stray rpc_result(s), last req_msg_id %d, while awaiting batch {%s}',
                    self::MAX_STRAY_RESULTS,
                    $reqId,
                    implode(', ', array_keys($pending))
                ));
            }

            return null;
        }

        $key = $pending[$reqId];
        unset($pending[$reqId]);

        $inner = self::unwrapResultIfGzipped((array) $body['result']);
        if (($inner['_'] ?? '') === 'rpc_error') {
            $method = TLRegistry::nameOf(unpack('V', substr((string) $this->batchRequestBodies[$key], 0, 4))[1] ?? 0);
            $errorCode = (int) ($inner['error_code'] ?? 0);
            $errorMessage = (string) ($inner['error_message'] ?? 'UNKNOWN');
            // Defer the raise until every sibling's rpc_result has been
            // drained: throwing here aborts the receive loop and strands the
            // remaining frames, whose stale req_msg_ids churn the next
            // call()/callBatch() (unknown-req_msg_id / stray poison cap).
            // receiveBatchResults() rethrows the first collected error once
            // $pending is empty — typed TelegramException, so FLOOD_WAIT /
            // MIGRATE semantics survive — and the connection stays usable.
            $errors[] = RpcExceptionResolver::resolve($errorMessage, $errorCode, $method);

            // The wire facts are kept alongside the exception because the
            // resolved type does not uniformly expose them: DcMigrationException
            // extends TelegramException directly, so an instanceof read at the
            // throw site would lose method + code for MIGRATE errors. Captured
            // here, at the source, every error stays fully identifiable.
            $errorContexts[] = [
                'method' => $method,
                'code' => $errorCode,
                'message' => $errorMessage,
            ];

            return null;
        }
        $results[$key] = $inner;

        return null;
    }

    /**
     * Surfaces the batch rpc_errors that are NOT the one being thrown.
     *
     * $errors[0] becomes the exception, so errors 2..N would otherwise leave no
     * trace at all — no log, no return value, no side effect — and the caller
     * cannot know a second item of the batch also failed. Each one is logged
     * with its method + code + message so a multi-failure batch stays fully
     * diagnosable, while the throw itself is untouched: still the FIRST error,
     * still the same typed TelegramException, still raised only after every
     * sibling has been drained.
     *
     * Logging is the only viable carrier here: Exception::$previous is private
     * with no setter, so the suppressed errors cannot be chained onto the thrown
     * object after construction without re-wrapping it — which would destroy the
     * typed class (FloodWaitException / DcMigrationException / ...) that callers
     * branch on. This class already owns a PSR-3 logger ($this->logger, optional
     * and NullLogger by default), so no new dependency is introduced.
     *
     * @param  array<int, TelegramException>  $errors  every collected rpc_error, in arrival order
     * @param  array<int, array{method: string|null, code: int, message: string}>  $contexts  per-error wire facts, parallel to $errors
     */
    protected function logSuppressedBatchErrors(array $errors, array $contexts): void
    {
        if (count($errors) < 2) {
            return; // single failure: nothing is lost, the exception carries it all
        }

        $total = count($errors);
        foreach (array_slice($errors, 1) as $index => $error) {
            $context = $contexts[$index + 1] ?? ['method' => null, 'code' => 0, 'message' => ''];

            $this->logger->debug('teleframe.mtproto.connection.batch.rpc_error', [
                'kind' => 'batch_rpc_error',
                'suppressed_index' => $index + 2,
                'batch_error_count' => $total,
                'thrown_index' => 1,
                'method' => $context['method'],
                'code' => $context['code'],
                'message' => $context['message'],
                'exception_class' => $error::class,
            ]);
        }
    }

    /**
     * A 4-byte frame decoding to a negative int32 is a transport-level
     * error code (e.g. -404: auth key unknown) — surface it typed.
     */
    protected function assertNotTransportErrorFrame(string $frame): void
    {
        if (strlen($frame) === 4) {
            $int32 = unpack('l', $frame)[1];
            if ($int32 < 0) {
                throw RpcExceptionResolver::fromTransportCode($int32);
            }
        }
    }

    /**
     * Transient push-message constructor ids (msgs_ack / new_session_created),
     * sourced from TLRegistry so the ids exist in exactly one place.
     *
     * @return list<int>
     */
    protected static function transientConstructorIds(): array
    {
        return [TLRegistry::id('msgs_ack'), TLRegistry::id('new_session_created')];
    }

    /**
     * Update-container constructors the server pushes OUT OF BAND while an RPC
     * reply is pending (live-observed: messages.getDialogs is preceded by an
     * `updates` container). These are not the rpc_result, so they must be
     * consumed and skipped, not returned as the answer. Losing a push is
     * safe: the pts-based poller re-fetches anything missed via
     * updates.getDifference.
     *
     * @return list<string>
     */
    protected static function outOfBandUpdateConstructors(): array
    {
        return ['updates', 'updatesCombined', 'updateShort', 'updateShortMessage', 'updateShortChatMessage', 'updateShortSentMessage'];
    }

    /**
     * Reads encrypted frames until the reply for THIS request arrives.
     * rpc_results are demultiplexed strictly by req_msg_id: a result
     * answering another request (a batch sibling left unread after a
     * mid-batch rpc_error throw, a late duplicate, ...) is a stray —
     * skipped, never returned. Strays and transient pushes (msgs_ack /
     * new_session_created) are each poison-capped: at most
     * MAX_TRANSIENT_MESSAGES and MAX_STRAY_RESULTS respectively, then the
     * stream is declared broken.
     *
     * @param  int  $expectedMsgId  the msg_id call() sent this request with
     * @return array<string, mixed>
     */
    protected function receiveDecodedResponse(int $expectedMsgId): array
    {
        $transientIds = self::transientConstructorIds();
        $outOfBandCtors = self::outOfBandUpdateConstructors();
        $transients = 0;
        $outOfBand = 0;
        $strays = 0;
        while (true) {
            $frame = FrameCodec::receiveAbridgedMessage($this->socket);
            $this->touchActivity();
            $this->assertNotTransportErrorFrame($frame);

            $msg = PacketCodec::decryptPacket($frame, $this->session->authKey, expectedSessionId: $this->sessionId);
            $payload = $msg['payload'];

            $id = strlen($payload) >= 4 ? unpack('V', substr($payload, 0, 4))[1] : 0;
            if (in_array($id, $transientIds, true)) {
                if ($transients++ >= self::MAX_TRANSIENT_MESSAGES) {
                    throw new RuntimeException('EncryptedConnection: too many consecutive transient messages');
                }
                $this->logger->debug('teleframe.mtproto.connection.stray', [
                    'kind' => 'transient',
                    'constructor_id' => $id,
                ]);
                if ($id === TLRegistry::id('new_session_created')) {
                    $offset = 0;
                    $push = TLDecoder::decodeObject($payload, $offset);
                    $this->refreshServerSalt((int) ($push['server_salt'] ?? $this->serverSalt));
                }

                continue; // transient push, keep reading
            }

            // msg_container uses naked encoding (id + count + {msg_id, seqno, bytes, body} tuples,
            // no vector header, no per-message constructor) — must be hand-parsed.
            if ($id === 0x73F1F8DC) {
                $actionable = self::parseNakedContainer($payload, $this->serverSalt, $expectedMsgId, $strays, $this->logger);
                $this->refreshServerSalt($actionable['salt']);
                if ($actionable['message'] === null) {
                    continue; // container held only transients/strays; keep reading
                }

                if (in_array($actionable['message']['_'] ?? '', $outOfBandCtors, true)) {
                    self::countOutOfBandUpdate($outOfBand);

                    continue; // server-pushed update container, not our rpc_result
                }

                return $actionable['message'];
            }

            $offset = 0;
            $decoded = TLDecoder::decodeObject($payload, $offset);
            // Telegram may gzip the WHOLE message body (live-observed on
            // large updates.getDifference responses), not just rpc_result's
            // inner result field: unwrap before the rpc_result demux.
            $decoded = self::unwrapResultIfGzipped($decoded);
            if (($decoded['_'] ?? '') !== 'rpc_result'
                && in_array($decoded['_'] ?? '', $outOfBandCtors, true)) {
                self::countOutOfBandUpdate($outOfBand);

                continue; // unsolicited update push while the reply is pending
            }
            if (($decoded['_'] ?? '') === 'rpc_result') {
                $reqId = (int) $decoded['req_msg_id'];
                if ($reqId !== $expectedMsgId) {
                    self::tallyStrayResult($strays, $reqId, $expectedMsgId, $this->logger);

                    continue; // stray batch sibling / foreign result: skip, keep reading
                }
            }

            return $decoded;
        }
    }

    /**
     * Counts one skipped stray rpc_result against the MAX_STRAY_RESULTS
     * poison cap, throwing past it.
     */
    protected static function tallyStrayResult(int &$strays, int $reqId, int $expectedMsgId, ?LoggerInterface $logger = null): void
    {
        $logger?->debug('teleframe.mtproto.connection.stray', [
            'kind' => 'rpc_result',
            'req_msg_id' => $reqId,
            'expected_msg_id' => $expectedMsgId,
        ]);
        $strays++;
        if ($strays > self::MAX_STRAY_RESULTS) {
            throw new RuntimeException(sprintf(
                'EncryptedConnection: more than %d stray rpc_result(s), last req_msg_id %d, while awaiting req_msg_id %d',
                self::MAX_STRAY_RESULTS,
                $reqId,
                $expectedMsgId
            ));
        }
    }

    /**
     * Counts one consumed out-of-band update push against its generous cap.
     */
    protected static function countOutOfBandUpdate(int &$outOfBand): void
    {
        $outOfBand++;
        if ($outOfBand > self::MAX_OUT_OF_BAND_UPDATES) {
            throw new RuntimeException(sprintf(
                'EncryptedConnection: more than %d out-of-band update container(s) while awaiting an rpc_result',
                self::MAX_OUT_OF_BAND_UPDATES,
            ));
        }
    }

    /**
     * Parses a naked-encoded msg_container payload. Returns the first
     * actionable decoded body — one whose rpc_result req_msg_id matches the
     * awaited request, or any non-rpc_result non-transient body — or null
     * when every element was transient/stray, plus the possibly-refreshed
     * server salt from new_session_created. Stray rpc_result bodies count
     * against the caller's stray cap via $strays.
     *
     * @param  int  $strays  running stray counter (mutated)
     * @return array{message: array<string, mixed>|null, salt: int}
     */
    protected static function parseNakedContainer(string $payload, int $currentSalt, int $expectedMsgId, int &$strays, ?LoggerInterface $logger = null): array
    {
        $transientIds = self::transientConstructorIds();
        $salt = $currentSalt;
        $offset = 4;
        $count = unpack('V', substr($payload, $offset, 4))[1];
        $offset += 4;

        for ($i = 0; $i < $count; $i++) {
            $offset += 8; // msg_id
            $offset += 4; // seqno
            $bodyLen = unpack('V', substr($payload, $offset, 4))[1];
            $offset += 4;
            $bodyBin = substr($payload, $offset, $bodyLen);
            $offset += $bodyLen;

            $bodyOffset = 0;
            $body = TLDecoder::decodeObject($bodyBin, $bodyOffset);
            // Container inner bodies can be whole-body gzip_packed too.
            $body = self::unwrapResultIfGzipped($body);
            $name = (string) ($body['_'] ?? '');

            if ($name === 'new_session_created') {
                $logger?->debug('teleframe.mtproto.connection.stray', [
                    'kind' => 'transient',
                    'constructor' => 'new_session_created',
                ]);
                $salt = (int) ($body['server_salt'] ?? $salt);

                continue;
            }
            if (in_array(TLRegistry::id($name), $transientIds, true)) {
                $logger?->debug('teleframe.mtproto.connection.stray', [
                    'kind' => 'transient',
                    'constructor_id' => TLRegistry::id($name),
                ]);

                continue;
            }
            if ($name === 'rpc_result' && (int) $body['req_msg_id'] !== $expectedMsgId) {
                self::tallyStrayResult($strays, (int) $body['req_msg_id'], $expectedMsgId, $logger);

                continue; // stray sibling packed into the container: drop, keep scanning
            }

            return ['message' => $body, 'salt' => $salt];
        }

        return ['message' => null, 'salt' => $salt];
    }

    /**
     * Encrypted client message id: ≡ 0 (mod 4), strictly increasing —
     * matching MadelineProto/TDLib (server rejects other residues with
     * bad_msg_notification code 18). Seq stays odd for content messages.
     */
    protected function nextMessageId(): int
    {
        $candidate = ((int) ((microtime(true) + $this->session->serverTimeDelta) * 2 ** 32)) & ~3;
        if ($candidate <= $this->lastMessageId) {
            $candidate = $this->lastMessageId + 4; // keeps ≡ 0 (mod 4) while strictly increasing
        }

        return $this->lastMessageId = $candidate;
    }

    /**
     * Refreshes the in-memory salt and mirrors it into the SessionData the
     * connection holds (same object reference the Client owns), so a fresh
     * salt from bad_server_salt / new_session_created survives reconnects
     * and session persistence.
     */
    protected function refreshServerSalt(int $salt): void
    {
        $this->serverSalt = $salt;
        $this->session->serverSalt = $salt;
    }

    protected function touchActivity(): void
    {
        $this->lastActivity = microtime(true);
    }

    /**
     * seq_no for the outgoing msg_container envelope. A container is a
     * NON-content-related message (never acknowledged on its own), so its
     * seq_no must be EVEN: counter*2 with the content counter NOT consumed
     * (docs: core.telegram.org/mtproto/description, error_code 34 otherwise).
     * Because the container is generated after its contents, this is always
     * one above the highest inner seq_no — "greater than or equal to the
     * sequence numbers of the messages contained in it" holds.
     */
    protected function nextContainerSeqNo(): int
    {
        return $this->contentCounter + 1; // contentCounter is always odd (−1 + 2n), so +1 is even
    }

    /**
     * seq_no for outgoing client content messages: odd and strictly
     * increasing (2n+1). A content message with an even seq_no is rejected
     * by the server with bad_msg_notification code 35.
     */
    protected function nextContentSeqNo(): int
    {
        return $this->contentCounter += 2;
    }

    /**
     * Whether invokeWithLayer/initConnection has already been sent on this
     * connection. callBatch() never sends the wrap itself, so its callers
     * (Client::callMany) route the first request through call() to establish
     * the layer before batching the rest.
     */
    public function isInited(): bool
    {
        return $this->inited;
    }

    public function lastSessionData(): SessionData
    {
        return $this->session;
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
    }
}
