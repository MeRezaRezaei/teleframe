<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

use RuntimeException;

/**
 * Single-connection obfuscated2 relay terminal for BROWSER clients — the
 * WebSocket half of the control layer's transport scaffold (raw TCP is
 * RelayServer; wss://…/apiws is this class).
 *
 * The vendored GramJS client cannot dial raw TCP: it upgrades to
 * WebSocket (`PromisedWebSockets` → wss://host/port/apiws, RFC6455) and runs
 * its obfuscated2 transport INSIDE binary messages. This terminal performs
 * the server side of that stack:
 *
 *   1. RFC6455 opening handshake (validates the upgrade request, answers 101
 *      with the computed Sec-WebSocket-Accept).
 *   2. Reads the client's obfuscated2 64-byte header from the binary-message
 *      byte stream, routes on the little-endian int16 DC id at decrypted
 *      bytes 60-62 (falling back to the configured default DC — the vendored
 *      tgweb client writes no dc id, see RelayServer).
 *   3. Pumps a bidirectional byte stream: client binary-message payloads are
 *      decrypted with the from-client key pair before they are written
 *      upstream; upstream plaintext is encrypted with the to-client pair and
 *      re-framed as binary messages. Control frames (ping/pong/close) are
 *      answered per RFC6455 without touching the obfuscated2 stream.
 *
 * WebSocket frames are a byte-STREAM carrier: the relay concatenates every
 * data-frame payload into one wire stream (the client does the same on its
 * read side), so transport framing (abridged/intermediate/secure) is
 * preserved verbatim on both legs, exactly as in RelayServer.
 *
 * Auth (W-02): the raw RFC6455 upgrade authenticates nobody, so a vault
 * terminal deployed behind it must gate the socket. When a $ticketValidator
 * is configured the relay REQUIRES the client to present a one-shot relay
 * ticket (minted by POST accounts/{id}/ticket, consumed by the
 * RelayTicketBroker) during the HTTP upgrade — via an
 * `Authorization: Bearer <ticket>` header or a `?ticket=<ticket>` query
 * param on the request line. The ticket is redeemed exactly once (one-shot
 * per socket session), the socket is bound to the granted session, and any
 * client without a valid ticket is rejected with an HTTP 401 protocol error
 * before the 101 upgrade. The granted session rides through to the answerer
 * as its 6th argument so a socket can only reach a terminal that validated
 * a ticket.
 *
 * Grant shape: a validator may return a bare account id (the historical
 * shape) or an identity array `{account_id, user_id}`. Whatever the
 * validator returns is passed through UNCHANGED as the answerer's 6th
 * argument — only the 401 gate normalizes it — so terminals that record the
 * raw grant (and their tests) keep byte-for-byte behaviour while
 * identity-bearing arrays still reach the terminal untouched.
 *
 * Silent by design — nothing is logged and invalid clients are simply
 * closed. Same ctor shape as RelayServer so tests can point the upstream
 * resolver at loopback peers.
 */
class WebSocketRelayServer
{
    /** @var callable(int, string): resource */
    private readonly mixed $upstreamResolver;

    private readonly ?int $defaultDcId;

    /** @var callable(resource, Obfuscated2Cipher, string, int, string, int|array{account_id: int, user_id: int|string|null}|null): void|null */
    private readonly mixed $answerer;

    /** @var callable(string): (int|array{account_id: int, user_id: int|string|null}|null)|null */
    private readonly mixed $ticketValidator;

    /**
     * @param  string  $listenHost  bind host (loopback by default)
     * @param  int  $port  bind port; 0 asks the OS for an ephemeral port
     * @param  callable(int $dcId, string $protocol): resource|null|null  $upstreamResolver
     *                                                                                       injectable upstream connector (tests supply a loopback peer);
     *                                                                                       null resolves to the real dc{id}.telegram.org:443 host
     * @param  int|null  $defaultDcId  fallback DC for clients that leave bytes
     *                                 60-62 of the header random (the vendored
     *                                 Telegram Web client writes no dc id); null
     *                                 disables the fallback and out-of-range DCs
     *                                 are rejected
     * @param  callable(resource $client, Obfuscated2Cipher $cipher, string $lead, int $dcId, string $protocol, int|array{account_id: int, user_id: int|string|null}|null $ticketGrant): void|null  $answerer
     *                                                                                                                                                                                                         optional terminal seam: when set, a parsed
     *                                                                                                                                                                                                         client is handed to the callable INSTEAD of
     *                                                                                                                                                                                                         dialing an upstream and pumping bytes. A 6th
     *                                                                                                                                                                                                         optional arg carries the ticket grant: a bare
     *                                                                                                                                                                                                         account id (legacy validators) or an identity
     *                                                                                                                                                                                                         array {account_id, user_id} (null = no
     *                                                                                                                                                                                                         validator configured) — passed through
     *                                                                                                                                                                                                         UNCHANGED from the validator. The callable
     *                                                                                                                                                                                                         owns the connection (build a
     *                                                                                                                                                                                                         WebSocketObfuscated2Link from the cipher +
     *                                                                                                                                                                                                         lead), and the client socket is closed on
     *                                                                                                                                                                                                         its return. null keeps the byte-proxy path.
     * @param  callable(string $ticket): (int|array{account_id: int, user_id: int|string|null}|null)|null  $ticketValidator
     *                                                                                                                       optional auth gate (W-02): when set, every socket
     *                                                                                                                       must present a one-shot relay ticket during the HTTP
     *                                                                                                                       upgrade (Authorization Bearer or ?ticket=); the
     *                                                                                                                       callable redeems the ticket and returns the granted
     *                                                                                                                       session — a bare account id, or an identity array
     *                                                                                                                       {account_id, user_id} to also carry the Laravel
     *                                                                                                                       user — or null to reject. One-shot, session-
     *                                                                                                                       bound: the ticket dies with the first use and the
     *                                                                                                                       granted socket keeps its session. null disables the
     *                                                                                                                       gate (byte-proxy paths).
     */
    public function __construct(
        private readonly string $listenHost = '127.0.0.1',
        private readonly int $port = 443,
        ?callable $upstreamResolver = null,
        ?int $defaultDcId = null,
        ?callable $answerer = null,
        ?callable $ticketValidator = null,
    ) {
        $this->upstreamResolver = $upstreamResolver ?? static function (int $dcId, string $protocol) {
            $socket = @stream_socket_client("tcp://dc{$dcId}.telegram.org:443", $errno, $errstr, 3.0);
            if ($socket === false) {
                return null;
            }
            // Terminated client framing must reach the DC as plain abridged
            // (FrameCodec::writeInit parity) — the sole init byte.
            @fwrite($socket, "\xef");

            return $socket;
        };
        $this->defaultDcId = $defaultDcId;
        $this->answerer = $answerer;
        $this->ticketValidator = $ticketValidator;
    }

    /** @return resource a listening TCP server socket */
    public function listen()
    {
        $server = @stream_socket_server("tcp://{$this->listenHost}:{$this->port}", $errno, $errstr);
        if ($server === false) {
            throw new RuntimeException("WebSocketRelayServer: listen failed on {$this->listenHost}:{$this->port}: {$errstr} ({$errno})");
        }

        return $server;
    }

    /**
     * Accept and terminate one browser client connection to completion.
     * Invalid clients (refused handshake, bad header, refused upstream,
     * premature close) are dropped silently; the loop keeps living.
     *
     * @param  resource  $server  the listening socket from listen()
     * @param  float  $acceptTimeoutSeconds  stream_socket_accept timeout
     *                                       (a short tick lets stop-flag loops poll)
     */
    public function serveOnce($server, float $acceptTimeoutSeconds = 30): void
    {
        $client = @stream_socket_accept($server, $acceptTimeoutSeconds);
        if (! is_resource($client)) {
            return;
        }

        $upstream = null;
        try {
            // --- RFC6455 opening handshake -----------------------------------
            // parseHandshake returns the client's Sec-WebSocket-Key plus the
            // negotiated subprotocol (the `binary` subprotocol MTProto's
            // WebSocket transport requires browsers to offer); the server
            // echoes both so the browser accepts the upgrade.
            $request = $this->readHandshakeRequest($client);
            $handshake = WebSocketFrameCodec::parseHandshake($request);
            if ($handshake === null) {
                // Not an RFC6455 upgrade: close with no response, the
                // historical relay behavior on the relay port.
                return;
            }

            // --- Auth gate (W-02) ---------------------------------------------
            // The RFC6455 upgrade validates no caller identity, so a configured
            // ticketValidator makes the ticket the entrance fee: the client must
            // present a one-shot relay ticket (Authorization Bearer or ?ticket=
            // on the request line) and the validator must redeem it. One-shot per
            // socket session: a ticket spent here is gone for every other socket,
            // and the granted session is bound to THIS socket for its lifetime.
            // Missing/invalid tickets get an HTTP 401 protocol error before the
            // 101 upgrade ever leaves the server. The 401 gate only needs the
            // account id, so the grant — which may be an identity array
            // {account_id, user_id} — is normalized for the check; the original
            // value is what rides through to the answerer untouched.
            $grant = null;
            if ($this->ticketValidator !== null) {
                $ticket = self::extractTicket($request);
                $grant = $ticket === null ? null : ($this->ticketValidator)($ticket);
                $grantAccountId = is_array($grant) ? $grant['account_id'] : $grant;
                if (! is_int($grantAccountId) || $grantAccountId < 1) {
                    self::write401($client);

                    return;
                }
            }

            self::writeAll($client, WebSocketFrameCodec::handshakeResponse($handshake['key'], $handshake['subprotocol']));

            // --- obfuscated2 header over the WS byte stream ------------------
            // $carry holds partial-frame wire bytes; $stream accumulates the
            // decoded data-frame payloads (the obfuscated2 byte stream).
            $carry = '';
            $stream = '';
            while (strlen($stream) < Obfuscated2Codec::HEADER_SIZE) {
                $chunk = $this->readClientPump($client, $carry);
                if ($chunk === null) {
                    return; // client closed mid-header (or sent a close frame)
                }
                $stream .= $chunk;
            }

            $header = substr($stream, 0, Obfuscated2Codec::HEADER_SIZE);
            $parsed = Obfuscated2Codec::parseHeader($header);

            $dcId = $this->resolveDcId($parsed['dc_id']);
            if ($dcId === null) {
                return;
            }

            $cipher = Obfuscated2Cipher::fromParsedHeader($parsed);
            $lead = substr($stream, Obfuscated2Codec::HEADER_SIZE);

            if ($this->answerer !== null) {
                // Terminal seam: an answerer (e.g. VaultMtprotoAnswerer)
                // consumes the plaintext duplex directly and never dials an
                // upstream — stock clients keep the byte-proxy path below. The
                // ticket grant (account id, or identity array when a validator
                // returned one) rides in for session binding.
                ($this->answerer)($client, $cipher, $lead, $dcId, $parsed['protocol'], $grant);

                return;
            }

            $upstream = ($this->upstreamResolver)($dcId, $parsed['protocol']);
            if (! is_resource($upstream)) {
                return;
            }

            $this->pump($client, $upstream, $cipher, $lead);
        } catch (Obfuscated2Exception|RuntimeException) {
            // Invalid or interrupted client; nothing to log by design.
        } finally {
            if (is_resource($upstream)) {
                fclose($upstream);
            }
            if (is_resource($client)) {
                fclose($client);
            }
        }
    }

    /** Serve clients forever until the process is stopped. */
    public function acceptLoop(): void
    {
        $server = $this->listen();
        try {
            for (; ;) {
                $this->serveOnce($server);
            }
        } finally {
            fclose($server);
        }
    }

    /**
     * Reads the HTTP handshake request (up to the blank-line terminator).
     *
     * @param  resource  $client
     */
    private function readHandshakeRequest($client): string
    {
        $buffer = '';
        while (! str_contains($buffer, "\r\n\r\n")) {
            $chunk = @fread($client, 8192);
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('WebSocketRelayServer: client closed during the handshake');
            }
            $buffer .= $chunk;
            if (strlen($buffer) > 16 * 1024) {
                throw new RuntimeException('WebSocketRelayServer: handshake request too large');
            }
        }

        return $buffer;
    }

    /**
     * Pulls the client's one-shot relay ticket out of a raw HTTP upgrade
     * request. The `?ticket=` query param wins when present; ONLY then does an
     * `Authorization: Bearer <ticket>` header count. The order matters: the
     * browser's seed peek and WS dial carry the relay ticket in the query
     * string while ALSO sending the app's Sanctum bearer in Authorization —
     * preferring the header would hand the relay the wrong credential when
     * both are present. For a request that names only one of the two, either
     * channel is accepted. String functions only — zero regex, per the
     * src/Core discipline.
     *
     * @return string|null null when the request carries no ticket
     */
    private static function extractTicket(string $request): ?string
    {
        $parts = explode(' ', $request);
        $target = (string) ($parts[1] ?? '');
        $queryAt = strpos($target, '?');
        if ($queryAt !== false) {
            foreach (explode('&', (string) substr($target, $queryAt + 1)) as $pair) {
                if (str_starts_with($pair, 'ticket=')) {
                    $ticket = (string) substr($pair, 7);
                    if ($ticket !== '') {
                        return $ticket;
                    }
                }
            }
        }

        $lines = explode("\r\n", $request);

        foreach (array_slice($lines, 1) as $line) {
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }
            if (strtolower(trim(substr($line, 0, $colon))) !== 'authorization') {
                continue;
            }
            $value = trim(substr($line, $colon + 1));
            if (str_starts_with(strtolower($value), 'bearer ')) {
                $ticket = trim(substr($value, 7));
                if ($ticket !== '') {
                    return $ticket;
                }
            }
        }

        return null;
    }

    /**
     * Writes the shared protocol-error rejection: HTTP 401, zero-length body,
     * connection closed. Used by the WS-dial ticket gate.
     *
     * @param  resource  $socket
     */
    private static function write401($socket): void
    {
        self::writeAll($socket, "HTTP/1.1 401 Unauthorized\r\n"
            ."Access-Control-Allow-Origin: *\r\n"
            ."Connection: close\r\nContent-Length: 0\r\n\r\n");
    }

    /**
     * One read pass over the client's WS byte stream: drains every complete
     * frame in the socket buffer, answers control frames, and returns the
     * concatenated data-frame payload newly available (or null on close).
     *
     * @param  resource  $client
     * @param  string  $carry  unconsumed partial-frame bytes (in/out)
     */
    private function readClientPump($client, string &$carry): ?string
    {
        $chunk = @fread($client, 8192);
        if ($chunk === false || $chunk === '') {
            return null;
        }
        $carry .= $chunk;

        return $this->drainFrames($client, $carry);
    }

    /**
     * Decodes every complete frame from $carry (in/out), answering control
     * frames. Returns the concatenation of the data-frame payloads, or null
     * when a close frame was received (the caller should tear down).
     *
     * @param  resource  $client
     */
    private function drainFrames($client, string &$carry): ?string
    {
        $out = '';
        for (; ;) {
            $frame = WebSocketFrameCodec::parseFrame($carry);
            if ($frame === null) {
                return $out; // incomplete frame — wait for more bytes
            }
            $carry = substr($carry, self::frameLength($frame));
            $opcode = $frame['opcode'];

            if ($opcode === WebSocketFrameCodec::OPCODE_PING) {
                self::writeAll($client, WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_PONG, $frame['payload']));

                continue;
            }
            if ($opcode === WebSocketFrameCodec::OPCODE_CLOSE) {
                return null; // RFC6455 close handshake initiator → close our side
            }
            if (
                $opcode === WebSocketFrameCodec::OPCODE_BINARY
                || $opcode === WebSocketFrameCodec::OPCODE_CONTINUATION
            ) {
                $out .= $frame['payload'];

                continue;
            }
            // TEXT (0x1) and PONG (0xA) frames carry no wire data — ignore.
        }
    }

    /** @param array{opcode: int, payload: string, masked: bool} $frame */
    private static function frameLength(array $frame): int
    {
        $len = strlen($frame['payload']);
        $header = 2;
        if ($len >= 126 && $len <= 0xFFFF) {
            $header += 2;
        } elseif ($len > 0xFFFF) {
            $header += 8;
        }

        return $header + strlen($frame['payload']) + ($frame['masked'] ? 4 : 0);
    }

    /**
     * Header-decoded DC ids out of the accepted range (or absent, when the
     * client left bytes 60-62 random) fall back to the configured default
     * DC; without one, null rejects the connection.
     */
    private function resolveDcId(int $headerDcId): ?int
    {
        if ($headerDcId >= 1 && $headerDcId <= 150) {
            return $headerDcId;
        }

        return $this->defaultDcId;
    }

    /**
     * Bidirectional byte-stream pump: client WS data frames decrypt → write
     * upstream; upstream plaintext encrypts → re-frame as WS binary
     * messages. Mirrors RelayServer::pump (stream_select, 1s tick, write
     * halves shut down on the peer's EOF) but the client leg carries
     * RFC6455 framing — the decrypt boundary lives in
     * WebSocketObfuscated2Link (readPlain/writePlain), so the byte-proxy and
     * the answerer share one implementation.
     *
     * @param  resource  $client  WebSocket client socket (post-handshake)
     * @param  resource  $upstream  already-connected upstream TCP socket
     * @param  string  $lead  payload bytes already read with the header
     */
    public function pump($client, $upstream, Obfuscated2Cipher $cipher, string $lead = ''): void
    {
        stream_set_blocking($client, true);
        stream_set_blocking($upstream, true);

        $link = new WebSocketObfuscated2Link($client, $cipher, $lead);

        $clientAlive = true;
        $upstreamAlive = true;

        while ($clientAlive || $upstreamAlive) {
            $read = [];
            if ($clientAlive) {
                $read[] = $client;
            }
            if ($upstreamAlive) {
                $read[] = $upstream;
            }

            $write = null;
            $except = null;
            $ready = @stream_select($read, $write, $except, 1, 0);
            if ($ready === false) {
                break;
            }
            if ($ready === 0) {
                continue;
            }

            foreach ($read as $socket) {
                if ($socket === $client) {
                    if (! $clientAlive) {
                        continue;
                    }
                    // stream_select guarantees readability; timeout 0 keeps
                    // the link non-blocking ('' = partial frame, keep going;
                    // null = WS close frame / EOF → half-close upstream).
                    $plain = $link->readPlain(0);
                    if ($plain === null) {
                        $clientAlive = false;
                        @stream_socket_shutdown($upstream, STREAM_SHUT_WR);

                        continue;
                    }
                    if ($plain !== '') {
                        self::writeAll($upstream, $plain);
                    }
                } else {
                    if (! $upstreamAlive) {
                        continue;
                    }
                    $data = @fread($upstream, 8192);
                    if ($data === false || $data === '') {
                        $upstreamAlive = false;
                        @stream_socket_shutdown($client, STREAM_SHUT_WR);

                        continue;
                    }
                    $link->writePlain($data);
                }
            }
        }

        $link->close();
        fclose($upstream);
    }

    /**
     * @param  resource  $socket
     */
    private static function writeAll($socket, string $bytes): void
    {
        $total = strlen($bytes);
        $written = 0;
        while ($written < $total) {
            $n = @fwrite($socket, substr($bytes, $written));
            if ($n === false || $n === 0) {
                throw new RuntimeException('WebSocketRelayServer: write to peer failed');
            }
            $written += $n;
        }
    }
}
