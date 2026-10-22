<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * WEB Proxy Stream Multiplexer (tdesktop-web-proxy-bridge-v1).
 *
 * Handles the shared frame protocol for multiplexing multiple logical streams
 * over a single WEB proxy carrier connection (HTTPS/WebSocket).
 *
 * Frame types (per PROTOCOL.md):
 * - 0x01: OPEN      - Open new logical stream
 * - 0x02: DATA      - Data payload for stream
 * - 0x03: CLOSE     - Close stream
 * - 0x04: WINDOW    - Flow control window update
 * - 0x05: PING      - Keepalive ping
 * - 0x06: PONG      - Keepalive pong
 * - 0x07: BYE       - Connection termination
 * - 0x08: HELLO     - Carrier negotiation (client -> server)
 * - 0x09: WELCOME   - Carrier negotiation (server -> client)
 *
 * Each frame: [type:1][flags:1][stream_id:4][length:4][payload...]
 *
 * Supports two transport modes:
 * - HTTPS: Custom frame protocol over TLS (long-polling)
 * - WebSocket: Custom frame protocol over WebSocket (RFC 6455)
 */
class WebProxyStream
{
    /** Frame type constants */
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

    public const FLAG_FIN = 0x01; // End of stream (for DATA frames)

    /** Transport modes */
    public const MODE_HTTPS = 'https';

    public const MODE_WEBSOCKET = 'websocket';

    /** Default flow control window (4 MiB) */
    public const DEFAULT_WINDOW = 4 * 1024 * 1024;

    /** Maximum frame payload size (1 MiB) */
    public const MAX_FRAME_SIZE = 1024 * 1024;

    private $socket;

    private LoggerInterface $logger;

    private string $mode;

    private int $nextStreamId = 1;

    private array $streamWindows = [];

    private array $streamBuffers = [];

    private array $streamClosed = [];

    private bool $closed = false;

    /**
     * @param  resource  $socket  Connected carrier socket (after HELLO/WELCOME handshake)
     * @param  string  $mode  Transport mode: 'https' or 'websocket'
     */
    public function __construct($socket, ?LoggerInterface $logger = null, string $mode = self::MODE_HTTPS)
    {
        $this->socket = $socket;
        $this->logger = $logger ?? new NullLogger;
        $this->mode = $mode;
    }

    /**
     * Open a new logical stream for MTProto traffic.
     *
     * @return int Stream ID assigned to this logical stream
     */
    public function openStream(): int
    {
        if ($this->closed) {
            throw new RuntimeException('WebProxyStream: connection is closed');
        }

        $streamId = $this->nextStreamId++;
        $this->streamWindows[$streamId] = self::DEFAULT_WINDOW;
        $this->streamBuffers[$streamId] = '';
        $this->streamClosed[$streamId] = false;

        // Send OPEN frame
        $this->sendFrame(self::FRAME_OPEN, self::FLAG_NONE, $streamId, '');

        $this->logger->debug('teleframe.web_proxy.stream.opened', [
            'stream_id' => $streamId,
        ]);

        return $streamId;
    }

    /**
     * Write data to a logical stream.
     *
     * @param  int  $streamId  Logical stream ID from openStream()
     * @param  string  $data  Data to send
     * @param  bool  $fin  Whether this is the final data for this stream
     */
    public function writeStream(int $streamId, string $data, bool $fin = false): void
    {
        if ($this->closed) {
            throw new RuntimeException('WebProxyStream: connection is closed');
        }
        if (! isset($this->streamWindows[$streamId])) {
            throw new RuntimeException("WebProxyStream: unknown stream ID {$streamId}");
        }
        if ($this->streamClosed[$streamId]) {
            throw new RuntimeException("WebProxyStream: stream {$streamId} is already closed");
        }

        $flags = $fin ? self::FLAG_FIN : self::FLAG_NONE;
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            // Wait for window space
            $this->waitForWindow($streamId);

            $chunkSize = min(self::MAX_FRAME_SIZE, $length - $offset, $this->streamWindows[$streamId]);
            $chunk = substr($data, $offset, $chunkSize);

            $isLastChunk = ($offset + $chunkSize >= $length);
            $chunkFlags = ($isLastChunk && $fin) ? self::FLAG_FIN : self::FLAG_NONE;

            $this->sendFrame(self::FRAME_DATA, $chunkFlags, $streamId, $chunk);

            $this->streamWindows[$streamId] -= $chunkSize;
            $offset += $chunkSize;
        }
    }

    /**
     * Read data from a logical stream.
     *
     * @param  int  $streamId  Logical stream ID
     * @param  int  $length  Maximum bytes to read
     * @return string Data read (may be less than $length if stream ends)
     */
    public function readStream(int $streamId, int $length): string
    {
        if ($this->closed) {
            throw new RuntimeException('WebProxyStream: connection is closed');
        }
        if (! isset($this->streamBuffers[$streamId])) {
            throw new RuntimeException("WebProxyStream: unknown stream ID {$streamId}");
        }

        // Fill buffer until we have data or stream is closed
        while ($this->streamBuffers[$streamId] === '' && ! $this->streamClosed[$streamId]) {
            $this->processIncomingFrames($streamId);
        }

        if ($this->streamClosed[$streamId]) {
            return ''; // EOF
        }

        $data = substr($this->streamBuffers[$streamId], 0, $length);
        $this->streamBuffers[$streamId] = substr($this->streamBuffers[$streamId], strlen($data));

        // Send WINDOW update for bytes consumed
        if (strlen($data) > 0) {
            $this->sendWindowUpdate($streamId, strlen($data));
        }

        return $data;
    }

    /**
     * Read exactly $length bytes from a logical stream.
     */
    public function readStreamExact(int $streamId, int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length) {
            $chunk = $this->readStream($streamId, $length - strlen($buffer));
            if ($chunk === '') {
                throw new RuntimeException('WebProxyStream: EOF while reading exact');
            }
            $buffer .= $chunk;
        }

        return $buffer;
    }

    /**
     * Close a logical stream.
     */
    public function closeStream(int $streamId): void
    {
        if (! isset($this->streamWindows[$streamId])) {
            return; // Already closed or unknown
        }

        if (! $this->streamClosed[$streamId]) {
            $this->sendFrame(self::FRAME_CLOSE, self::FLAG_NONE, $streamId, '');
            $this->streamClosed[$streamId] = true;

            $this->logger->debug('teleframe.web_proxy.stream.closed', [
                'stream_id' => $streamId,
            ]);
        }

        unset($this->streamWindows[$streamId], $this->streamBuffers[$streamId], $this->streamClosed[$streamId]);
    }

    /**
     * Send a PING frame for keepalive.
     */
    public function ping(): void
    {
        if ($this->closed) {
            return;
        }
        $this->sendFrame(self::FRAME_PING, self::FLAG_NONE, 0, '');
    }

    /**
     * Close the entire WEB proxy connection.
     */
    public function close(): void
    {
        if (! $this->closed) {
            $this->sendFrame(self::FRAME_BYE, self::FLAG_NONE, 0, '');
            $this->closed = true;

            $this->logger->debug('teleframe.web_proxy.connection.closed', []);
        }
    }

    /**
     * Process incoming frames until a specific stream has data or is closed.
     *
     * @param  int|null  $targetStreamId  If set, process until this stream has data
     */
    private function processIncomingFrames(?int $targetStreamId = null): void
    {
        while (true) {
            $frame = $this->readFrame();
            if ($frame === null) {
                // Connection closed
                $this->closed = true;
                foreach (array_keys($this->streamBuffers) as $sid) {
                    $this->streamClosed[$sid] = true;
                }

                return;
            }

            $this->handleFrame($frame);

            if ($targetStreamId !== null && isset($this->streamBuffers[$targetStreamId]) && $this->streamBuffers[$targetStreamId] !== '') {
                return;
            }
        }
    }

    /**
     * Read a single frame from the socket.
     *
     * @return array{type: int, flags: int, stream_id: int, length: int, payload: string}|null
     */
    private function readFrame(): ?array
    {
        if ($this->mode === self::MODE_WEBSOCKET) {
            return $this->readWebSocketFrame();
        }

        // Read frame header (10 bytes: type, flags, stream_id, length)
        $header = StreamSocket::readExact($this->socket, 10);
        if ($header === '') {
            return null;
        }

        $type = ord($header[0]);
        $flags = ord($header[1]);
        $streamId = unpack('V', substr($header, 2, 4))[1];
        $length = unpack('V', substr($header, 6, 4))[1];

        if ($length > self::MAX_FRAME_SIZE) {
            throw new RuntimeException("WebProxyStream: frame too large ({$length} > ".self::MAX_FRAME_SIZE.')');
        }

        $payload = '';
        if ($length > 0) {
            $payload = StreamSocket::readExact($this->socket, $length);
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
     * Read a WebSocket frame and extract the inner custom frame.
     *
     * @return array{type: int, flags: int, stream_id: int, length: int, payload: string}|null
     */
    private function readWebSocketFrame(): ?array
    {
        $wsFrame = WebSocketFrame::decode($this->socket);
        if ($wsFrame === null) {
            return null;
        }

        $opcode = $wsFrame['opcode'];
        $payload = $wsFrame['payload'];

        // Handle WebSocket control frames
        if ($opcode === WebSocketFrame::OP_CLOSE) {
            $this->closed = true;
            foreach (array_keys($this->streamBuffers) as $sid) {
                $this->streamClosed[$sid] = true;
            }
            // Send close response
            $closeFrame = WebSocketFrame::buildCloseFrame(WebSocketFrame::CLOSE_NORMAL);
            StreamSocket::write($this->socket, $closeFrame);

            return null;
        }

        if ($opcode === WebSocketFrame::OP_PING) {
            // Respond with PONG
            $pongFrame = WebSocketFrame::buildPongFrame($payload);
            StreamSocket::write($this->socket, $pongFrame);

            return $this->readWebSocketFrame(); // Read next frame
        }

        if ($opcode === WebSocketFrame::OP_PONG) {
            // Pong received - keepalive OK
            return $this->readWebSocketFrame(); // Read next frame
        }

        if ($opcode !== WebSocketFrame::OP_BINARY && $opcode !== WebSocketFrame::OP_CONTINUATION) {
            $this->logger->warning('teleframe.web_proxy.websocket.unexpected_opcode', [
                'opcode' => '0x'.dechex($opcode),
            ]);

            return $this->readWebSocketFrame();
        }

        // Extract inner custom frame from WebSocket binary payload
        if ($payload === '') {
            return $this->readWebSocketFrame(); // Empty frame, read next
        }

        // Parse inner frame (same format as HTTPS mode)
        if (strlen($payload) < 10) {
            throw new RuntimeException('WebProxyStream: WebSocket frame payload too small for inner frame');
        }

        $header = substr($payload, 0, 10);
        $innerPayload = substr($payload, 10);

        $type = ord($header[0]);
        $flags = ord($header[1]);
        $streamId = unpack('V', substr($header, 2, 4))[1];
        $length = unpack('V', substr($header, 6, 4))[1];

        if ($length > self::MAX_FRAME_SIZE) {
            throw new RuntimeException("WebProxyStream: frame too large ({$length} > ".self::MAX_FRAME_SIZE.')');
        }

        if (strlen($innerPayload) !== $length) {
            throw new RuntimeException("WebProxyStream: inner frame length mismatch (expected {$length}, got ".strlen($innerPayload).')');
        }

        return [
            'type' => $type,
            'flags' => $flags,
            'stream_id' => $streamId,
            'length' => $length,
            'payload' => $innerPayload,
        ];
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
            case self::FRAME_DATA:
                if (! isset($this->streamBuffers[$streamId])) {
                    // Unknown stream - ignore or log
                    $this->logger->warning('teleframe.web_proxy.frame.unknown_stream', [
                        'stream_id' => $streamId,
                        'type' => 'DATA',
                    ]);

                    return;
                }
                $this->streamBuffers[$streamId] .= $payload;
                if ($flags & self::FLAG_FIN) {
                    $this->streamClosed[$streamId] = true;
                }
                break;

            case self::FRAME_CLOSE:
                if (isset($this->streamClosed[$streamId])) {
                    $this->streamClosed[$streamId] = true;
                }
                break;

            case self::FRAME_WINDOW:
                if (isset($this->streamWindows[$streamId])) {
                    $windowIncrement = unpack('V', $payload)[1] ?? 0;
                    $this->streamWindows[$streamId] += $windowIncrement;
                }
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
                foreach (array_keys($this->streamBuffers) as $sid) {
                    $this->streamClosed[$sid] = true;
                }
                break;

            default:
                $this->logger->warning('teleframe.web_proxy.frame.unknown_type', [
                    'type' => '0x'.dechex($type),
                    'stream_id' => $streamId,
                ]);
        }
    }

    /**
     * Wait for flow control window space on a stream.
     */
    private function waitForWindow(int $streamId): void
    {
        $deadline = microtime(true) + 30.0; // 30 second timeout

        while ($this->streamWindows[$streamId] <= 0) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException("WebProxyStream: flow control timeout on stream {$streamId}");
            }

            // Process incoming frames to get WINDOW updates
            $this->processIncomingFrames($streamId);

            if ($this->streamClosed[$streamId]) {
                throw new RuntimeException("WebProxyStream: stream {$streamId} closed while waiting for window");
            }

            usleep(1000); // 1ms backoff
        }
    }

    /**
     * Send a WINDOW update frame.
     */
    private function sendWindowUpdate(int $streamId, int $bytes): void
    {
        $payload = pack('V', $bytes);
        $this->sendFrame(self::FRAME_WINDOW, self::FLAG_NONE, $streamId, $payload);
    }

    /**
     * Send a frame on the carrier connection.
     */
    private function sendFrame(int $type, int $flags, int $streamId, string $payload): void
    {
        $frame = chr($type & 0xFF)
            .chr($flags & 0xFF)
            .pack('V', $streamId)
            .pack('V', strlen($payload))
            .$payload;

        if ($this->mode === self::MODE_WEBSOCKET) {
            // Wrap in WebSocket binary frame
            $wsFrame = WebSocketFrame::encode($frame, WebSocketFrame::OP_BINARY, true, true);
            StreamSocket::write($this->socket, $wsFrame);
        } else {
            StreamSocket::write($this->socket, $frame);
        }
    }

    /**
     * Check if the connection is closed.
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Get the underlying socket resource.
     */
    public function getSocket()
    {
        return $this->socket;
    }
}
