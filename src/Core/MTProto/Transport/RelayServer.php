<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Single-connection obfuscated2 relay server — one half of the control
 * layer's transport scaffold (the other, /apiws, serves the browser).
 *
 * Terminates a stock MTProxy-style client: reads the 64-byte obfuscated2
 * header (no secret, "accept" mode), routes on the little-endian int16 DC
 * id at decrypted bytes 60-62 (falling back to the configured default DC
 * when the client wrote none), then
 * pumps a bidirectional byte stream between the client and an upstream TCP
 * peer. Client ciphertext is decrypted with the from-client key pair before
 * it is written upstream; upstream plaintext is encrypted with the
 * to-client pair before it is written back. Framing travels through
 * untouched — the client's chosen abridged/intermediate/secure framing is
 * preserved verbatim on both legs.
 *
 * The upstream peer is resolved by an injected callable (default: the real
 * dc{id}.telegram.org:443 host) so tests can point the relay at a local
 * loopback peer. Silent by design — nothing is logged and invalid clients
 * are simply closed.
 *
 * Auth (W-10): the raw TCP leg has NO caller identity, so binding it to a
 * non-loopback interface without a shared secret (e.g. TF_RELAY_TCP_SECRET)
 * just opens an anonymous proxying endpoint. When $acceptSecret is set, a
 * client must first send the acceptance token (see acceptToken) as the
 * opening bytes of the connection — a pre-header HMAC challenge that is
 * checked before any obfuscated2 header is read or any byte is proxied.
 * listen() additionally refuses to bind non-loopback hosts when no secret
 * is configured: an explicit internet-facing bind must come with the guard,
 * or the relay fails loudly instead of silently exposing an open proxy.
 *
 * Sponsor channel (MTProto proxy promotion): when $sponsorChannel is set,
 * the server advertises the channel to connecting clients. The channel
 * username (e.g., '@mychannel') is included in the proxy configuration
 * that clients receive, allowing Telegram apps to display it as a sponsored
 * channel in proxy settings.
 */
class RelayServer
{
    /** Raw-bytes length of the acceptance token (64 hex chars). */
    public const AUTH_TOKEN_BYTES = 64;

    /** @var callable(int, string): resource */
    private readonly mixed $upstreamResolver;

    private readonly ?int $defaultDcId;

    private readonly ?string $acceptSecret;

    private readonly ?string $sponsorChannel;

    private readonly LoggerInterface $logger;

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
     * @param  string|null  $acceptSecret  optional shared-secret guard (W-10):
     *                                     when set, serveOnce demands the
     *                                     acceptance token (acceptToken) as the
     *                                     connection's opening bytes before
     *                                     touching the obfuscated2 header; when
     *                                     null the raw leg accepts like a classic
     *                                     MTProxy "accept" mode. listen() refuses
     *                                     non-loopback binds without a secret.
     * @param  string|null  $sponsorChannel  optional sponsor channel username
     *                                       (e.g., '@mychannel') to advertise
     *                                       to connecting clients
     * @param  LoggerInterface|null  $logger  PSR-3 logger (NullLogger default —
     *                                        the engine stays silent unconfigured)
     */
    public function __construct(
        private readonly string $listenHost = '127.0.0.1',
        private readonly int $port = 443,
        ?callable $upstreamResolver = null,
        ?int $defaultDcId = null,
        ?string $acceptSecret = null,
        ?string $sponsorChannel = null,
        ?LoggerInterface $logger = null,
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
        $this->acceptSecret = $acceptSecret;
        $this->sponsorChannel = $sponsorChannel;
        $this->logger = $logger ?? new NullLogger;
    }

    /**
     * Get the sponsor channel username.
     */
    public function getSponsorChannel(): ?string
    {
        return $this->sponsorChannel;
    }

    /**
     * The shared-secret acceptance token a client must send as the opening
     * bytes of the connection when the relay runs with an acceptSecret.
     * Deterministic from the secret, so the daemon and its configured
     * clients agree without any key exchange: HMAC-SHA256 over a fixed
     * purpose string, hex-encoded to exactly AUTH_TOKEN_BYTES characters.
     *
     * Null bytes on the wire are avoided by the hex encoding, and the
     * comparison at serveOnce is timing-safe (hash_equals).
     */
    public static function acceptToken(string $secret): string
    {
        return hash_hmac('sha256', 'teleframe-relay-accept-v1', $secret);
    }

    /**
     * True for hosts that cannot be reached from another machine: the IPv4
     * loopback range, IPv6 loopback, and the `localhost` alias. Every other
     * host (including the wildcard binds) is potentially internet-facing.
     */
    public static function isLoopbackHost(string $host): bool
    {
        $host = strtolower(trim($host));
        if ($host === 'localhost' || $host === '::1') {
            return true;
        }

        return str_starts_with($host, '127.');
    }

    /** @return resource a listening TCP server socket */
    public function listen()
    {
        // W-10: an explicit non-loopback bind without a shared secret would
        // be an anonymous open proxy. Refuse it loudly rather than serving.
        if ($this->acceptSecret === null && ! self::isLoopbackHost($this->listenHost)) {
            $this->logger->warning('teleframe.relay.refused.non-loopback-bind-without-secret');

            throw new RuntimeException("RelayServer: refusing non-loopback bind {$this->listenHost}:{$this->port} without an accept secret (set TF_RELAY_TCP_SECRET / teleframe.relay.tcp_secret)");
        }

        $server = @stream_socket_server("tcp://{$this->listenHost}:{$this->port}", $errno, $errstr);
        if ($server === false) {
            throw new RuntimeException("RelayServer: listen failed on {$this->listenHost}:{$this->port}: {$errstr} ({$errno})");
        }

        return $server;
    }

    /**
     * Accept and terminate one client connection to completion. Invalid
     * clients (missing/bad acceptance token, bad header, refused upstream,
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
            // W-10: with an acceptSecret configured, the opening bytes must be
            // the acceptance token, checked BEFORE any obfuscated2 header is
            // parsed or any byte is proxied upstream. A missing or invalid
            // token drops the connection exactly like a bad header.
            if ($this->acceptSecret !== null) {
                $token = self::readExact($client, self::AUTH_TOKEN_BYTES);
                if (! hash_equals(self::acceptToken($this->acceptSecret), $token)) {
                    return;
                }
            }

            $header = self::readExact($client, Obfuscated2Codec::HEADER_SIZE);
            $parsed = Obfuscated2Codec::parseHeader($header);

            $dcId = $this->resolveDcId($parsed['dc_id']);
            if ($dcId === null) {
                return;
            }

            $upstream = ($this->upstreamResolver)($dcId, $parsed['protocol']);
            if (! is_resource($upstream)) {
                return;
            }

            $cipher = Obfuscated2Cipher::fromParsedHeader($parsed);
            $this->pump(
                $client,
                static fn (string $bytes): string => $cipher->xcrypt($bytes, true),
                $upstream,
                static fn (string $bytes): string => $cipher->xcrypt($bytes, false),
            );
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
     * Bidirectional byte-stream pump between the client and the upstream
     * peer. Uses stream_select with a 1s tick so both halves stay alive
     * while sockets are open; writes are fully drained before returning.
     * On EOF of one side the other side's write half is shut down and the
     * remaining read half is drained until both close.
     *
     * @param  resource  $client  terminated obfuscated2 client socket
     * @param  callable(string): string  $cipherToServer  client → server transform
     * @param  resource  $upstream  already-connected upstream TCP socket
     * @param  callable(string): string  $cipherFromServer  server → client transform
     */
    public function pump($client, callable $cipherToServer, $upstream, callable $cipherFromServer, float $timeoutSeconds = 1.0): void
    {
        stream_set_blocking($client, true);
        stream_set_blocking($upstream, true);

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
            $ready = @stream_select($read, $write, $except, (int) floor($timeoutSeconds), (int) (($timeoutSeconds - (int) floor($timeoutSeconds)) * 1_000_000));
            if ($ready === false) {
                break;
            }
            if ($ready === 0) {
                continue; // one tick passed with no traffic; keep pumping
            }

            foreach ($read as $socket) {
                if (! $clientAlive && $socket === $client) {
                    continue;
                }
                if (! $upstreamAlive && $socket === $upstream) {
                    continue;
                }

                $data = $socket === $client
                    ? @fread($client, 8192)
                    : @fread($upstream, 8192);

                if ($data === false || $data === '') {
                    if ($socket === $client) {
                        $clientAlive = false;
                        @stream_socket_shutdown($upstream, STREAM_SHUT_WR);
                    } else {
                        $upstreamAlive = false;
                        @stream_socket_shutdown($client, STREAM_SHUT_WR);
                    }

                    continue;
                }

                if ($socket === $client) {
                    self::writeAll($upstream, $cipherToServer($data));
                } else {
                    self::writeAll($client, $cipherFromServer($data));
                }
            }
        }

        fclose($client);
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
                throw new RuntimeException('RelayServer: write to peer failed');
            }
            $written += $n;
        }
    }

    /**
     * @param  resource  $socket
     */
    private static function readExact($socket, int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length) {
            $chunk = @fread($socket, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('RelayServer: peer closed before the obfuscated2 header');
            }
            $buffer .= $chunk;
        }

        return $buffer;
    }
}
