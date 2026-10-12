<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Connection;

use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PacketCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\StreamSocket;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Unencrypted framed connection used only for the auth-key handshake.
 * Every plain message is wrapped in the unencrypted MTProto envelope:
 * auth_key_id(8, zero) || message_id(8) || message_length(4, LE) || body.
 *
 * Logging is optional and silent by default: a NullLogger is used when none
 * is given. Metadata only — payloads are NEVER logged.
 */
class PlainConnection
{
    private const MAX_MESSAGE = 2 * 1024 * 1024;

    /** @var resource */
    public $socket;

    protected LoggerInterface $logger;

    /** @param resource $socket */
    final public function __construct($socket, ?LoggerInterface $logger = null)
    {
        $this->socket = $socket;
        $this->logger = $logger ?? new NullLogger;
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
        string $host,
        int $port = 443,
        float $timeout = 10.0,
        ?LoggerInterface $logger = null,
        ?array $proxy = null,
        bool $tls = false,
        ?string $peerName = null,
        ?string $cafile = null
    ): static {
        $logger?->debug('teleframe.mtproto.plain.connect.start', [
            'host' => $host,
            'port' => $port,
            'timeout' => $timeout,
            'proxied' => $proxy !== null,
            'tls' => $tls,
        ]);

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
            $logger?->debug('teleframe.mtproto.plain.connect.open', [
                'host' => $host,
                'port' => $port,
                'proxied' => $proxy !== null,
                'tls' => $tls,
            ]);

            FrameCodec::writeInit($socket);
            $logger?->debug('teleframe.mtproto.plain.init.byte.sent', [
                'host' => $host,
                'port' => $port,
            ]);
        } catch (RuntimeException $e) {
            $logger?->error('teleframe.mtproto.plain.handshake.failed', [
                'host' => $host,
                'port' => $port,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $e;
        }

        return new static($socket, logger: $logger);
    }

    /**
     * Builds the unencrypted-message envelope around $payload. Client message
     * ids follow the docs' plain-message convention (unixtime<<32 plus
     * sub-second counter, divisible by 4 — see PacketCodec::generateMessageId).
     */
    public static function buildPlainEnvelope(string $payload, int $msgId): string
    {
        if ($msgId <= 0) {
            throw new RuntimeException('PlainConnection: message_id must be positive');
        }

        return pack('P', 0).pack('P', $msgId).pack('V', strlen($payload)).$payload;
    }

    /**
     * Validates a received unencrypted-message envelope and returns the body.
     *
     * @throws RuntimeException on non-zero auth_key_id, impossible length, or
     *                          length not matching the actual frame size
     */
    public static function parsePlainEnvelope(string $frame): string
    {
        if (strlen($frame) < 20) {
            throw new RuntimeException('PlainConnection: envelope shorter than 20-byte header');
        }
        if (substr($frame, 0, 8) !== str_repeat("\x00", 8)) {
            throw new RuntimeException('PlainConnection: non-zero auth_key_id in plain response');
        }
        $msgId = unpack('P', substr($frame, 8, 8))[1];
        if ($msgId <= 0) {
            throw new RuntimeException('PlainConnection: invalid response message_id');
        }
        $length = unpack('V', substr($frame, 16, 4))[1];
        if ($length < 1 || $length > self::MAX_MESSAGE) {
            throw new RuntimeException(sprintf('PlainConnection: invalid message length %d', $length));
        }
        $body = substr($frame, 20);
        if (strlen($body) !== $length) {
            throw new RuntimeException(sprintf('PlainConnection: length %d does not match frame body %d', $length, strlen($body)));
        }

        return $body;
    }

    public function request(string $payload): string
    {
        $this->logger->debug('teleframe.mtproto.plain.init.request.sent', [
            'bytes' => strlen($payload),
        ]);

        try {
            FrameCodec::sendAbridgedMessage(
                $this->socket,
                self::buildPlainEnvelope($payload, PacketCodec::generateMessageId())
            );
        } catch (RuntimeException $e) {
            $this->logger->error('teleframe.mtproto.plain.handshake.failed', [
                'phase' => 'send',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $e;
        }

        try {
            $frame = FrameCodec::receiveAbridgedMessage($this->socket);
        } catch (RuntimeException $e) {
            $this->logger->error('teleframe.mtproto.plain.handshake.failed', [
                'phase' => 'read',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $e;
        }

        $body = self::parsePlainEnvelope($frame);

        $this->logger->debug('teleframe.mtproto.plain.init.response.received', [
            'bytes' => strlen($body),
        ]);

        return $body;
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            $this->logger->debug('teleframe.mtproto.plain.close');
            @fclose($this->socket);
        }
    }
}
