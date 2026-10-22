<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\MtprotoServerSession;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\PlainConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PacketCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\ServerDhHandshake;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSerializer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;

/**
 * VaultMtprotoAnswerer — the everglide which plays "Telegram" to a browser-
 * shaped MTProto client over the /apiws WebSocket terminal, so the vault
 * credential never enters the browser.
 *
 * The answerer consumes the WS obfuscated2 plaintext duplex (a
 * WebSocketObfuscated2Link built from the cipher + lead the
 * WebSocketRelayServer solution handed it) and runs two sequential phases:
 *
 *   1. Transparent DH (ServerDhHandshake): req_pq_multi -> resPQ,
 *      req_DH_params -> server_DH_params_ok, set_client_DH_params ->
 *      dh_gen_ok. The browser seats a relay-local auth key while the vault
 *      credential stays server-side. Every transparent message is an
 *      unencrypted envelope (auth_key_id zero || msg_id || length || body)
 *      inside abridged framing, exactly what PlainConnection produces.
 *   2. Encrypted session (MtprotoServerSession): the FIRST encrypted
 *      envelope reveals the browser's session_id (salt is still zero), the
 *      server answers with new_session_created (adopting the browser's
 *      session_id and handing it the real salt), and every subsequent
 *      decoded request is dispatched against the VAULT scope via the
 *      injected dispatch callable
 *      `(string $method, array $params, ?array $ticketGrant): array`; the
 *      result is re-encoded as an rpc_result to the browser. auth.*
 *      methods are answered LOCALLY (never forwarded to the vault), so the
 *      stock QR/phone flow can never mint a token or SMS against the real
 *      account and the vault can never be logged out through the client.
 *      A per-method local-answer table manufactures the client-auth
 *      primitives (loginTokenSuccess / auth.authorization / boolTrue /
 *      accountDaysTTL / Vector<User>) so the browser observes the vault
 *      identity as ALREADY AUTHORIZED — auth.exportLoginToken is
 *      AUTHORITATIVE (it answers loginTokenSuccess immediately, so the
 *      browser's very first auth poll skips the stock QR screen) and the
 *      fake self user is hand-packed with the browser's constructor id
 *      (user#b1b8cc83, the vendored gramjs layer) because the local registry
 *      carries user#31774388 (the layer-227 wire id the vault client needs).
 *
 * The dispatch callable is exactly what RelayVaultTerminalFactory::dispatch()
 * builds for RelayCommand -- so the same WS session re-seats onto whichever
 * vault account is ACTIVE (RelayActiveAccount generation) on the next RPC.
 *
 * Endpoint-session model (verbatim request #4): EVERY dial runs the
 * transparent DH below — the browser seats a fresh relay-local auth key per
 * connection against OUR endpoint (the ticket already granted the vault
 * account at the WS upgrade), so each Laravel user gets a SEPARATE NEW
 * session and the real vault authKey never leaves the server. There is no
 * seeded-dial path: a browser can present no vault auth_key_id because the
 * vault authKey can never be seeded to the client in any payload.
 *
 * Silent by default (NullLogger), PSR-3 metadata only. Zero regex: string
 * functions / unpack only, per the src/Core discipline.
 *
 * Auth (W-02): the answerer only runs the vault session for sockets the
 * WebSocketRelayServer authenticated with a one-shot relay ticket — the 6th
 * invoke argument is the granted session (a bare account id, or an identity
 * array {account_id, user_id} so the terminal's dispatch policy sees the
 * Laravel user behind the ticket). `$ticketRequired` defaults to true: an
 * invoke with no granted session (no validator configured on the relay, or
 * a socket that slipped past the gate) is closed immediately as a protocol
 * error, before any DH step or non-auth RPC dispatch can run.
 */
final class VaultMtprotoAnswerer
{
    private const MAX_FRAME = 2 * 1024 * 1024;

    private const READ_TIMEOUT_S = 5;

    /**
     * Per-connection budgets. Without these the serve loop had exactly one
     * exit — nextFrame() returning null, which happens only on the 5s read
     * timeout — so a peer sending one byte every five seconds pinned a PHP
     * process and a socket for ever. No max lifetime, no message count, no
     * byte budget, no total-served counter. `ping_delay_disconnect` was
     * ANSWERED but never enforced, which is the same gap wearing a hat.
     *
     * A browser MTProto session is short-lived, so these defaults do not
     * constrain a real client; they make a hostile or wedged one finite.
     */
    private const MAX_LIFETIME_S = 900;

    private const MAX_MESSAGES = 5000;

    private const MAX_BYTES = 64 * 1024 * 1024;

    /** Consecutive empty reads before the read path is declared wedged. */
    private const MAX_EMPTY_READS = 120;

    /** The vault account the terminal pretends the browser already authorized. */
    private const FAKE_USER_ID = 1724372757;

    /** The vendored gramjs browser's user ctor id (user#b1b8cc83). */
    private const BROWSER_USER_CTOR = 0xB1B8CC83;

    /** accountDaysTTL handed to account.getAccountTtl. */
    private const ACCOUNT_TTL_DAYS = 365;

    /** @var callable(string, array<string, mixed>, array{account_id: int, user_id: int|string|null}|null): array<string, mixed> */
    private $dispatch;

    private LoggerInterface $logger;

    private bool $ticketRequired;

    public function __construct(
        callable $dispatch,
        private readonly string $rsaPrivateKeyPem,
        ?LoggerInterface $logger = null,
        bool $ticketRequired = true,
    ) {
        $this->dispatch = $dispatch;
        $this->logger = $logger ?? new NullLogger;
        $this->ticketRequired = $ticketRequired;
    }

    /**
     * The WebSocketRelayServer answerer seam: terminate one browser client
     * through the DH handshake and the vault-dispatched encrypted session to
     * completion. The client socket is closed by the link on exit.
     *
     * @param  resource  $client  the WebSocket client socket (post-handshake)
     * @param  string  $lead  decrypted-at-64 payload bytes that arrived with
     *                        the 64-byte header
     * @param  int|array{account_id: int, user_id: int|string|null}|null  $ticketGrant
     *                                                                                  the granted session from the one-shot relay
     *                                                                                  ticket the relay redeemed at the WS upgrade: a
     *                                                                                  bare account id (legacy validators) or an
     *                                                                                  identity array {account_id, user_id} (null = no
     *                                                                                  validator was configured). When $ticketRequired
     *                                                                                  is true, null closes the socket with a protocol
     *                                                                                  error before anything runs.
     */
    public function __invoke($client, Obfuscated2Cipher $cipher, string $lead, int $dcId, string $protocol, int|array|null $ticketGrant = null): void
    {
        $grant = self::normalizeTicketGrant($ticketGrant);
        if ($this->ticketRequired && $grant === null) {
            $this->logger->warning('teleframe.mtproto.answerer.refused.unauthenticated');

            return;
        }

        $link = new WebSocketObfuscated2Link($client, $cipher, $lead, $this->logger);

        $buffer = '';
        $phase = 'handshake';
        $authKey = null;

        // The budgets are armed BEFORE the DH handshake, not after it: the
        // handshake is exactly the phase an unauthenticated-but-ticketed peer
        // controls, so it must be bounded by the same caps as the session.
        $startedAt = time();
        $emptyReads = 0;

        try {
            // Endpoint-session dial: every envelope of this phase is a
            // transparent DH message (auth_key_id zero). Seat a relay-local key
            // through ServerDhHandshake — the browser's own fresh
            // per-connection session, minted against OUR endpoint. The vault
            // authKey never leaves the server, so there is no seeded
            // (already-encrypted) dial path by construction.
            //
            // The FIRST read lives INSIDE this loop, not before it. The
            // handshake is the phase a ticketed peer controls most directly, so
            // a peer that redeems a ticket and then goes silent — before or
            // after its req_pq_multi — must meet the same caps as the session.
            // Both budgets are checked BEFORE every read, so termination never
            // depends on the peer saying anything.
            $handshake = new ServerDhHandshake($this->rsaPrivateKeyPem, $this->logger);
            while (true) {
                if ($this->handshakeBudgetSpent($startedAt, $emptyReads)) {
                    return;
                }

                $frame = $this->nextFrame($link, $buffer, $emptyReads);
                if ($frame === null) {
                    return;
                }
                if ($frame === '') {
                    // Read window expired with no frame: loop back so the gate
                    // above re-reads the empty-read counter.
                    continue;
                }

                $this->serveTransparent($link, $handshake, $frame);
                if ($handshake->state() === 'seated') {
                    break;
                }
            }

            $authKey = (string) $handshake->authKey();

            $session = null;
            $phase = 'session';

            $served = 0;
            $bytes = 0;

            while (true) {
                // Every budget is checked BEFORE the read, so the loop's
                // termination never depends on the peer saying anything.
                if (time() - $startedAt >= self::MAX_LIFETIME_S) {
                    $this->logger->info('teleframe.mtproto.answerer.budget_lifetime', [
                        'served' => $served,
                        'bytes' => $bytes,
                    ]);
                    return;
                }

                if ($served >= self::MAX_MESSAGES || $bytes >= self::MAX_BYTES) {
                    $this->logger->info('teleframe.mtproto.answerer.budget_volume', [
                        'served' => $served,
                        'bytes' => $bytes,
                    ]);
                    return;
                }

                $frame = $this->nextFrame($link, $buffer, $emptyReads);

                if ($emptyReads >= self::MAX_EMPTY_READS) {
                    $this->logger->info('teleframe.mtproto.answerer.budget_wedged', [
                        'served' => $served,
                        'empty_reads' => $emptyReads,
                    ]);
                    return;
                }

                if ($frame === null) {
                    return;
                }
                if ($frame === '') {
                    continue;
                }

                $served++;
                $bytes += strlen($frame);

                if ($session === null) {
                    // The browser's first encrypted envelope still carries
                    // salt zero; decrypting it (no expected session id yet)
                    // reveals the session_id the browser generated, which
                    // every server-direction frame MUST echo. New session on
                    // it, hand the client its real salt, then serve the frame.
                    $first = PacketCodec::decryptPacket($frame, $authKey, fromServer: false);
                    $session = new MtprotoServerSession(
                        $authKey,
                        $first['session_id'],
                        self::serverSalt(),
                        $this->logger,
                    );
                    $link->writePlain(FrameCodec::wrapAbridgedPayload($session->serverSessionCreated()));
                }

                $this->serveEncrypted($link, $session, $frame, $grant);
            }
        } catch (RuntimeException $e) {
            $this->logger->debug('teleframe.mtproto.answerer.closed', [
                'phase' => $phase,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        } finally {
            $link->close();
        }
    }

    /**
     * The handshake-phase budget gate. The DH exchange runs BEFORE any
     * session budget was armed, so without this a peer that redeems a real
     * relay ticket, sends a valid req_pq_multi and then goes silent holds a
     * PHP process and its socket open for ever.
     */
    private function handshakeBudgetSpent(int $startedAt, int $emptyReads): bool
    {
        if (time() - $startedAt >= self::MAX_LIFETIME_S) {
            $this->logger->info('teleframe.mtproto.answerer.budget_lifetime', [
                'phase' => 'handshake',
                'empty_reads' => $emptyReads,
            ]);

            return true;
        }

        if ($emptyReads >= self::MAX_EMPTY_READS) {
            $this->logger->info('teleframe.mtproto.answerer.budget_wedged', [
                'phase' => 'handshake',
                'empty_reads' => $emptyReads,
            ]);

            return true;
        }

        return false;
    }

    /**
     * One DH round-trip: plain envelope in, plain envelope out.
     */
    private function serveTransparent(WebSocketObfuscated2Link $link, ServerDhHandshake $handshake, string $frame): void
    {
        $body = PlainConnection::parsePlainEnvelope($frame);
        $offset = 0;
        $request = TLDecoder::decodeObject($body, $offset);
        $response = (array) $handshake->handle((object) $request);
        $name = (string) ($response['_'] ?? '');

        $this->logger->debug('teleframe.mtproto.answerer.dh', ['step' => $request['_'] ?? '?']);

        $link->writePlain(FrameCodec::wrapAbridgedPayload(
            PlainConnection::buildPlainEnvelope(
                TLEncoder::encodeObject($name, $response),
                self::transparentServerMessageId(),
            ),
        ));
    }

    /** One encrypted envelope: decode, dispatch, answer; ack the batch. */
    private function serveEncrypted(WebSocketObfuscated2Link $link, MtprotoServerSession $session, string $frame, ?array $grant): void
    {
        $requests = $session->decodeRequest($frame);
        $acked = [];

        foreach ($requests as $request) {
            $msgId = $request['original_msg_id'];
            $method = (string) $request['method'];
            $acked[] = $msgId;

            if ($method === 'ping' || $method === 'ping_delay_disconnect') {
                $link->writePlain(FrameCodec::wrapAbridgedPayload($session->encodePong($msgId)));

                continue;
            }

            // Transport-level service traffic is local by construction:
            // msgs_ack acknowledges THIS relay's own server-side message ids
            // (new_session_created / rpc_result) — forwarding it upstream as
            // an RPC would make it the first-call on a fresh upstream session
            // and die with an upstream EOF. A client pong (reply to a relay
            // ping) is symmetrical. Both are still counted in the batch ack.
            if ($method === 'msgs_ack' || $method === 'pong') {
                $this->logger->debug('teleframe.mtproto.answerer.service.local', ['method' => $method]);

                continue;
            }

            $link->writePlain(FrameCodec::wrapAbridgedPayload(
                $session->encodeResponse($msgId, $method, $this->answerMethod($method, $request['params'], $grant)),
            ));
        }

        if ($acked !== []) {
            $link->writePlain(FrameCodec::wrapAbridgedPayload($session->encodeAck(...$acked)));
        }
    }

    /**
     * Maps one decoded RPC to a decoded result array. A per-method local-
     * answer table (keyed by exact method string, never regex) manufactures
     * the client-auth primitives so the browser observes the vault identity
     * as ALREADY AUTHORIZED: auth.exportLoginToken is AUTHORITATIVE (the
     * already-authorized loginTokenSuccess hook — see importLoginTokenAnswer
     * — so the browser's very first auth poll transitions straight to
     * authorized and never parks on the stock QR screen), auth.importLoginToken
     * / auth.importAuthorization answer the already-authorized hook, the
     * multi-DC handoff ships the exported fake identity, and the heartbeat
     * Bool + self fetch (root Vector<User> of the fake self user) complete
     * the table. Every OTHER auth.* keeps the historical Bool answer so the
     * stock login flow can never mint a token or SMS against the real account
     * and the vault can never be logged out. Everything else goes to the vault
     * dispatch, which is what makes checkAuthorization (updates.getState /
     * help.getConfig) observe the vault identity as already-authenticated. A
     * failed vault dispatch is surfaced as an rpc_error so GramJS handles it
     * instead of hanging.
     *
     * @param  array<string, mixed>  $params
     * @param  array{account_id: int, user_id: int|string|null}|null  $grant
     *                                                                        the normalized
     *                                                                        ticket grant
     *                                                                        (account + user)
     *                                                                        handed through to
     *                                                                        the dispatch for
     *                                                                        the connection
     * @return array<string, mixed>
     */
    private function answerMethod(string $method, array $params, ?array $grant): array
    {
        $local = match ($method) {
            'auth.exportLoginToken' => $this->importLoginTokenAnswer(),
            'auth.importLoginToken' => $this->importLoginTokenAnswer(),
            'auth.importAuthorization' => $this->importAuthorizationAnswer(),
            'auth.exportAuthorization' => $this->exportAuthorizationAnswer(),
            'account.registerDevice' => ['_' => 'boolTrue'],
            'account.getAccountTtl' => $this->getAccountTtlAnswer(),
            'users.getUsers' => $this->getUsersAnswer(),
            default => null,
        };

        if ($local !== null) {
            $this->logger->warning('teleframe.mtproto.answerer.auth.local', ['method' => $method]);

            return $local;
        }

        if (str_starts_with($method, 'auth.')) {
            // Any auth.* method outside the table above keeps the historical
            // Bool answer (valid for auth.cancelCode / auth.logOut): the
            // vault never sees an auth RPC.
            $this->logger->warning('teleframe.mtproto.answerer.auth.local', ['method' => $method]);

            return ['_' => 'boolTrue'];
        }

        try {
            $result = ($this->dispatch)($method, $params, $grant);
        } catch (Throwable $e) {
            $this->logger->error('teleframe.mtproto.answerer.dispatch.failed', [
                'method' => $method,
                'exception' => $e::class,
            ]);

            return [
                '_' => 'rpc_error',
                'error_code' => 500,
                'error_message' => 'TELEFRAME_RELAY_FAILED',
            ];
        }

        if (! is_string($result['_'] ?? null) || $result['_'] === '') {
            throw new RuntimeException(sprintf(
                'VaultMtprotoAnswerer: dispatch of %s must return a decoded result array carrying "_"',
                $method,
            ));
        }

        return $result;
    }

    /**
     * The "already authorized" hook: auth.loginTokenSuccess carrying an
     * auth.authorization whose user is the fake vault self. Auth flows that
     * import a login token observe an authorized account immediately.
     *
     * Also the AUTHORITATIVE answer to auth.exportLoginToken (verbatim
     * request #4): the browser's first auth poll skips the stock QR/phone
     * screen entirely — the ticket already granted the account and this
     * endpoint is authoritative, so authorization is conceded up front
     * instead of handing out a login token.
     *
     * @return array<string, mixed>
     */
    private function importLoginTokenAnswer(): array
    {
        return [
            '_' => 'preencoded',
            'body' => TLSerializer::packInt(TLRegistry::id('auth.loginTokenSuccess'))
                .$this->authorizationBody(),
        ];
    }

    /**
     * Companion-app / multi-DC route to already-authorized: a full
     * auth.authorization with the fake self user.
     *
     * @return array<string, mixed>
     */
    private function importAuthorizationAnswer(): array
    {
        return ['_' => 'preencoded', 'body' => $this->authorizationBody()];
    }

    /**
     * Multi-DC handoff: the exported auth of the fake vault identity, so a
     * migrated client can re-import onto another DC.
     *
     * @return array<string, mixed>
     */
    private function exportAuthorizationAnswer(): array
    {
        return [
            '_' => 'auth.exportedAuthorization',
            'id' => self::FAKE_USER_ID,
            'bytes' => random_bytes(32),
        ];
    }

    /**
     * accountDaysTTL {days} — the vault account's TTL, already-authorized.
     *
     * @return array<string, mixed>
     */
    private function getAccountTtlAnswer(): array
    {
        return ['_' => 'accountDaysTTL', 'days' => self::ACCOUNT_TTL_DAYS];
    }

    /**
     * self fetch: a root Vector<User> of one fake self user. The vector
     * wrapper (0x1cb5c415) cannot go through TLEncoder — MtprotoServerSession
     * hand-packs '_' => 'Vector' results.
     *
     * @return array<string, mixed>
     */
    private function getUsersAnswer(): array
    {
        return ['_' => 'Vector', 'items' => [self::fakeSelfUser()]];
    }

    /**
     * The captured userInfo used in place of the vault: id 1724372757,
     * self => true, a minimal-but-valid shape for gramjs hydration. The
     * '_id' marker carries the BROWSER's user constructor id (user#b1b8cc83)
     * — the local registry knows user#31774388 (the layer-227 wire id the
     * vault client needs), so MtprotoServerSession splices the browser id in
     * when encoding this object (see MtprotoServerSession::encodeDecodedObject).
     *
     * @return array<string, mixed>
     */
    private static function fakeSelfUser(): array
    {
        return [
            '_' => 'user',
            '_id' => self::BROWSER_USER_CTOR,
            'self' => true,
            'id' => self::FAKE_USER_ID,
            'access_hash' => 0,
            'first_name' => 'Ana',
            'last_name' => '',
            'username' => 'vault',
            'phone' => '1724372757',
            'status' => ['_' => 'userStatusOnline', 'expires' => time() + 300],
        ];
    }

    /**
     * auth.authorization {user: User} as raw TL bytes (flags word zero, no
     * tmp_sessions / setup_password_required / future_auth_token), with the
     * nested user hand-packed under the browser's user ctor id. Nested
     * objects cannot reach a ctor-id override through TLEncoder, so the whole
     * body is pre-packed here and passed through MtprotoServerSession
     * untouched ('_' => 'preencoded').
     */
    private function authorizationBody(): string
    {
        return TLSerializer::packInt(TLRegistry::id('auth.authorization'))
            .TLSerializer::packInt(0)
            .TLSerializer::packInt(self::BROWSER_USER_CTOR)
            .substr($this->registryUserBody(), 4);
    }

    /** The registry-encoded user body (user#31774388 + fields). */
    private function registryUserBody(): string
    {
        return TLEncoder::encodeObject('user', self::fakeSelfUser());
    }

    /**
     * Returns the next complete abridged frame by draining the link's
     * decrypted stream into $buffer.
     *
     * @param  string  $buffer  in/out partial-frame carry
     * @return string|null the frame payload, '' when the read window expired
     *                     with nothing buffered (the caller's
     *                     MAX_EMPTY_READS cap decides whether that is fatal),
     *                     or null when the connection is over
     */
    /**
     * @param  int|null  $emptyReads  consecutive 5s read timeouts so far (by
     *                                 ref, incremented/reset here); the serve
     *                                 loops cap it so a silent peer cannot
     *                                 hold the connection for ever
     */
    private function nextFrame(WebSocketObfuscated2Link $link, string &$buffer, ?int &$emptyReads = null): ?string
    {
        for (; ;) {
            $payload = $this->parseFrame($buffer);
            if ($payload !== null) {
                return $payload;
            }

            $chunk = $link->readPlain(self::READ_TIMEOUT_S);
            if ($chunk === null) {
                return null;
            }
            if ($chunk === '') {
                // A 5s read timeout lands here. RETURN, never `continue`:
                // looping back into parseFrame() would re-read inside this
                // method, so the caller's MAX_EMPTY_READS cap (and the
                // handshake-phase lifetime gate) would never be reachable —
                // the very wedge these budgets exist to close. Returning ''
                // hands the decision back to the caller, which caps it.
                if ($emptyReads !== null) {
                    $emptyReads++;
                }

                return '';
            }
            if ($emptyReads !== null) {
                $emptyReads = 0;
            }
            $buffer .= $chunk;
        }
    }

    /**
     * Parses one abridged frame (varint len/4 prefix + payload) out of
     * $buffer, removing it. Returns null when fewer complete frames are
     * buffered yet.
     *
     * @param  string  $buffer  in/out
     */
    private function parseFrame(string &$buffer): ?string
    {
        if (strlen($buffer) < 1) {
            return null;
        }

        $len4 = ord($buffer[0]);
        $header = 1;
        if ($len4 >= 0x7F) {
            if (strlen($buffer) < 4) {
                return null;
            }
            $len4 = unpack('V', substr($buffer, 1, 3)."\x00")[1];
            $header = 4;
        }

        $len = $len4 << 2;
        if ($len < 1 || $len > self::MAX_FRAME) {
            throw new RuntimeException(sprintf('VaultMtprotoAnswerer: invalid abridged frame length %d', $len));
        }
        if (strlen($buffer) < $header + $len) {
            return null;
        }

        $payload = substr($buffer, $header, $len);
        $buffer = substr($buffer, $header + $len);

        return $payload;
    }

    private static function serverSalt(): int
    {
        return random_int(1, PHP_INT_MAX);
    }

    /**
     * Normalize a raw ticket grant into a canonical identity array, or null
     * when the grant cannot name an account. A bare int (the historical
     * validator shape) binds no user; an identity array {account_id, user_id}
     * carries the Laravel user captured at mint time. An int below 1, an
     * array whose account_id is missing or below 1, or any other shape is
     * refused.
     *
     * @return array{account_id: int, user_id: int|string|null}|null
     */
    public static function normalizeTicketGrant(int|array|null $grant): ?array
    {
        if (is_int($grant)) {
            return $grant >= 1 ? ['account_id' => $grant, 'user_id' => null] : null;
        }

        if (! is_array($grant)) {
            return null;
        }

        $accountId = isset($grant['account_id']) && is_int($grant['account_id'])
            ? $grant['account_id']
            : null;
        if ($accountId === null || $accountId < 1) {
            return null;
        }

        $userId = $grant['user_id'] ?? null;
        if (! is_int($userId) && ! is_string($userId)) {
            $userId = null;
        }
        if (is_string($userId) && $userId === '') {
            $userId = null;
        }

        return ['account_id' => $accountId, 'user_id' => $userId];
    }

    /**
     * Transparent-message server message_id: odd, ≡ 3 (mod 4) — the plain
     * message convention mirror of the server messages MtprotoServerSession
     * writes (odd, ≡ 1 mod 8 after the & ~7 floor).
     */
    private static function transparentServerMessageId(): int
    {
        return (((int) (microtime(true) * 2 ** 32)) & ~7) | 3;
    }
}
