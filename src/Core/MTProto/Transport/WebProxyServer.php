<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * WEB Proxy Server (tdesktop-web-proxy-bridge-v1 server-side).
 *
 * Implements the server side of the tdesktop-web-proxy-bridge-v1 protocol.
 * Accepts connections from clients using WEB proxy and forwards traffic to Telegram DCs.
 *
 * Protocol flow:
 * 1. Client connects via HTTPS (long-polling) or WebSocket
 * 2. Client sends HELLO frame with supported carrier modes
 * 3. Server responds with WELCOME frame selecting carrier mode
 * 4. Client opens streams with OPEN frames
 * 5. Data flows via DATA frames with flow control (WINDOW frames)
 * 6. Streams closed with CLOSE frames
 * 7. Keepalive via PING/PONG frames
 * 8. Connection terminated with BYE frame
 *
 * Supports sponsor channel: when configured, the server advertises a Telegram
 * channel that clients will display in their proxy settings.
 */
class WebProxyServer
{
    /** Frame types (per PROTOCOL.md) */
    public const FRAME_OPEN = 0x01;

    public const FRAME_DATA = 0x02;

    public const FRAME_CLOSE = 0x03;

    public const FRAME_WINDOW = 0x04;

    public const FRAME_PING = 0x05;

    public const FRAME_PONG = 0x06;

    public const FRAME_BYE = 0x07;

    public const FRAME_HELLO = 0x08;

    public const FRAME_WELCOME = 0x09;

    /** Frame flags */
    public const FLAG_NONE = 0x00;

    public const FLAG_FIN = 0x01;

    /** Default flow control window (4 MiB) */
    public const DEFAULT_WINDOW = 4 * 1024 * 1024;

    /** Maximum frame payload size (1 MiB) */
    public const MAX_FRAME_SIZE = 1024 * 1024;

    /** Supported carrier modes */
    public const CARRIER_MODES = ['https', 'https-lanes', 'websocket', 'websocket-lanes', 'auto'];

    private $socket;

    private LoggerInterface $logger;

    private array $streams = [];

    private array $streamWindows = [];

    private array $streamBuffers = [];

    private array $streamClosed = [];

    private ?string $sponsorChannel = null;

    private bool $closed = false;

    /**
     * @param  resource  $socket  Connected client socket (after TLS/WebSocket upgrade)
     * @param  callable(int, string): resource  $upstreamResolver  Resolves DC connections
     * @param  string|null  $sponsorChannel  Optional sponsor channel username (e.g., '@mychannel')
     */
    public function __construct(
        $socket,
        /** @var callable(int, string): resource */
        private readonly mixed $upstreamResolver,
        ?LoggerInterface $logger = null,
        ?string $sponsorChannel = null
    ) {
        $this->socket = $socket;
        $this->logger = $logger ?? new NullLogger;
        $this->sponsorChannel = $sponsorChannel;
    }

    /**
     * Process a single client connection (handles HELLO/WELCOME and frame loop).
     */
    public function serve(): void
    {
        try {
            // 1. Read HELLO frame from client
            $helloFrame = $this->readFrame();
            if ($helloFrame === null || $helloFrame['type'] !== self::FRAME_HELLO) {
                $this->logger->warning('teleframe.web_proxy_server.invalid_hello', [
                    'type' => $helloFrame['type'] ?? 'null',
                ]);

                return;
            }

            // 2. Parse HELLO payload (client's supported carriers)
            $helloPayload = json_decode($helloFrame['payload'], true);
            if (! is_array($helloPayload) || ! isset($helloPayload['carriers'])) {
                $this->logger->warning('teleframe.web_proxy_server.invalid_hello_payload');

                return;
            }

            // 3. Select carrier mode
            $clientCarriers = $helloPayload['carriers'];
            $selectedCarrier = $this->selectCarrier($clientCarriers);

            // 4. Send WELCOME frame
            $welcomePayload = json_encode([
                'carrier' => $selectedCarrier,
                'sponsor_channel' => $this->sponsorChannel,
            ]);
            $this->sendFrame(self::FRAME_WELCOME, self::FLAG_NONE, 0, $welcomePayload);

            $this->logger->info('teleframe.web_proxy_server.handshake_done', [
                'selected_carrier' => $selectedCarrier,
                'sponsor_channel' => $this->sponsorChannel,
                'client_carriers' => $clientCarriers,
            ]);

            // 5. Main frame loop
            $this->frameLoop();

        } catch (RuntimeException $e) {
            $this->logger->warning('teleframe.web_proxy_server.client_error', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Select carrier mode from client's supported modes.
     * Prefers WebSocket modes for better performance, falls back to HTTPS.
     */
    private function selectCarrier(array $clientCarriers): string
    {
        // Prefer WebSocket modes
        $preferred = ['websocket', 'websocket-lanes', 'https', 'https-lanes'];

        foreach ($preferred as $mode) {
            if (in_array($mode, $clientCarriers, true)) {
                return $mode;
            }
        }

        // Fallback to auto (should not happen if client follows protocol)
        return 'auto';
    }

    /**
     * Main frame processing loop.
     */
    private function frameLoop(): void
    {
        while (! $this->closed) {
            $frame = $this->readFrame();
            if ($frame === null) {
                break; // Connection closed
            }

            $this->handleFrame($frame);
        }
    }

    /**
     * Handle an incoming frame based on its type.
     */
    private function handleFrame(array $frame): void
    {
        $type = $frame['type'];
        $streamId = $frame['stream_id'];
        $payload = $frame['payload'];
        $flags = $frame['flags'];

        switch ($type) {
            case self::FRAME_OPEN:
                $this->handleOpen($streamId);
                break;

            case self::FRAME_DATA:
                $this->handleData($streamId, $payload, $flags);
                break;

            case self::FRAME_CLOSE:
                $this->handleClose($streamId);
                break;

            case self::FRAME_WINDOW:
                $this->handleWindow($streamId, $payload);
                break;

            case self::FRAME_PING:
                // Respond with PONG
                $this->sendFrame(self::FRAME_PONG, self::FLAG_NONE, 0, '');
                break;

            case self::FRAME_PONG:
                // Pong received - keepalive OK
                break;

            case self::FRAME_BYE:
                $this->closed = true;
                break;

            default:
                $this->logger->warning('teleframe.web_proxy_server.unknown_frame_type', [
                    'type' => '0x'.dechex($type),
                    'stream_id' => $streamId,
                ]);
        }
    }

    /**
     * Handle OPEN frame - create new stream to upstream DC.
     */
    private function handleOpen(int $streamId): void
    {
        if (isset($this->streams[$streamId])) {
            $this->logger->warning('teleframe.web_proxy_server.duplicate_stream', [
                'stream_id' => $streamId,
            ]);

            return;
        }

        // For WEB proxy, the first OPEN typically contains the target DC info
        // In the simple case, we'll use a default DC or extract from stream
        // The client should specify DC in the OPEN payload or we use a default

        $dcId = 2; // Default DC
        $protocol = 'abridged'; // Default protocol

        $upstream = ($this->upstreamResolver)($dcId, $protocol);
        if (! is_resource($upstream)) {
            $this->sendFrame(self::FRAME_CLOSE, self::FLAG_NONE, $streamId, '');

            return;
        }

        $this->streams[$streamId] = $upstream;
        $this->streamWindows[$streamId] = self::DEFAULT_WINDOW;
        $this->streamBuffers[$streamId] = '';
        $this->streamClosed[$streamId] = false;

        $this->logger->debug('teleframe.web_proxy_server.stream_opened', [
            'stream_id' => $streamId,
            'dc_id' => $dcId,
        ]);

        // Start reading from upstream
        $this->readFromUpstream($streamId);
    }

    /**
     * Handle DATA frame - forward to upstream.
     */
    private function handleData(int $streamId, string $payload, int $flags): void
    {
        if (! isset($this->streams[$streamId])) {
            $this->logger->warning('teleframe.web_proxy_server.data_unknown_stream', [
                'stream_id' => $streamId,
            ]);

            return;
        }

        if ($this->streamClosed[$streamId]) {
            return;
        }

        // Check flow control window
        $window = $this->streamWindows[$streamId] ?? 0;
        if ($window <= 0) {
            // Window full - buffer or drop
            $this->logger->debug('teleframe.web_proxy_server.window_full', [
                'stream_id' => $streamId,
            ]);

            return;
        }

        $len = min(strlen($payload), $window);
        $data = substr($payload, 0, $len);

        try {
            $this->writeToUpstream($streamId, $data);
        } catch (RuntimeException $e) {
            $this->closeStream($streamId);

            return;
        }

        $this->streamWindows[$streamId] -= $len;

        if ($flags & self::FLAG_FIN) {
            $this->streamClosed[$streamId] = true;
            $this->closeStream($streamId);
        }
    }

    /**
     * Handle CLOSE frame.
     */
    private function handleClose(int $streamId): void
    {
        $this->closeStream($streamId);
    }

    /**
     * Handle WINDOW frame - update flow control window.
     */
    private function handleWindow(int $streamId, string $payload): void
    {
        if (! isset($this->streamWindows[$streamId])) {
            return;
        }

        if (strlen($payload) >= 4) {
            $increment = unpack('V', $payload)[1] ?? 0;
            $this->streamWindows[$streamId] += $increment;
        }
    }

    /**
     * Read a frame from the client socket.
     */
    private function readFrame(): ?array
    {
        $header = $this->readExact(10);
        if ($header === '') {
            return null;
        }

        $type = ord($header[0]);
        $flags = ord($header[1]);
        $streamId = unpack('V', substr($header, 2, 4))[1];
        $length = unpack('V', substr($header, 6, 4))[1];

        if ($length > self::MAX_FRAME_SIZE) {
            throw new RuntimeException("WebProxyServer: frame too large ({$length} > ".self::MAX_FRAME_SIZE.')');
        }

        $payload = '';
        if ($length > 0) {
            $payload = $this->readExact($length);
        }

        return [
            'type' => $type,
            'flags' => $flags,
            'stream_id' => $streamId,
            'length' => $length,
            'payload' => $payload,
        ];
    }

    /**
     * Send a frame to the client.
     */
    private function sendFrame(int $type, int $flags, int $streamId, string $payload): void
    {
        $frame = chr($type & 0xFF)
            .chr($flags & 0xFF)
            .pack('V', $streamId)
            .pack('V', strlen($payload))
            .$payload;

        $this->write($this->socket, $frame);
    }

    /**
     * Read exactly $length bytes from the client socket.
     */
    private function readExact(int $length): string
    {
        return StreamSocket::readExact($this->socket, $length);
    }

    /**
     * Write data to the client socket.
     */
    private function write($socket, string $data): void
    {
        StreamSocket::write($socket, $data);
    }

    /**
     * Write data to upstream socket.
     */
    private function writeToUpstream(int $streamId, string $data): void
    {
        $upstream = $this->streams[$streamId];
        $this->write($upstream, $data);
    }

    /**
     * Read from upstream and send DATA frames to client.
     */
    private function readFromUpstream(int $streamId): void
    {
        $upstream = $this->streams[$streamId];
        $buffer = @fread($upstream, 8192);

        if ($buffer === false || $buffer === '') {
            $this->closeStream($streamId);

            return;
        }

        $this->sendFrame(self::FRAME_DATA, self::FLAG_NONE, $streamId, $buffer);
    }

    /**
     * Close a stream and cleanup.
     */
    private function closeStream(int $streamId): void
    {
        if (! isset($this->streams[$streamId])) {
            return;
        }

        $this->sendFrame(self::FRAME_CLOSE, self::FLAG_NONE, $streamId, '');

        if (is_resource($this->streams[$streamId])) {
            fclose($this->streams[$streamId]);
        }

        unset(
            $this->streams[$streamId],
            $this->streamWindows[$streamId],
            $this->streamBuffers[$streamId],
            $this->streamClosed[$streamId]
        );

        $this->logger->debug('teleframe.web_proxy_server.stream_closed', [
            'stream_id' => $streamId,
        ]);
    }

    /**
     * Cleanup all resources.
     */
    private function cleanup(): void
    {
        foreach ($this->streams as $streamId => $upstream) {
            if (is_resource($upstream)) {
                fclose($upstream);
            }
        }

        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->streams = [];
        $this->streamWindows = [];
        $this->streamBuffers = [];
        $this->streamClosed = [];
        $this->closed = true;
    }
}
