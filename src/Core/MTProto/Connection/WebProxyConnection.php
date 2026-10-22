<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Connection;

use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcExceptionResolver;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSerializer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\StreamSocket;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebProxyBridge;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebProxyStream;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * WEB Proxy MTProto Connection (tdesktop-web-proxy-bridge-v1).
 *
 * Uses WebProxyStream for multiplexing MTProto traffic over a WEB proxy
 * carrier connection (HTTPS long-polling mode).
 */
class WebProxyConnection
{
    public const LAYER = 227;

    protected const MAX_STRAY_RESULTS = 8;

    public const MAX_BATCH_MESSAGES = 1020;

    public const MAX_BATCH_CONTAINER_BYTES = 32768;

    protected ?array $batchRequestBodies = null;

    protected WebProxyStream $webProxyStream;

    protected int $streamId;

    protected int $serverSalt = 0;

    protected int $sessionId;

    protected bool $inited = false;

    protected int $lastMessageId = 0;

    protected int $contentCounter = -1;

    protected float $lastActivity = 0.0;

    protected LoggerInterface $logger;

    /**
     * @param  WebProxyStream  $webProxyStream  Connected WEB proxy stream (after HELLO/WELCOME)
     */
    public function __construct(
        protected SessionData $session,
        WebProxyStream $webProxyStream,
        protected int $apiId = 0,
        ?LoggerInterface $logger = null
    ) {
        $this->webProxyStream = $webProxyStream;
        $this->logger = $logger ?? new NullLogger;
        $this->sessionId = (int) unpack('P', random_bytes(8))[1];
        $this->serverSalt = $session->serverSalt;

        // Open a logical stream for MTProto traffic
        $this->streamId = $this->webProxyStream->openStream();

        // Write the 0xef init byte (abridged handshake)
        $this->writeRaw("\xef");
    }

    /**
     * Establish a WEB proxy connection to a Telegram DC.
     *
     * @param  array{type?: string, host?: string, secret?: string, carrier?: string}|null  $proxy
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
        if ($proxy === null || ($proxy['type'] ?? '') !== 'web') {
            throw new RuntimeException('WebProxyConnection: requires WEB proxy config');
        }

        $logger = $logger ?? new NullLogger;

        try {
            // StreamSocket::createConnection with WEB proxy will:
            // 1. Connect to bridge URL with TLS
            // 2. Perform HELLO/WELCOME handshake
            // 3. Return the carrier socket
            $carrierSocket = StreamSocket::createConnection(
                host: $host,
                port: $port,
                proxy: $proxy,
                timeout: $timeout,
                tls: $tls,
                logger: $logger,
                cafile: $cafile,
                peerName: $peerName
            );

            // Determine transport mode from carrier
            $carrier = WebProxyBridge::normalizeCarrier((string) ($proxy['carrier'] ?? WebProxyBridge::DEFAULT_CARRIER));
            $mode = ($carrier === 'websocket' || $carrier === 'websocket-lanes')
                ? WebProxyStream::MODE_WEBSOCKET
                : WebProxyStream::MODE_HTTPS;

            // Wrap carrier socket in WebProxyStream
            $webProxyStream = new WebProxyStream($carrierSocket, $logger, $mode);
        } catch (RuntimeException $e) {
            $logger->error('teleframe.mtproto.web_proxy.connection.failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $e;
        }

        $logger->debug('teleframe.mtproto.web_proxy.connection.connect', [
            'host' => $host,
            'port' => $port,
            'proxied' => true,
        ]);

        /** @var static $instance */
        $instance = new self($session, $webProxyStream, 0, $logger);

        return $instance;
    }

    /**
     * Set the API ID for initConnection (called by Client after construction).
     */
    public function setApiId(int $apiId): self
    {
        $this->apiId = $apiId;

        return $this;
    }

    /**
     * Set logger.
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        $this->webProxyStream = $this->webProxyStream; // logger is not passed to stream, but could be

        return $this;
    }

    /**
     * Build first query body with invokeWithLayer + initConnection.
     */
    public static function buildFirstQueryBody(int $layer, array $initConnectionArgs): string
    {
        $init = TLEncoder::encodeObject('initConnection', $initConnectionArgs);

        return TLSerializer::packInt(TLRegistry::id('invokeWithLayer'))
            .TLSerializer::packInt($layer)
            .$init;
    }

    /**
     * Unwrap gzip_packed result.
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
            throw new RuntimeException('WebProxyConnection: gzdecode failed');
        }
        $offset = 0;

        return TLDecoder::decodeObject($inflated, $offset);
    }

    /**
     * MTProto keepalive ping.
     *
     * @return array<string, mixed> the pong result
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
                'WebProxyConnection: pong ping_id mismatch (sent %d, received %s)',
                $pingId,
                isset($pong['ping_id']) ? var_export($pong['ping_id'], true) : '(missing)'
            ));
        }

        return $pong;
    }

    /**
     * Fresh random ping_id for keepalive.
     */
    protected function newPingId(): int
    {
        return random_int(0, PHP_INT_MAX);
    }

    /**
     * Seconds since last activity.
     */
    public function idleSeconds(): float
    {
        return $this->lastActivity === 0.0 ? 0.0 : max(0.0, microtime(true) - $this->lastActivity);
    }

    /**
     * Current server salt.
     */
    public function getServerSalt(): int
    {
        return $this->serverSalt;
    }

    /**
     * Check if connection is initialized.
     */
    public function isInited(): bool
    {
        return $this->inited;
    }

    /**
     * Execute a raw MTProto RPC method.
     *
     * @param  string  $constructor  MTProto method name
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     *
     * @throws TelegramException on rpc_error
     * @throws RuntimeException on transport/protocol failures
     */
    public function call(string $constructor, array $args = []): array
    {
        if ($this->webProxyStream->isClosed()) {
            throw new RuntimeException('WebProxyConnection: connection closed');
        }

        $start = microtime(true);
        $this->logger->debug('teleframe.mtproto.web_proxy.call', [
            'method' => $constructor,
            'phase' => 'start',
        ]);

        try {
            [$result, $bytes] = $this->runCall($constructor, $args);
        } catch (TelegramException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            $this->logger->error('teleframe.mtproto.web_proxy.call.failed', [
                'method' => $constructor,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $e;
        }

        $this->logger->debug('teleframe.mtproto.web_proxy.call', [
            'method' => $constructor,
            'messages' => 1,
            'bytes' => $bytes,
            'ms' => (int) ((microtime(true) - $start) * 1000),
            'phase' => 'done',
        ]);

        return $result;
    }

    /**
     * Execute N independent MTProto RPC methods in one round-trip.
     *
     * @param  array<string, array{method: string, params: array<string, mixed>}>  $requests
     * @return array<string, array<string, mixed>>
     */
    public function callBatch(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        if ($this->webProxyStream->isClosed()) {
            throw new RuntimeException('WebProxyConnection: connection closed');
        }

        // Build bodies for all requests
        $bodies = [];
        foreach ($requests as $key => $request) {
            $bodies[$key] = TLEncoder::encodeObject($request['method'], $request['params']);
        }
        self::assertBatchWithinBounds($bodies);

        $this->batchRequestBodies = $bodies;

        try {
            if (! $this->inited) {
                // First call establishes the layer
                $firstKey = array_key_first($bodies);
                $results = [$firstKey => $this->call($requests[$firstKey]['method'], $requests[$firstKey]['params'])];
                unset($bodies[$firstKey]);
                $this->inited = true;

                return $results + $this->callBatchEncoded($bodies);
            }

            return $this->callBatchEncoded($bodies);
        } finally {
            $this->batchRequestBodies = null;
        }
    }

    /**
     * Internal method to execute a batch with pre-encoded bodies.
     *
     * @param  array<string, string>  $bodies  key => encoded body
     * @return array<string, array<string, mixed>>
     */
    private function callBatchEncoded(array $bodies): array
    {
        if ($bodies === []) {
            return [];
        }

        return $this->runBatch($bodies);
    }

    /**
     * Run a single RPC call.
     *
     * @return array{0: array<string, mixed>, 1: int} [result, bytes_sent]
     */
    protected function runCall(string $constructor, array $args = []): array
    {
        $body = $this->inited
            ? TLEncoder::encodeObject($constructor, $args)
            : self::buildFirstQueryBody(self::LAYER, $this->buildInitConnectionArgs($constructor, $args));

        $this->sendMessage($body);
        $this->lastActivity = microtime(true);

        return [$this->readResponse(), strlen($body)];
    }

    /**
     * Build initConnection arguments.
     */
    protected function buildInitConnectionArgs(string $constructor, array $args): array
    {
        $initArgs = [
            'api_id' => $this->apiId,
            'device_model' => 'teleframe',
            'system_version' => 'unknown',
            'app_version' => '1.0',
            'system_lang_code' => 'en',
            'lang_pack' => '',
            'lang_code' => 'en',
            'query' => ['_' => $constructor] + $args,
        ];

        // Add proxy field for WEB proxy (flags.0)
        // The proxy info is already handled at the transport layer, but initConnection
        // can carry it for the server's reference

        return $initArgs;
    }

    /**
     * Run a batch of pre-encoded bodies.
     *
     * @param  array<string, string>  $bodies  key => encoded body
     * @return array<string, array<string, mixed>> key => decoded result
     */
    protected function runBatch(array $bodies): array
    {
        $containerBody = TLSerializer::packInt(TLRegistry::id('msg_container'))
            .TLSerializer::packInt(count($bodies));

        foreach ($bodies as $msgId => $body) {
            $containerBody .= TLSerializer::packLong((int) $msgId)
                .TLSerializer::packInt(strlen($body))
                .$body;
        }

        $this->sendMessage($containerBody);
        $this->lastActivity = microtime(true);

        $results = [];
        $remaining = $bodies;

        while ($remaining !== []) {
            $response = $this->readResponse();
            if (($response['_'] ?? '') === 'rpc_result') {
                $reqMsgId = $response['req_msg_id'] ?? null;
                if ($reqMsgId !== null && isset($remaining[$reqMsgId])) {
                    $results[$reqMsgId] = $response['result'];
                    unset($remaining[$reqMsgId]);
                } else {
                    // Stray result - log and continue
                    $this->logger->warning('teleframe.mtproto.web_proxy.stray_result', [
                        'req_msg_id' => $reqMsgId,
                    ]);
                }
            } elseif (($response['_'] ?? '') === 'rpc_error') {
                // Batch rpc_error - defer until all results drained
                $errorMessage = $response['error_message'] ?? 'Unknown RPC error';
                throw RpcExceptionResolver::resolve($errorMessage, $response['error_code'] ?? 0);
            } elseif (($response['_'] ?? '') === 'msgs_ack') {
                // ACK - ignore
            } elseif (($response['_'] ?? '') === 'gzip_packed') {
                $unpacked = self::unwrapResultIfGzipped($response);
                // Recurse to handle unpacked
                $reqMsgId = $unpacked['req_msg_id'] ?? null;
                if ($reqMsgId !== null && isset($remaining[$reqMsgId])) {
                    $results[$reqMsgId] = $unpacked['result'];
                    unset($remaining[$reqMsgId]);
                }
            } else {
                // Out-of-band update - skip
                $this->logger->debug('teleframe.mtproto.web_proxy.out_of_band', [
                    'type' => $response['_'] ?? 'unknown',
                ]);
            }
        }

        return $results;
    }

    /**
     * Send a message (with abridged framing).
     */
    protected function sendMessage(string $payload): void
    {
        $framed = FrameCodec::wrapAbridgedPayload($payload);
        $this->writeRaw($framed);
    }

    /**
     * Write raw bytes to the WEB proxy stream.
     */
    protected function writeRaw(string $data): void
    {
        $this->webProxyStream->writeStream($this->streamId, $data);
    }

    /**
     * Read a response from the WEB proxy stream.
     *
     * @return array<string, mixed>
     */
    protected function readResponse(): array
    {
        // Read abridged frame length
        $first = ord($this->readExact(1));
        $len4 = $first;
        if ($len4 >= 0x7F) {
            $len4 = unpack('V', $this->readExact(3)."\x00")[1];
        }
        $len = $len4 << 2;

        if ($len < 1 || $len > FrameCodec::MAX_FRAME) {
            throw new RuntimeException("WebProxyConnection: invalid frame length {$len}");
        }

        $frame = $this->readExact($len);
        $offset = 0;

        return TLDecoder::decodeObject($frame, $offset);
    }

    /**
     * Read exactly $length bytes from the WEB proxy stream.
     */
    protected function readExact(int $length): string
    {
        return $this->webProxyStream->readStreamExact($this->streamId, $length);
    }

    /**
     * Assert batch within bounds.
     *
     * @param  array<string, string>  $bodies
     */
    public static function assertBatchWithinBounds(array $bodies): void
    {
        if (count($bodies) > self::MAX_BATCH_MESSAGES) {
            throw new RuntimeException('WebProxyConnection: batch exceeds max messages ('.count($bodies).' > '.self::MAX_BATCH_MESSAGES.')');
        }
        $totalBytes = 0;
        foreach ($bodies as $body) {
            $totalBytes += strlen($body);
        }
        if ($totalBytes > self::MAX_BATCH_CONTAINER_BYTES) {
            throw new RuntimeException("WebProxyConnection: batch exceeds max container bytes ({$totalBytes} > ".self::MAX_BATCH_CONTAINER_BYTES.')');
        }
    }

    /**
     * Close the connection.
     */
    public function close(): void
    {
        if (! $this->webProxyStream->isClosed()) {
            $this->webProxyStream->closeStream($this->streamId);
            $this->webProxyStream->close();
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
