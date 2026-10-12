<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Standard PHP Stream Socket Transport for Telegram MTProto.
 * Connects directly or tunnels through SOCKS5 / HTTP proxies using standard stream transports.
 *
 * TLS on the target stream is VERIFIED by default, and — the part that matters
 * for a tunnel — the certificate is verified against the TUNNELED TARGET's
 * name (peer_name), never against the proxy endpoint. See resolvePeerName().
 *
 * The TF_INSECURE_TLS=1 opt-out exists for local debugging against self-signed
 * endpoints. It is deliberately NOT a process-wide static: it is read at the
 * one explicit call site (createConnection), it is overridable there, and
 * every time it (or an explicit verifyPeer: false) disables verification a
 * warning is logged. Nothing in the process can silently and stickily turn
 * verification off.
 */
class StreamSocket
{
    /** Recognized truthy values for the TF_INSECURE_TLS opt-out. */
    private const INSECURE_TLS_TRUE = ['1', 'true', 'yes', 'on'];

    /**
     * Name the tunneled target is verified as when the target was dialled by
     * bare IP. The Telegram DC addresses in Client::DC_IPS are literals, and a
     * literal cannot authenticate anything: a proxy that terminates the tunnel
     * with SAN=IP:<its own address> would satisfy an IP-SAN check. Verifying
     * this name instead is what binds the TLS session to Telegram.
     */
    public const DEFAULT_TLS_PEER_NAME = 'dc.telegram.org';

    /** Never block forever: stream_set_timeout(0) means "no timeout". */
    private const MIN_STREAM_TIMEOUT_SECONDS = 1;

    /** Never hand stream_socket_client a non-positive timeout. */
    private const MIN_CONNECT_TIMEOUT_SECONDS = 0.001;

    /**
     * Wall-clock budget for ONE read() call's retry loop: how long an idle-but-open
     * connection is ridden out before it is reported as a timeout rather than an
     * EOF. Long enough to absorb a slow answer or a flood-wait on a real DC, short
     * enough that a genuinely wedged peer fails the caller in half a minute instead
     * of hanging it. See read() for the bound's exact worst case.
     */
    private const READ_RETRY_BUDGET_SECONDS = 30.0;

    /**
     * Log event for a spent read budget. Distinct from read_failed, which is what a
     * genuine EOF logs, so the two causes stay separable in the ledger instead of
     * both arriving as "EOF while reading".
     */
    public const READ_TIMEOUT_EVENT = 'teleframe.mtproto.socket.read_timeout';

    /**
     * Stable message prefix for a spent read budget. isReadTimeout() matches on it,
     * so it is part of this class's contract: changing it silently disables every
     * caller's ability to tell a timeout from a closed connection.
     */
    private const READ_TIMEOUT_PREFIX = 'StreamSocket: read timed out';

    /**
     * Pause before re-reading when the previous attempt returned immediately rather
     * than having waited out a stream read timeout, so a non-blocking or idle-peer
     * stream cannot turn the retry loop into a CPU spin.
     */
    private const READ_RETRY_BACKOFF_MICROSECONDS = 20000;

    /**
     * Builds the SSL context options for the target stream.
     *
     * Verified by default: verify_peer + verify_peer_name are on, the chain is
     * checked against $cafile when given (else PHP's default CA store), and —
     * when a $peerName is given — the certificate must actually be issued for
     * that name.
     *
     * @param  string|null  $cafile  PEM bundle path for the target's certificate chain
     * @param  string|null  $peerName  Name the target certificate must be issued for;
     *                                 null omits the option (only meaningful when TLS is on)
     * @param  bool|null  $verifyPeer  true forbids the TF_INSECURE_TLS opt-out entirely,
     *                                 false forces verification off, null defers to the env
     * @param  LoggerInterface|null  $logger  When verification ends up disabled, a warning
     *                                        is emitted here so it is never silent
     * @return array{verify_peer: bool, verify_peer_name: bool, peer_name?: string, cafile?: string}
     */
    public static function tlsContextOptions(
        ?string $cafile = null,
        ?string $peerName = null,
        ?bool $verifyPeer = null,
        ?LoggerInterface $logger = null
    ): array {
        if (self::verificationDisabled($verifyPeer)) {
            $logger?->warning('teleframe.mtproto.socket.tls_verification_disabled', [
                'reason' => $verifyPeer === false ? 'explicit_verify_peer_false' : 'env_TF_INSECURE_TLS',
                'peer_name' => $peerName,
            ]);

            return ['verify_peer' => false, 'verify_peer_name' => false];
        }

        $options = ['verify_peer' => true, 'verify_peer_name' => true];
        if ($peerName !== null && $peerName !== '') {
            $options['peer_name'] = $peerName;
        }
        if ($cafile !== null && $cafile !== '') {
            $options['cafile'] = $cafile;
        }

        return $options;
    }

    /**
     * Whether TLS peer verification ends up disabled for one connection. An
     * explicit true pin forbids the env opt-out, an explicit false forces it
     * off, and null falls back to the TF_INSECURE_TLS env default. Purely a
     * per-call decision — there is no process-wide state to poison.
     */
    private static function verificationDisabled(?bool $verifyPeer = null): bool
    {
        if ($verifyPeer !== null) {
            return ! $verifyPeer;
        }

        return self::insecureTlsEnabled();
    }

    /**
     * Whether TLS peer verification is disabled by environment
     * (TF_INSECURE_TLS=1/true/yes/on). Defaults to false — verification on.
     */
    public static function insecureTlsEnabled(): bool
    {
        $value = getenv('TF_INSECURE_TLS');

        return is_string($value) && in_array(strtolower($value), self::INSECURE_TLS_TRUE, true);
    }

    /**
     * The name the target certificate is verified as.
     *
     * This is the INNER target — the Telegram DC — never the proxy endpoint.
     * An explicit $peerName wins; otherwise the dialed host is used, unless
     * that host is a bare IP literal, which cannot authenticate a name and so
     * falls back to the Telegram DC name.
     */
    public static function resolvePeerName(string $targetHost, ?string $peerName = null): string
    {
        $candidate = $peerName !== null && $peerName !== '' ? $peerName : $targetHost;

        if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
            return self::DEFAULT_TLS_PEER_NAME;
        }

        return $candidate;
    }

    /**
     * Connects to a Telegram DC socket directly or via a SOCKS5 / HTTP CONNECT proxy.
     *
     * Supported proxy types:
     * - "http" / "https": HTTP CONNECT tunnel. The proxy hop is always a plain TCP
     *   pipe; "https" is accepted as an alias for "http" (CONNECT over plain TCP).
     * - "socks5": SOCKS5 tunnel (RFC 1928), with RFC 1929 username/password
     *   sub-negotiation when credentials are supplied.
     * Any other type is rejected with a RuntimeException rather than silently
     * falling back to a direct connection. A proxy entry without a host and port is
     * also rejected.
     *
     * @param  string  $host  Target Telegram DC IP
     * @param  int  $port  Target Telegram DC Port (default 443)
     * @param  array{type?: string, host?: string, port?: int, username?: string, password?: string}|null  $proxy
     * @param  float  $timeout  Timeout in seconds
     * @param  bool  $tls  When true, the TARGET stream is upgraded with TLS via
     *                     stream_socket_enable_crypto() after the connect (or after the
     *                     proxy handshake). TLS wraps the target stream only — the
     *                     connection to the proxy itself always stays plain TCP — and
     *                     it is applied on the direct path too, so asking for TLS is
     *                     never silently downgraded to plaintext. The target is
     *                     VERIFIED (verify_peer + verify_peer_name + peer_name =
     *                     resolvePeerName($host, $peerName), i.e. the DC, never the
     *                     proxy), against $cafile when given, unless
     *                     TF_INSECURE_TLS is set or verifyPeer is explicitly false —
     *                     either of which logs a warning.
     * @param  string|null  $cafile  Explicit PEM CA bundle for the target's chain;
     *                               null uses PHP's default CA store
     * @param  string|null  $peerName  Name the target certificate must be issued for;
     *                                 null derives it from $host
     * @param  bool|null  $verifyPeer  Per-call verification pin (see tlsContextOptions);
     *                                 never sticky, never process-wide
     * @param  LoggerInterface|null  $logger  PSR-3 logger for socket.connect (optional, never stored as static state)
     * @return resource Writable stream socket
     */
    public static function createConnection(
        string $host,
        int $port = 443,
        ?array $proxy = null,
        float $timeout = 10.0,
        bool $tls = false,
        ?LoggerInterface $logger = null,
        ?string $cafile = null,
        ?string $peerName = null,
        ?bool $verifyPeer = null
    ) {
        // The peer name is the tunneled TARGET, resolved before the proxy is even
        // known to exist, so no proxy field can ever end up in it.
        $resolvedPeerName = self::resolvePeerName($host, $peerName);

        $options = [
            'socket' => [
                'tcp_nodelay' => true,
            ],
            'ssl' => self::tlsContextOptions($cafile, $tls ? $resolvedPeerName : null, $verifyPeer, $logger),
        ];

        $context = stream_context_create($options);
        $remoteSocket = "tcp://{$host}:{$port}";

        if ($proxy === null) {
            $socket = @stream_socket_client(
                $remoteSocket,
                $errno,
                $errstr,
                self::connectTimeout($timeout),
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (! $socket) {
                $logger?->error('teleframe.mtproto.socket.connect', [
                    'host' => $host,
                    'port' => $port,
                    'error' => $errstr,
                ]);
                throw new RuntimeException("Failed to connect to Telegram DC {$remoteSocket}: {$errstr} ({$errno})");
            }

            stream_set_timeout($socket, self::streamTimeout($timeout));

            if ($tls) {
                self::enableTargetTls($socket, $host, $port, $resolvedPeerName, $logger, false);
            }

            $logger?->debug('teleframe.mtproto.socket.connect', [
                'host' => $host,
                'port' => $port,
                'proxied' => false,
                'tls' => $tls,
                'peer_name' => $tls ? $resolvedPeerName : null,
            ]);

            return $socket;
        }

        return self::connectViaProxy($host, $port, $proxy, $options, $timeout, $tls, $logger, $resolvedPeerName);
    }

    /**
     * Upgrades an already-connected TARGET stream to TLS and verifies it.
     * Shared by the direct and the tunnelled path; the proxy hop is never
     * touched by this method (its stream is already the tunnel).
     *
     * @param  resource  $socket
     */
    private static function enableTargetTls(
        $socket,
        string $host,
        int $port,
        string $peerName,
        ?LoggerInterface $logger = null,
        bool $viaProxy = true
    ): void {
        // The socket already carries the ssl-context options it was created
        // with (including peer_name = $peerName); enabling crypto wraps the
        // TARGET stream — tunnelled or direct — never the proxy hop.
        $enabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($enabled !== true) {
            fclose($socket);
            $logger?->error('teleframe.mtproto.socket.tls_upgrade_failed', [
                'host' => $host,
                'port' => $port,
                'peer_name' => $peerName,
                'proxied' => $viaProxy,
            ]);
            throw new RuntimeException(
                $viaProxy
                    ? 'StreamSocket: TLS upgrade over the proxy tunnel failed'
                    : 'StreamSocket: TLS upgrade on the direct connection failed'
            );
        }

        $crypto = stream_get_meta_data($socket)['crypto'] ?? [];

        $logger?->debug('teleframe.mtproto.socket.tls_established', [
            'host' => $host,
            'port' => $port,
            'peer_name' => $peerName,
            'proxied' => $viaProxy,
            'protocol' => (string) ($crypto['protocol'] ?? 'unknown'),
        ]);
    }

    /**
     * Establishes a tunnel to $host:$port through the configured proxy.
     *
     * The proxy hop is dialed with plain tcp://; TLS (when requested) is applied to
     * the already-connected tunneled stream, never to the proxy connection itself.
     *
     * @param  array{type?: string, host?: string, port?: int, username?: string, password?: string}  $proxy
     * @param  array{socket: array{tcp_nodelay: bool}, ssl: array{verify_peer: bool, verify_peer_name: bool, peer_name?: string, cafile?: string}}  $options
     * @return resource Writable stream socket, tunnelled through the proxy
     */
    private static function connectViaProxy(
        string $host,
        int $port,
        array $proxy,
        array $options,
        float $timeout,
        bool $tls,
        ?LoggerInterface $logger = null,
        ?string $peerName = null
    ) {
        $type = strtolower((string) ($proxy['type'] ?? 'socks5'));

        if ($type !== 'http' && $type !== 'https' && $type !== 'socks5') {
            throw new RuntimeException("StreamSocket: unsupported proxy type \"{$type}\" (supported: http, https, socks5)");
        }

        $proxyHost = $proxy['host'] ?? null;
        $proxyPort = $proxy['port'] ?? null;
        if ($proxyHost === null || $proxyHost === '' || $proxyPort === null) {
            throw new RuntimeException('StreamSocket: proxy config requires a host and a port');
        }

        $proxyEndpoint = "tcp://{$proxyHost}:{$proxyPort}";

        $socket = @stream_socket_client(
            $proxyEndpoint,
            $errno,
            $errstr,
            self::connectTimeout($timeout),
            STREAM_CLIENT_CONNECT,
            stream_context_create($options)
        );

        if (! $socket) {
            $logger?->error('teleframe.mtproto.socket.connect', [
                'host' => $host,
                'port' => $port,
                'proxy' => $proxyEndpoint,
                'error' => $errstr,
            ]);
            throw new RuntimeException("Failed to connect to proxy {$proxyEndpoint}: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, self::streamTimeout($timeout));

        if ($type === 'http' || $type === 'https') {
            self::httpTunnel($socket, $host, $port, $proxy);
        } else {
            self::socks5Tunnel($socket, $host, $port, $proxy);
        }

        if ($tls) {
            self::enableTargetTls(
                $socket,
                $host,
                $port,
                $peerName ?? self::resolvePeerName($host),
                $logger,
                true
            );
        }

        $logger?->debug('teleframe.mtproto.socket.connect', [
            'host' => $host,
            'port' => $port,
            'proxied' => true,
            'proxy' => $proxyEndpoint,
            'tls' => $tls,
            'peer_name' => $tls ? ($peerName ?? self::resolvePeerName($host)) : null,
        ]);

        return $socket;
    }

    /**
     * Connect timeout for stream_socket_client(): never zero, never negative
     * (a non-positive float is not a meaningful connect deadline).
     */
    private static function connectTimeout(float $timeout): float
    {
        return $timeout > self::MIN_CONNECT_TIMEOUT_SECONDS ? $timeout : self::MIN_CONNECT_TIMEOUT_SECONDS;
    }

    /**
     * Whole-second stream timeout for stream_set_timeout(). A truncation to 0
     * would mean "block forever", so a sub-1.0 value floors at 1 second
     * instead of turning into an unbounded read.
     */
    private static function streamTimeout(float $timeout): int
    {
        $seconds = (int) ceil($timeout);

        return $seconds > self::MIN_STREAM_TIMEOUT_SECONDS ? $seconds : self::MIN_STREAM_TIMEOUT_SECONDS;
    }

    /**
     * Perform the HTTP CONNECT handshake on an established plain-TCP connection to
     * the proxy, then leave the stream ready for the tunneled payload.
     *
     * @param  resource  $socket
     * @param  array{type?: string, host?: string, port?: int, username?: string, password?: string}  $proxy
     */
    private static function httpTunnel($socket, string $host, int $port, array $proxy): void
    {
        $username = (string) ($proxy['username'] ?? '');
        $password = (string) ($proxy['password'] ?? '');
        $authLine = '';
        if ($username !== '' || $password !== '') {
            $authLine = 'Proxy-Authorization: Basic '.base64_encode($username.':'.$password)."\r\n";
        }

        self::write($socket, "CONNECT {$host}:{$port} HTTP/1.1\r\nHost: {$host}:{$port}\r\n{$authLine}\r\n");

        $head = self::readProxyHead($socket, "\r\n\r\n", 'the HTTP CONNECT response');
        if (! str_starts_with($head, 'HTTP/1.1 200') && ! str_starts_with($head, 'HTTP/1.0 200')) {
            $lineEnd = strpos($head, "\r\n");
            $statusLine = $lineEnd === false ? trim($head) : trim(substr($head, 0, $lineEnd));
            self::failProxy("StreamSocket: HTTP proxy rejected the CONNECT request: {$statusLine}", $socket);
        }
    }

    /**
     * Perform the RFC 1928 SOCKS5 handshake (with RFC 1929 username/password auth
     * when credentials are given) on an established plain-TCP connection to the proxy,
     * then leave the stream ready for the tunneled payload.
     *
     * @param  resource  $socket
     * @param  array{type?: string, host?: string, port?: int, username?: string, password?: string}  $proxy
     */
    private static function socks5Tunnel($socket, string $host, int $port, array $proxy): void
    {
        $username = (string) ($proxy['username'] ?? '');
        $password = (string) ($proxy['password'] ?? '');

        if ($username !== '' || $password !== '') {
            if (strlen($username) > 255 || strlen($password) > 255) {
                self::failProxy('StreamSocket: SOCKS5 username and password must not exceed 255 bytes', $socket);
            }

            // Greeting offering username/password as the only method.
            self::write($socket, "\x05\x01\x02");
            $greeting = self::readProxyBytes($socket, 2, 'the SOCKS5 greeting reply');
            if ($greeting[0] !== "\x05" || $greeting[1] !== "\x02") {
                self::failSocks5Method($socket, $greeting, 'username/password');
            }

            // RFC 1929 sub-negotiation: VER(1) ULEN(1) UNAME PLEN(1) PASSWD.
            $authRequest = "\x01".chr(strlen($username)).$username.chr(strlen($password)).$password;
            self::write($socket, $authRequest);

            $authReply = self::readProxyBytes($socket, 2, 'the SOCKS5 username/password reply');
            if ($authReply[0] !== "\x01" || $authReply[1] !== "\x00") {
                self::failProxy('StreamSocket: SOCKS5 proxy rejected the username/password credentials', $socket);
            }
        } else {
            // Greeting offering no-authentication as the only method.
            self::write($socket, "\x05\x01\x00");
            $greeting = self::readProxyBytes($socket, 2, 'the SOCKS5 greeting reply');
            if ($greeting[0] !== "\x05" || $greeting[1] !== "\x00") {
                self::failSocks5Method($socket, $greeting, 'anonymous');
            }
        }

        // CONNECT request: VER(5) CMD(1=CONNECT) RSV(0) ATYP(1|3) addr port.
        if (strlen($host) > 255) {
            self::failProxy('StreamSocket: SOCKS5 hostname exceeds 255 bytes for the domain method', $socket);
        }
        self::write($socket, "\x05\x01\x00".self::socks5AddressBlock($host).pack('n', $port));

        // Reply head: VER REP RSV ATYP.
        $reply = self::readProxyBytes($socket, 4, 'the SOCKS5 connect reply');
        if ($reply[0] !== "\x05" || $reply[1] !== "\x00") {
            self::failSocks5Connect($socket, $reply);
        }

        // Consume the variable-length bind address (BND.ADDR + BND.PORT) so the
        // tunneled stream stays aligned for the first payload byte.
        switch ($reply[3]) {
            case "\x01":
                self::readProxyBytes($socket, 4 + 2, 'the SOCKS5 bind address');
                break;
            case "\x04":
                self::readProxyBytes($socket, 16 + 2, 'the SOCKS5 bind address');
                break;
            case "\x03":
                $addrLen = ord(self::readProxyBytes($socket, 1, 'the SOCKS5 bind address'));
                self::readProxyBytes($socket, $addrLen + 2, 'the SOCKS5 bind address');
                break;
            default:
                self::failProxy(
                    'StreamSocket: SOCKS5 proxy returned an unsupported bind address type 0x'.bin2hex($reply[3]),
                    $socket
                );
        }
    }

    /**
     * Builds the SOCKS5 ATYP + address block for $host. Resolves the host locally
     * (gethostbyname) and sends the plain IPv4 when possible, otherwise falls back
     * to the RFC 1928 domain method so the proxy performs the DNS.
     */
    private static function socks5AddressBlock(string $host): string
    {
        $ipv4 = self::resolveIpv4($host);
        if ($ipv4 !== null) {
            return "\x01".inet_pton($ipv4);
        }

        return "\x03".chr(strlen($host)).$host;
    }

    private static function resolveIpv4(string $host): ?string
    {
        $candidates = [];
        $literal = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        if ($literal !== false) {
            $candidates[] = $literal;
        }
        $resolved = gethostbyname($host);
        if ($resolved !== $host) {
            $candidates[] = $resolved;
        }
        foreach ($candidates as $candidate) {
            $validated = filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            if ($validated !== false) {
                return $validated;
            }
        }

        return null;
    }

    /**
     * @param  resource  $socket
     */
    private static function failSocks5Method($socket, string $reply, string $mode): never
    {
        if ($reply[1] === "\xFF") {
            self::failProxy(
                'StreamSocket: SOCKS5 proxy found no acceptable authentication method for '.$mode.' auth',
                $socket
            );
        }

        self::failProxy(
            'StreamSocket: SOCKS5 proxy declined the requested '.$mode.' method (method 0x'.bin2hex($reply[1]).')',
            $socket
        );
    }

    /**
     * @param  resource  $socket
     */
    private static function failSocks5Connect($socket, string $reply): never
    {
        self::failProxy(
            'StreamSocket: SOCKS5 proxy could not connect to the target: '.self::socks5ReplyMeaning($reply[1]),
            $socket
        );
    }

    private static function socks5ReplyMeaning(string $code): string
    {
        return match ($code) {
            "\x01" => 'general SOCKS server failure',
            "\x02" => 'connection not allowed by ruleset',
            "\x03" => 'network unreachable',
            "\x04" => 'host unreachable',
            "\x05" => 'connection refused',
            "\x06" => 'TTL expired',
            "\x07" => 'command not supported',
            "\x08" => 'address type not supported',
            default => 'reply code 0x'.bin2hex($code),
        };
    }

    /**
     * Reads from $socket until $boundary is found and returns everything read.
     *
     * @param  resource  $socket
     */
    private static function readProxyHead($socket, string $boundary, string $what): string
    {
        $data = '';
        while (! str_contains($data, $boundary)) {
            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                self::failProxyRead($socket, $what);
            }
            $data .= $chunk;
        }

        return $data;
    }

    /**
     * Reads exactly $length bytes from $socket.
     *
     * @param  resource  $socket
     */
    private static function readProxyBytes($socket, int $length, string $what): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = @fread($socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                self::failProxyRead($socket, $what);
            }
            $data .= $chunk;
        }

        return $data;
    }

    /**
     * Closes $socket and throws a RuntimeException carrying $message.
     * Guards every handshake failure path so a rejected tunnel never leaks a socket.
     *
     * @param  resource  $socket
     */
    private static function failProxy(string $message, $socket): never
    {
        fclose($socket);
        throw new RuntimeException($message);
    }

    /**
     * Closes $socket and throws a read-failure RuntimeException (distinguishing a
     * proxy time-out from a premature close).
     *
     * @param  resource  $socket
     */
    private static function failProxyRead($socket, string $what): never
    {
        $timedOut = ! empty(stream_get_meta_data($socket)['timed_out']);
        self::failProxy(
            $timedOut
                ? "StreamSocket: proxy timed out while reading {$what}"
                : "StreamSocket: proxy closed the connection while reading {$what}",
            $socket
        );
    }

    public static function write($socket, string $bytes, ?LoggerInterface $logger = null): void
    {
        $total = strlen($bytes);
        $written = 0;
        while ($written < $total) {
            $n = @fwrite($socket, substr($bytes, $written));
            if ($n === false || $n === 0) {
                $logger?->error('teleframe.mtproto.socket.write_failed', [
                    'offset' => $written,
                    'length' => $total,
                ]);
                throw new RuntimeException('StreamSocket: write failed at offset '.$written);
            }
            $written += $n;
        }
    }

    /**
     * Formats a budget for a message without PHP's locale-dependent float printing:
     * 30.0 stays "30", 1.5 stays "1.5".
     */
    private static function seconds(float $budget): string
    {
        return rtrim(rtrim(sprintf('%.3F', $budget), '0'), '.');
    }

    /**
     * Read UP TO $length bytes — never fewer, never more, and NOT necessarily
     * exactly $length.
     *
     * `fread()` on a socket is allowed to return fewer bytes than requested
     * while the stream is perfectly healthy: TCP delivers segments, and a
     * large body routinely arrives across several of them. A single-fread
     * implementation that treats its result as a complete buffer is the
     * classic MTProto frame desync — the length prefix says 4 KB, the caller
     * receives 900 bytes, and every frame after it is garbage.
     *
     * That is why this method returns the result of ONE read and readExact() is
     * the loop: callers that need a precise count MUST use readExact(). This
     * method exists for the "take whatever has arrived" case, and the asymmetry
     * is deliberate — do not use it to frame a message, and do not "fix" a short
     * read by looping and accumulating here.
     *
     * AN EMPTY READ IS NOT NECESSARILY AN EOF, AND NEITHER IS AN EMPTY RETURN
     * VALUE. On this PHP (measured on both a unix socket pair and a TCP loopback
     * socket, blocked with stream_set_timeout()) a read that yields NOTHING comes
     * back in one of these three shapes, and only ONE of them is a closed peer:
     *
     *   fread() === false, timed_out=true, eof=false
     *       The READ TIMEOUT elapsed with no bytes available. This is an IDLE but
     *       healthy connection, and it is what a real Telegram DC produces
     *       routinely: a slow answer, a flood-wait, one segment lost to a
     *       retransmit. (Note the shape: a timeout is a FALSE, not an empty
     *       string — code that only tests `=== ''` misses every one of them.)
     *   fread() === '', eof=true, timed_out=false
     *       Genuine EOF: the peer closed. PHP clears `timed_out` on this read, so a
     *       timeout latched by an earlier attempt can never mask a later close.
     *   fread() === false, timed_out=false
     *       A hard read error — the read FAILED (it can even report eof=true while
     *       failing, so this is classified before the EOF shape, never as one).
     *
     * Reading the first shape as an EOF is what made live ingest runs die with
     * "EOF while reading" while the connection was still perfectly usable:
     * Client::call() treats any transport RuntimeException as a dead connection and
     * evicts it. So a timeout is retried until a wall-clock budget is spent, and
     * only then raised — as a distinguishable error (see isReadTimeout()), never as
     * an EOF. A genuine EOF and a hard read error both raise on the first read:
     * neither can improve by being read again.
     *
     * THE RETRY IS BOUNDED, and that is load-bearing. An unbounded retry here
     * would trade a fatal error for a silent hang, which is worse than the defect
     * it fixes: a wedged ingest process is indistinguishable from a slow one. The
     * budget bounds the total wall clock of ONE read() call; because a single
     * blocking fread() cannot be interrupted early, the worst case is the budget
     * plus at most one stream read timeout.
     *
     * THE RETRY CANNOT DESYNC. It only ever re-issues a read that returned ZERO
     * bytes (a read that times out consumed nothing), and it re-requests the same
     * remaining count — so no byte is dropped and none is duplicated. Continuing a
     * frame across a timeout is exactly what readExact()'s loop already does.
     *
     * @param  resource  $socket
     * @param  float|null  $retryBudgetSeconds  wall-clock budget for this call's
     *                                          retry loop; null uses the shipped
     *                                          default. Tests pass a small value.
     */
    public static function read($socket, int $length, ?LoggerInterface $logger = null, ?float $retryBudgetSeconds = null): string
    {
        $budget = $retryBudgetSeconds ?? self::READ_RETRY_BUDGET_SECONDS;
        $deadline = microtime(true) + $budget;

        while (true) {
            $chunk = @fread($socket, $length);
            if ($chunk !== false && $chunk !== '') {
                return $chunk;
            }

            $meta = @stream_get_meta_data($socket);
            $timedOut = ! empty($meta['timed_out']);

            if ($chunk === false && ! $timedOut) {
                // The read FAILED, it did not merely come up empty. Classified
                // before the EOF shape on purpose: a failing read can report
                // eof=true too, and calling that a closed connection would be a
                // second lie about the same empty result.
                $logger?->error('teleframe.mtproto.socket.read_failed', [
                    'length' => $length,
                    'cause' => 'read_error',
                ]);

                throw new RuntimeException("StreamSocket: read failed while reading {$length} bytes");
            }

            if ($chunk === '' && ! empty($meta['eof'])) {
                // The peer closed. Retrying this cannot help, so it raises at once.
                $logger?->error('teleframe.mtproto.socket.read_failed', [
                    'length' => $length,
                    'cause' => 'eof',
                ]);

                throw new RuntimeException('StreamSocket: EOF while reading');
            }

            // A timeout (or a transient zero-byte read on a stream that reports no
            // eof and no timeout — a non-blocking socket, a TLS mid-frame hiccup):
            // the socket is open and the peer has nothing to say YET.
            if (microtime(true) >= $deadline) {
                $logger?->error(self::READ_TIMEOUT_EVENT, [
                    'length' => $length,
                    'budget_seconds' => $budget,
                    'socket_open' => true,
                ]);

                throw new RuntimeException(sprintf(
                    'StreamSocket: read timed out after %ss waiting for %d bytes — the socket is still open, '
                    .'the peer has not closed it',
                    self::seconds($budget),
                    $length
                ));
            }

            // Nothing to read and nothing timed out — a transient zero-byte read, or a
            // NON-BLOCKING socket with no data yet. That shape returns instantly, so
            // without a pause the retry would spin on the CPU for the whole budget. The
            // timed-out path needs no pause: it already waited a full stream timeout.
            if (! $timedOut) {
                usleep(self::READ_RETRY_BACKOFF_MICROSECONDS);
            }
        }
    }

    /**
     * Is $error the read-timeout this class raises when a retry budget is spent?
     *
     * A caller can therefore tell a TIMEOUT (an open but idle connection — retry or
     * drop, but the bytes may still be coming) from a closed connection, which is
     * the distinction the historic "EOF while reading" message destroyed. This is a
     * stable predicate over the message prefix rather than a dedicated exception
     * class: PSR-4 gives one class per file, and the transport's exception classes
     * live outside the file this behaviour belongs to.
     */
    public static function isReadTimeout(Throwable $error): bool
    {
        return str_starts_with($error->getMessage(), self::READ_TIMEOUT_PREFIX);
    }

    /**
     * Read EXACTLY $length bytes, or throw.
     *
     * This is the method to use for framing. It loops because a single socket
     * read may legitimately return less than asked; see read() for why that
     * matters on this wire. Each read() inside the loop is itself bounded, so
     * this loop terminates on an idle peer instead of hanging on it.
     *
     * @param  resource  $socket
     */
    public static function readExact($socket, int $length, ?LoggerInterface $logger = null): string
    {
        $buffer = '';
        while (strlen($buffer) < $length) {
            $buffer .= self::read($socket, $length - strlen($buffer), $logger);
        }

        return $buffer;
    }
}
