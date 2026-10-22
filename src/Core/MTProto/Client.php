<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\PlainConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\WebProxyConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\AuthKeyFactory;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PasswordCalculator;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\StreamSocket;
use MeRezaRezaei\Teleframe\Core\Schema\MethodRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;

/**
 * Clean, stateless MTProto 2.0 Client.
 * Handles MTProto RPC calls, encryption, and proxy routing without local database or filesystem locks.
 *
 * Logging is optional and silent by default (NullLogger). Metadata only — session
 * strings, auth keys and message payloads are NEVER logged.
 */
class Client
{
    /**
     * Default Telegram production DC IP addresses.
     */
    public const DC_IPS = [
        1 => '149.154.175.53',
        2 => '149.154.167.51',
        3 => '149.154.175.100',
        4 => '149.154.167.91',
        5 => '91.108.56.130',
    ];

    public const DEFAULT_PORT = 443;

    /**
     * Telegram kills idle encrypted connections ~60s after the last message;
     * a connection idle longer than this gets a ping_delay_disconnect
     * keepalive BEFORE its next RPC (MadelineProto PingLoop parity, lazy).
     */
    public const KEEPALIVE_IDLE_SECONDS = 45.0;

    /**
     * How many times ONE request may be put on the wire before a READ TIMEOUT is
     * treated as a dead connection. Two = the original plus exactly ONE resend.
     *
     * WHY TWO, and not "retry until it answers": an unbounded ladder on a DC that
     * is merely slow converts a bounded failure into a silent hang, and a hung
     * ingest process is indistinguishable from a working one. Each attempt is
     * itself bounded by StreamSocket's read budget, so the worst case for one
     * call() is two budgets plus at most one stream read timeout each — a number
     * an operator can reason about. It also matches the ladder the connection
     * itself already uses for a bad_server_salt resend (EncryptedConnection's
     * own two-attempt loop), so "at most two attempts, second one carries a
     * fresh msg_id/seq_no" is a single, house-wide convention rather than two.
     *
     * A resend is safe on this wire because the request body is rebuilt from the
     * SAME ($constructor, $args) — every idempotency token it carries (notably
     * messages.sendMessage's random_id) is therefore unchanged, which is exactly
     * what makes an MTProto resend de-duplicable server-side.
     */
    public const READ_TIMEOUT_MAX_ATTEMPTS = 2;

    /**
     * Log event for "the transport timed out and the request is going on the
     * wire again, unchanged, on the same open socket".
     *
     * It exists so a retried timeout is never filed under the terminal
     * teleframe.mtproto.rpc.failed record. The operator's question when an
     * ingest run gets slow is always the same — did the DC go away, or was it
     * just slow — and with a single event for both, the ledger cannot answer it.
     */
    public const READ_TIMEOUT_RETRY_EVENT = 'teleframe.mtproto.rpc.read_timeout.retry';

    protected ?array $proxyConfig = null;

    /**
     * Whether the TARGET stream is upgraded to TLS after (tunnelled) connect.
     *
     * Off by default, and that is a protocol requirement, not an oversight: a
     * Telegram DC on 443 speaks obfuscated2, not TLS — a ClientHello would be
     * answered with garbage and every live connection would break. TLS here is
     * for tunnelling to something that IS a TLS endpoint (a local relay, a
     * lab DC). It is opt-in, and once on it is applied on both the direct and
     * the proxied path and the certificate is verified against
     * StreamSocket::DEFAULT_TLS_PEER_NAME.
     */
    protected bool $tlsEnabled = false;

    /** Name the tunneled/direct target certificate is verified as; null derives it. */
    protected ?string $tlsPeerName = null;

    /** Explicit PEM CA bundle for the target's chain; null uses PHP's default store. */
    protected ?string $tlsCaFile = null;

    /** Tri-state: null = defer to config('teleframe.live_mode') at first use; true/false = explicit. */
    private ?bool $live = null;

    private ?object $conn = null;

    private LoggerInterface $logger;

    public function __construct(
        public int $apiId = 0,
        public string $apiHash = '',
        public ?SessionData $session = null,
        ?bool $live = null,
        ?LoggerInterface $logger = null
    ) {
        $this->live = $live;
        $this->logger = $logger ?? new NullLogger;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function setSession(SessionData $session): self
    {
        $this->session = $session;
        $this->close();

        return $this;
    }

    public function getSession(): ?SessionData
    {
        return $this->session;
    }

    /**
     * Configure proxy for MTProto socket connections.
     *
     * @param  array{type?: string, host?: string, port?: int, username?: string, password?: string, secret?: string, carrier?: string}  $config
     */
    public function setProxy(array $config): self
    {
        $validTypes = ['socks5', 'http', 'https', 'mtproto', 'web'];
        if (isset($config['type']) && ! in_array(strtolower($config['type']), $validTypes, true)) {
            throw new InvalidArgumentException("Invalid proxy type: {$config['type']}. Supported types: ".implode(', ', $validTypes));
        }

        $this->proxyConfig = $config;

        return $this;
    }

    /**
     * Opt the socket path into TLS on the TARGET stream (after the tunnel).
     *
     * @param  bool  $enabled  When true, the target is verified against
     *                         $peerName (default StreamSocket::DEFAULT_TLS_PEER_NAME)
     * @param  string|null  $peerName  Name the target certificate must be issued for
     * @param  string|null  $cafile  PEM CA bundle for the target's chain
     */
    public function setTls(bool $enabled, ?string $peerName = null, ?string $cafile = null): self
    {
        $this->tlsEnabled = $enabled;
        $this->tlsPeerName = $peerName;
        $this->tlsCaFile = $cafile;

        return $this;
    }

    /**
     * Connects to the active DC stream socket.
     *
     * @return resource
     */
    public function openSocket()
    {
        $dcId = $this->session->dcId ?? 2;
        $host = self::DC_IPS[$dcId] ?? self::DC_IPS[2];

        return StreamSocket::createConnection(
            host: $host,
            port: self::DEFAULT_PORT,
            proxy: $this->proxyConfig,
            logger: $this->logger,
            cafile: $this->tlsCaFile,
            peerName: $this->tlsPeerName,
            tls: $this->tlsEnabled
        );
    }

    /**
     * Opt in to the live MTProto wire path (fluent; used by the doctor command).
     */
    public function live(): static
    {
        $this->live = true;

        return $this;
    }

    /**
     * Resolves the tri-state live flag at first use: an explicit constructor
     * value (or ->live()) wins; null defers to config('teleframe.live_mode')
     * when running inside a Laravel app, else stays offline.
     */
    protected function isLive(): bool
    {
        return $this->live ??= $this->resolveLiveDefault();
    }

    /**
     * config-if-available pattern (like TelegramWebhookController): the config()
     * helper only exists inside a Laravel application, so outside one — CLI
     * scripts, unit tests — the default resolves to offline.
     */
    protected function resolveLiveDefault(): bool
    {
        if (! function_exists('config')) {
            return false;
        }
        try {
            return (bool) config('teleframe.live_mode');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Encodability gate: a method that carries no TL schema signature can
     * never be serialized onto the wire, in ANY mode. Offline mode must not
     * be allowed to fabricate success for one, and live mode must say plainly
     * why the call is impossible instead of leaking the raw registry wording.
     *
     * Reuses TLRegistry — the same closure TLEncoder itself encodes through,
     * so this is a name check, NOT a second encoding mechanism: a
     * catalogued-but-unencodable method (the 34 mtproto entries with no
     * signature, e.g. all `communities.*` and all `ephemeral.*`) is rejected
     * here before params are even looked at.
     *
     * @throws InvalidArgumentException naming the method, in both modes
     */
    protected function assertEncodable(string $method): void
    {
        // Ask the catalogue first. MethodRegistry already carries a precise
        // reason for every name it refuses — "invokeWithTakeout is a Telegram
        // request ENVELOPE, not a callable method" — and this never asked it.
        // Every one of those names therefore reported the same wrong cause:
        // "no TL schema signature is registered for it, so it can never reach
        // Telegram". That blames the package's own packaging for what is a
        // deliberate design decision, and it is the wrong thing for an
        // integrator to go and investigate. The distinction matters most for
        // `invokeWithBusinessConnection`, which is an envelope a business-bot
        // client genuinely expects to be able to dial.
        //
        // A name in neither artifact is NOT a refusal reason — that stays the
        // packaging case below, which is what the existing message is for.
        $knownReason = null;

        try {
            $knownReason = MethodRegistry::unsupportedReason($method);
        } catch (InvalidArgumentException) {
            $knownReason = null;
        }

        if (is_string($knownReason) && $knownReason !== '') {
            throw new InvalidArgumentException('MTProto: '.rtrim($knownReason, ". \t\n\r").'.');
        }

        try {
            TLRegistry::signatureOf($method);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                "MTProto: method '{$method}' is not encodable — no TL schema signature is "
                .'registered for it, so it can never reach Telegram (live or offline).',
                0,
                $e
            );
        }
    }

    /**
     * Executes a raw MTProto RPC method.
     *
     * OFFLINE CONTRACT: when live mode is off (the default outside a Laravel
     * app, and whatever config('teleframe.live_mode') says), nothing is sent.
     * The returned array is an OFFLINE MARKER, not a Telegram answer: it
     * carries `'_' => 'rpc_result'` plus `'_offline' => true` (a real wire
     * answer is the decoded server constructor, e.g. `'_' => 'nearestDc'`, and
     * never carries `_offline`). It is emitted only for a method that HAS a
     * TL signature and is otherwise well-formed; anything that could not have
     * reached Telegram throws instead of returning this shape.
     *
     * @param  string  $method  MTProto method name (e.g. 'messages.sendMessage', 'users.getFullUser')
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException when the method is not encodable (unknown to TLRegistry) — in BOTH modes
     * @throws RuntimeException on malformed params (pre-I/O; connection kept), or on a transport/protocol
     *                          failure (connection evicted). A READ TIMEOUT is the one transport failure
     *                          that is NOT a dead connection: it is retried once on the same cached
     *                          socket first (READ_TIMEOUT_MAX_ATTEMPTS), so only a second consecutive
     *                          timeout — or any other cause — evicts.
     */
    public function call(string $method, array $params = []): array
    {
        // Before the mode branch: an unencodable method fails loudly in both
        // modes, so offline mode can never fabricate a result for a call that
        // could not possibly have worked.
        $this->assertEncodable($method);

        if (! $this->isLive()) {
            if ($this->session === null || empty($this->session->authKey)) {
                throw new RuntimeException('Session AuthKey is required to make authenticated MTProto calls.');
            }

            // Offline marker for unit tests & dry-run tooling. `_offline`
            // is the contract: nothing was sent, this is not a server answer.
            //
            // The marker itself is pinned by tests and must stay a return
            // value, not a throw (turning it into an exception is a breaking
            // change). What was missing is the AUDIBLE part: with live_mode
            // off by default, a first deploy called into it and got a silent
            // fabricated result with no log line anywhere. Warn once per call.
            $this->logger->warning(
                'teleframe.mtproto.offline.result: no Telegram answer — this result was fabricated locally '
                .'(live_mode is off; nothing was sent).',
                [
                    'method' => $method,
                    'dc_id' => $this->session->dcId,
                ]
            );

            return [
                '_' => 'rpc_result',
                '_offline' => true,
                'method' => $method,
                'params' => $params,
                'dc_id' => $this->session->dcId,
            ];
        }

        // Pre-I/O validation: an encode failure (unknown method, malformed
        // params) is a caller error, not a dead connection — it must not
        // evict a healthy cached connection. EncryptedConnection::call
        // (re)builds the body — fresh connections wrap it in invokeWithLayer,
        // which encodes the same fields — so this pass is validation-only.
        TLEncoder::encodeObject($method, $params);

        // The transport block is a BOUNDED LADDER, not a single shot: a read
        // timeout is retried once on the same cached connection (see
        // shouldRetryOnCachedConnection() for why a timeout is not a dead peer).
        for ($attempt = 1; ; $attempt++) {
            try {
                $conn = $this->ensureConnection();
                $idle = $conn->idleSeconds();
                if ($idle > self::KEEPALIVE_IDLE_SECONDS) {
                    $this->logger->debug('teleframe.mtproto.keepalive', [
                        'idle_seconds' => (int) $idle,
                    ]);
                    $conn->ping(); // lazy keepalive: keep the socket past the ~60s server idle kill
                }

                return $conn->call($method, $params);
            } catch (TelegramException $e) {
                throw $e; // RPC-level error: the connection stays usable
            } catch (RuntimeException $e) {
                if ($this->shouldRetryOnCachedConnection($e, $attempt, ['method' => $method])) {
                    continue;
                }

                // Anything else — a closed peer, a hard read error, a protocol
                // failure, or a second consecutive timeout — means the cached
                // connection is finished: evict, rethrow.
                $this->logger->error('teleframe.mtproto.rpc.failed', [
                    'method' => $method,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
                $this->close();
                throw $e;
            }
        }
    }

    /**
     * Executes N independent MTProto RPC methods in ONE round-trip via
     * EncryptedConnection::callBatch() (single msg_container). Offline mode
     * returns call()'s offline marker per key, keys and order preserved.
     *
     * Lazy handshake/first-call semantics match call(): a fresh connection
     * routes its FIRST request through call() — which wraps it in
     * invokeWithLayer + initConnection — and batches only the remainder;
     * steady-state connections send every request in one container.
     *
     * @param  array<string, array{method: string, params: array<string, mixed>}>  $requests
     * @return array<string, array<string, mixed>> key => decoded result, input order preserved
     *
     * @throws InvalidArgumentException when a method is not encodable (unknown to TLRegistry) — in BOTH modes
     * @throws TelegramException on rpc_error
     * @throws RuntimeException on bounds violations/malformed params (pre-I/O; connection kept), or on a
     *                          transport/protocol failure (connection evicted). A READ TIMEOUT is
     *                          retried once on the same cached connection first, exactly as in
     *                          call() — see READ_TIMEOUT_MAX_ATTEMPTS.
     */
    public function callMany(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        // Same pre-branch gate as call(): an unencodable method is rejected in
        // both modes, before a single offline marker is fabricated for it.
        foreach ($requests as $request) {
            $this->assertEncodable($request['method']);
        }

        if (! $this->isLive()) {
            if ($this->session === null || empty($this->session->authKey)) {
                throw new RuntimeException('Session AuthKey is required to make authenticated MTProto calls.');
            }

            // Offline marker for unit tests & dry-run tooling (call()'s shape)
            $stub = [];
            foreach ($requests as $key => $request) {
                $stub[$key] = [
                    '_' => 'rpc_result',
                    '_offline' => true,
                    'method' => $request['method'],
                    'params' => $request['params'],
                    'dc_id' => $this->session->dcId,
                ];
            }

            return $stub;
        }

        // Pre-I/O validation — BEFORE any connection is touched, so caller
        // errors never evict a healthy cached connection the way transport
        // failures do. Bodies are built for EVERY request up front:
        // TLRegistry validates each method name (unknown ->
        // InvalidArgumentException naming it) and TLEncoder the params;
        // then the batch bounds are asserted.
        $bodies = [];
        foreach ($requests as $key => $request) {
            $bodies[$key] = TLEncoder::encodeObject($request['method'], $request['params']);
        }
        // Both EncryptedConnection and WebProxyConnection have the same static method
        $connClass = $this->conn::class ?? EncryptedConnection::class;
        $connClass::assertBatchWithinBounds($bodies);

        for ($attempt = 1; ; $attempt++) {
            try {
                $conn = $this->ensureConnection();
                $idle = $conn->idleSeconds();
                if ($idle > self::KEEPALIVE_IDLE_SECONDS) {
                    $this->logger->debug('teleframe.mtproto.keepalive', [
                        'idle_seconds' => (int) $idle,
                    ]);
                    $conn->ping(); // lazy keepalive before the batched round-trip
                }

                if (! $conn->isInited()) {
                    // First-call semantics as today: the leading request establishes
                    // the layer via call()'s invokeWithLayer wrap; the rest ride one
                    // container. A single-request callMany degenerates to call().
                    //
                    // $bodies is COPIED rather than unset in place, because a read
                    // timeout re-enters this whole block: the resend must be the same
                    // batch, and mutating the caller's body map would silently shrink
                    // the retry. (On such a resend the connection is inited by now, so
                    // the retry legitimately takes the single-container branch — the
                    // layer is already established.)
                    $remaining = $bodies;
                    $firstKey = array_key_first($remaining);
                    $results = [$firstKey => $conn->call($requests[$firstKey]['method'], $requests[$firstKey]['params'])];
                    unset($remaining[$firstKey]);

                    return $results + $conn->callBatch($remaining);
                }

                return $conn->callBatch($bodies);
            } catch (TelegramException $e) {
                // RPC-level error: a batch rpc_error is raised only AFTER every
                // sibling rpc_result has been drained (EncryptedConnection
                // defers it), so no stale frame is left on the stream — the
                // connection stays usable, do not evict. Typed semantics
                // (FLOOD_WAIT / MIGRATE) are preserved by the rethrow.
                throw $e;
            } catch (RuntimeException $e) {
                if ($this->shouldRetryOnCachedConnection($e, $attempt, ['count' => count($requests)])) {
                    continue;
                }

                // Anything else — a closed peer, a hard read error, a protocol
                // failure, or a second consecutive timeout — means the cached
                // connection is finished: evict, rethrow.
                $this->logger->error('teleframe.mtproto.rpc.failed', [
                    'count' => count($requests),
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
                $this->close();
                throw $e;
            }
        }
    }

    /**
     * Should this transport failure be answered by putting the request on the
     * wire AGAIN, unchanged, on the SAME cached connection?
     *
     * A read timeout is the one transport failure that is not evidence of a dead
     * connection — it is evidence of an IDLE one. StreamSocket says so in the
     * message it raises ("the socket is still open, the peer has not closed it")
     * and exposes it as a predicate precisely for this caller. Evicting on it
     * threw away the retry StreamSocket had just bought: the next call paid a
     * fresh handshake against a DC that had merely been slow.
     *
     * Three conditions must ALL hold, and each one is a different way this could
     * otherwise become a lie:
     *
     *   - it IS a read timeout (isReadTimeout, not a re-derived message test):
     *     a genuine EOF or a hard read error is a CLOSED peer, and reading a
     *     closed socket again cannot succeed — it can only convert a prompt
     *     failure into a hang.
     *   - attempts remain (READ_TIMEOUT_MAX_ATTEMPTS): the ladder is bounded, so
     *     a DC that missed two consecutive read budgets fails the call instead of
     *     holding the caller for ever. Two misses is not "slow", it is gone.
     *   - something is cached to retry ON ($conn !== null): a timeout raised
     *     while DIALLING has no open socket behind it, so there is nothing to
     *     keep and no reason to pretend. That case falls through to the ordinary
     *     eviction, which is a no-op on an empty cache.
     *
     * Logging the retry is a side effect of saying yes, deliberately: the retried
     * timeout must be visible as its own event so it never disappears into the
     * terminal 'rpc.failed' record.
     *
     * @param  array<string, mixed>  $context  the caller's own log context (method or count)
     */
    private function shouldRetryOnCachedConnection(RuntimeException $e, int $attempt, array $context): bool
    {
        if (! StreamSocket::isReadTimeout($e)) {
            return false;
        }

        if ($attempt >= self::READ_TIMEOUT_MAX_ATTEMPTS || $this->conn === null) {
            return false;
        }

        $this->logger->warning(self::READ_TIMEOUT_RETRY_EVENT, array_merge($context, [
            'attempt' => $attempt,
            'max_attempts' => self::READ_TIMEOUT_MAX_ATTEMPTS,
            'timeout' => $e->getMessage(),
            'socket_open' => true,
        ]));

        return true;
    }

    /**
     * Lazily handshakes (when the session has no 256-byte auth key) and opens
     * the encrypted connection exactly once; later live calls reuse it.
     *
     * A freshly generated auth key is only known to the server on the socket
     * that created it until first use (server replies -404 otherwise), so the
     * handshake's PlainConnection socket is promoted into the EncryptedConnection.
     */
    protected function ensureConnection(): EncryptedConnection|WebProxyConnection
    {
        if ($this->conn !== null) {
            return $this->conn;
        }

        [$host, $port] = $this->resolveHost();
        $dcId = $this->session->dcId ?? 2;

        $this->logger->info('teleframe.mtproto.connect.start', [
            'dc' => $dcId,
            'host' => $host,
            'port' => $port,
            'live' => $this->isLive(),
        ]);

        try {
            $socket = null;
            $session = $this->ensureAuthKey($host, $port, $socket);
            $conn = $this->connectEncrypted($session, $host, $port, $socket);
        } catch (Throwable $e) {
            $this->logger->error('teleframe.mtproto.connect.failed', [
                'dc' => $dcId,
                'host' => $host,
                'port' => $port,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $e;
        }

        $this->logger->info('teleframe.mtproto.connect.ok', [
            'dc' => $dcId,
            'host' => $host,
            'port' => $port,
        ]);

        return $this->conn = $conn;
    }

    /**
     * Test-only seam: forces a fresh handshake (if needed) to the given host
     * and issues a single help.getNearestDc probe. Never caches a connection.
     *
     * @return array<string, mixed>
     */
    public function callToHost(string $host, int $port = 443): array
    {
        $socket = null;
        $conn = $this->connectEncrypted($this->ensureAuthKey($host, $port, $socket), $host, $port, $socket);
        try {
            return $conn->call('help.getNearestDc');
        } finally {
            $conn->close();
        }
    }

    /**
     * Runs the DH handshake via PlainConnection unless the session already
     * carries a full 256-byte auth key; returns the session to connect with
     * (and stores a freshly generated one on the client). When a handshake
     * happens, its socket is stashed on $promotedSocket so the caller can
     * continue encrypted traffic on the same connection the key was born on.
     */
    protected function ensureAuthKey(string $host, int $port, &$promotedSocket = null): SessionData
    {
        if ($this->session === null) {
            throw new RuntimeException('MTProto live call: no session');
        }
        if (strlen($this->session->authKey) === 256) {
            return $this->session;
        }

        $plain = PlainConnection::connect($host, $port, logger: $this->logger);
        try {
            $generated = AuthKeyFactory::generate($plain, $this->session->dcId, $this->logger);
            $this->logger->info('teleframe.mtproto.handshake.ok', [
                'dc' => $this->session->dcId ?? 2,
            ]);
            $promotedSocket = $plain->socket; // keep the socket open: key is connection-bound until first use

            // Mutate the existing session object in place rather than swapping
            // in the freshly generated one: callers hold a reference to their
            // SessionData (e.g. TeleframeAuthService::sendCodeOnDc) and export
            // it after the call returns — a replacement object would be lost
            // and the next request would handshake a DIFFERENT auth key, which
            // breaks auth.sendCode → auth.signIn (symptom: PHONE_CODE_EXPIRED).
            $this->absorbGeneratedKey($generated);

            return $this->session;
        } catch (Throwable $e) {
            $plain->close();
            throw $e;
        }
    }

    /**
     * Copies freshly generated key material onto the EXISTING session object.
     * Never replace the object: the auth key is only useful to the code that
     * requested it (auth.sendCode mints its phone_code_hash against this key),
     * so any later request must handshake zero times and reuse the same key.
     */
    protected function absorbGeneratedKey(SessionData $generated): void
    {
        $this->session->authKey = $generated->authKey;
        $this->session->serverTimeDelta = $generated->serverTimeDelta;
    }

    /**
     * Opens the encrypted connection, carrying this client's apiId into
     * initConnection (EncryptedConnection::connect() cannot take an apiId).
     * When $promotedSocket is given (the socket the auth key was generated
     * on), it is reused instead of dialing fresh.
     */
    protected function connectEncrypted(SessionData $session, string $host, int $port, $promotedSocket = null): EncryptedConnection|WebProxyConnection
    {
        $proxyType = strtolower((string) ($this->proxyConfig['type'] ?? ''));

        if ($proxyType === 'web') {
            if ($promotedSocket !== null) {
                // For promoted socket with WEB proxy, we need to wrap it
                // This is a rare case - typically WEB proxy doesn't use promoted sockets
                throw new RuntimeException('WebProxyConnection: promoted socket not supported for WEB proxy');
            }
            try {
                $conn = WebProxyConnection::connect(
                    session: $session,
                    host: $host,
                    port: $port,
                    proxy: $this->proxyConfig,
                    logger: $this->logger,
                    cafile: $this->tlsCaFile,
                    peerName: $this->tlsPeerName,
                    tls: $this->tlsEnabled
                );
                $conn->setApiId($this->apiId);
            } catch (RuntimeException $e) {
                throw new RuntimeException("MTProto WEB proxy connect to {$host}:{$port} failed: ".$e->getMessage(), 0, $e);
            }

            return $conn;
        }

        if ($promotedSocket !== null) {
            return new EncryptedConnection($session, $promotedSocket, $this->apiId, $this->logger);
        }
        try {
            $socket = StreamSocket::createConnection(
                host: $host,
                port: $port,
                proxy: $this->proxyConfig,
                logger: $this->logger,
                cafile: $this->tlsCaFile,
                peerName: $this->tlsPeerName,
                tls: $this->tlsEnabled
            );
            FrameCodec::writeInit($socket, $this->logger);
        } catch (RuntimeException $e) {
            throw new RuntimeException("MTProto connect to {$host}:{$port} failed: ".$e->getMessage(), 0, $e);
        }

        return new EncryptedConnection($session, $socket, $this->apiId, $this->logger);
    }

    /** @return array{0: string, 1: int} */
    protected function resolveHost(): array
    {
        $dcId = $this->session->dcId ?? 2;

        return [self::DC_IPS[$dcId] ?? self::DC_IPS[2], self::DEFAULT_PORT];
    }

    /**
     * Closes any open live connection and drops the cached handle.
     */
    public function close(): void
    {
        $this->conn?->close();
        $this->conn = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Helper to compute 2FA SRP parameters when Telegram returns SESSION_PASSWORD_NEEDED.
     *
     * @param  array<string, mixed>  $accountPassword
     * @return array{srp_id: int, A: string, M1: string}
     */
    public function compute2faProof(array $accountPassword, string $password): array
    {
        return PasswordCalculator::computeSrpProof($accountPassword, $password);
    }
}
