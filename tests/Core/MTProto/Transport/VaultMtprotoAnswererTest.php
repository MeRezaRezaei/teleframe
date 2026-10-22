<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\PlainConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\AesIge;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\AuthKeyFactory;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PacketCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PqFactorizer;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSerializer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Cipher;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\VaultMtprotoAnswerer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketRelayServer;
use phpseclib3\Crypt\RSA;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Loopback proof of the vault /apiws terminal (design §4.1, criterion 3) —
 * NO external network, no real DCs, no credentials.
 *
 * A forked child runs one WebSocketRelayServer serveOnce whose answerer seam
 * IS the VaultMtprotoAnswerer (real dispatch callable over the real seam
 * invocation). The parent plays the browser: RFC6455 handshake, obfuscated2
 * header, the FULL client-side DH exchange against the answerer's server role
 * (req_pq_multi -> resPQ -> req_DH_params -> server_DH_params_ok ->
 * set_client_DH_params -> dh_gen_ok, authenticating the relay's RSA
 * fingerprint and new_nonce_hash1 so the derived browser key equals the
 * relay's seated key), then the encrypted session: a first
 * invokeWithLayer(227)/initConnection(help.getConfig) envelope with salt 0
 * (session_id discovery), new_session_created salt handoff, and the
 * rpc_result reply.
 *
 * Assertion A: the recorded vault dispatch is help.getConfig; the fake vault
 * scope's client->getSession() (RelayFrameDialBridge::seated() shape) is the
 * vault SessionData, never an anonymous auth.
 * Assertion B: the reply decrypts with the browser key (fromServer:true) to
 * an rpc_result echoing the request msg_id with the canned vault result.
 * Assertion C: a dispatch-side generation bump (RelayActiveAccount::switchTo
 * stand-in) re-seats the second request on vault account B's scope while the
 * browser key and session stay unchanged.
 * Assertion D: the per-method local-answer table manufactures already-authorized
 * client-auth primitives — updates.getState stays dispatched (probe), while
 * users.getUsers (root Vector<User>), auth.exportLoginToken (authoritative
 * loginTokenSuccess), auth.importLoginToken (loginTokenSuccess),
 * auth.importAuthorization
 * (auth.authorization), account.registerDevice (boolTrue) and
 * account.getAccountTtl (accountDaysTTL) never reach the vault. The fake self
 * user is hand-packed under the BROWSER's user#b1b8cc83 (the vendored gramjs
 * layer beats the registry's layer-227 user#31774388), proven by decoding with
 * an overlaid browser user line while the forked answerer keeps the original
 * registry (so the ctor splice in MtprotoServerSession is exercised, not
 * masked).
 *
 * Bounded and deterministic (deadline-guarded reads); a stuck child can never
 * hang CI.
 */
final class VaultMtprotoAnswererTest extends TestCase
{
    private const DEADLINE_S = 5.0;

    private const POLL_INTERVAL_US = 1000;

    /** Decrypted abridged bytes not yet peeled into an envelope (carried across nextEnvelope calls). */
    private string $inPlain = '';

    /** Raw WS bytes not yet framed (carried across nextEnvelope calls). */
    private string $inWire = '';

    /** Leftover probe bytes so a multi-line burst is not dropped (W-02). */
    private string $probeBuffer = '';

    protected function setUp(): void
    {
        $this->probeBuffer = '';
    }

    /** @var array{0: string, 1: string}|null [private PKCS1, public PKCS1] — the relay's DH keypair */
    private static ?array $pair = null;

    /** @return array{0: string, 1: string} */
    private static function pair(): array
    {
        if (self::$pair === null) {
            $key = RSA::createKey(2048);
            self::$pair = [$key->toString('PKCS1'), $key->getPublicKey()->toString('PKCS1')];
        }

        return self::$pair;
    }

    /** A vault-shaped SessionData (dc 1, real 256-byte key) for a fake scope. */
    private function vaultSession(string $key): SessionData
    {
        return new SessionData(dcId: 1, authKey: $key);
    }

    public function test_answerer_dispatches_the_first_vault_rpc_and_replies_rpc_result(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked answerer child');
        }

        [$private, $public] = self::pair();
        $vaultKey = random_bytes(256);

        // The fake vault "upstream": RelayFrameDialBridge::seated() shape —
        // a client exposing getSession() + call($method, $params). Dispatch
        // records what it called over the probe socket (the forked child's
        // memory is copy-on-write, so the socket is the ONLY cross-process
        // channel — never assert on a by-ref array captured pre-fork).
        [$probeRelay, $probeTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $dispatch = static function (string $method, array $params) use ($probeRelay, $vaultKey): array {
            fwrite($probeRelay, json_encode(['scope' => 'A', 'method' => $method, 'vault_key_sha' => hash('sha256', $vaultKey)])."\n");

            return ['_' => 'boolTrue'];
        };

        $answerer = new VaultMtprotoAnswerer($dispatch, $private);
        // W-02: the terminal now requires a one-shot relay ticket at the WS
        // upgrade. Wrap the answerer to record the ticket-granted account id
        // the relay passes through — proving the socket is session-bound.
        $wrappedAnswerer = static function ($client, $cipher, $lead, int $dcId, string $protocol, ?int $ticketAccountId = null) use ($answerer, $probeRelay): void {
            fwrite($probeRelay, json_encode(['granted_account' => $ticketAccountId])."\n");
            ($answerer)($client, $cipher, $lead, $dcId, $protocol, $ticketAccountId);
        };
        $relay = new WebSocketRelayServer('127.0.0.1', 0, answerer: $wrappedAnswerer, ticketValidator: static fn (string $ticket): ?int => $ticket === 'test-ticket' ? 42 : null);
        $listen = $relay->listen();
        $port = $this->listeningPort($listen);

        $client = null;
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($probeTest);
            $relay->serveOnce($listen);
            self::childExit();
        }

        fclose($probeRelay);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $this->performHandshake($client);

            // The 64-byte obfuscated2 header MUST cross the wire as its own
            // masked WS binary message (as WebSocketRelayServerTest does) —
            // serveOnce collects it to seed the cipher + lead.
            $header = Obfuscated2CodecTest::clientHeader(5, 'abridged');
            $this->sendBinary($client, $header);
            $clientCipher = Obfuscated2Cipher::fromHeader($header);

            // Full client-side DH against the answerer's server role.
            $browserKey = $this->dhHandshake($client, $clientCipher, $public);

            // Encrypted session: salt 0 (unknown during session_id discovery).
            $sessionId = random_int(1, PHP_INT_MAX);
            $firstMsgId = $this->clientTimeBase();
            $body = EncryptedConnection::buildFirstQueryBody(227, [
                'api_id' => 1234,
                'device_model' => 'PHPUnit',
                'system_version' => 'Linux',
                'app_version' => '1.0.0',
                'system_lang_code' => 'en',
                'lang_pack' => '',
                'lang_code' => 'en',
                'query' => ['_' => 'help.getConfig'],
            ]);
            $this->sendEncryptedEnvelope($client, $clientCipher, $body, $browserKey, $sessionId, 0, 1, $firstMsgId);

            // Answers: new_session_created (salt handoff), then rpc_result.
            $newSession = $this->decryptServerEnvelope($this->readEnvelope($client, $clientCipher), $browserKey, $sessionId);
            $offset = 0;
            $nsc = TLDecoder::decodeObject($newSession['payload'], $offset);
            self::assertSame('new_session_created', $nsc['_']);
            self::assertSame($sessionId, $newSession['session_id']);
            $serverSalt = (int) $nsc['server_salt'];

            $rpcEnvelope = $this->decryptServerEnvelope($this->readEnvelope($client, $clientCipher), $browserKey, $sessionId);
            $offset = 0;
            $rpc = TLDecoder::decodeObject($rpcEnvelope['payload'], $offset);

            // --- Assertion B: rpc_result echoes the request msg_id + canned result ---
            self::assertSame('rpc_result', $rpc['_']);
            self::assertSame($firstMsgId, $rpc['req_msg_id']);
            self::assertSame(['_' => 'boolTrue'], $rpc['result']);

            // --- Assertion A: the recorded vault dispatch is help.getConfig,
            // against the vault SessionData (never an anonymous auth). ---
            // First the W-02 grant record (written when the answerer seated
            // the socket), then the dispatch record.
            $granted = $this->readProbeLine($probeTest);
            self::assertNotNull($granted, 'the relay must pass the ticket-granted account id through');
            self::assertSame(42, $granted['granted_account'], 'the socket must be bound to the ticket-granted account');
            $probeLine = $this->readProbeLine($probeTest);
            self::assertNotNull($probeLine, 'dispatch must have written its record');
            self::assertSame(['scope' => 'A', 'method' => 'help.getConfig', 'vault_key_sha' => hash('sha256', $vaultKey)], $probeLine);
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($probeTest);
            fclose($listen);
            $this->reapChild($pid);
        }
    }

    public function test_dispatch_generation_bump_reseats_onto_account_b_without_changing_the_browser_key(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked answerer child');
        }

        [$private, $public] = self::pair();
        $vaultKeyA = random_bytes(256);
        $vaultKeyB = random_bytes(256);
        $genFile = tempnam(sys_get_temp_dir(), 'relay-gen-');
        if ($genFile === false) {
            self::markTestSkipped('could not create a generation file');
        }
        file_put_contents($genFile, '1');

        // RelayVaultTerminalFactory::dispatch stand-in: reads the ACTIVE
        // generation on every dispatch (PSR-16 stand-in = file); when it
        // differs from the seated one, re-seats on the matching scope.
        [$probeRelay, $probeTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $seated = null;
        $dispatch = static function (string $method, array $params) use ($probeRelay, $genFile, $vaultKeyA, $vaultKeyB, &$seated): array {
            $generation = max(1, (int) @file_get_contents($genFile));
            $scope = $generation === 2 ? 'B' : 'A';
            $key = $scope === 'B' ? $vaultKeyB : $vaultKeyA;
            if ($seated !== $scope) {
                // RelayActiveAccount::switchTo() bumped the generation: the
                // terminal re-seats on the new ACTIVE account.
                $seated = $scope;
            }
            fwrite($probeRelay, json_encode(['scope' => $scope, 'seated' => $seated, 'method' => $method, 'vault_key_sha' => hash('sha256', $key)])."\n");

            return ['_' => 'boolTrue'];
        };

        $answerer = new VaultMtprotoAnswerer($dispatch, $private);
        // W-02: the terminal now requires a one-shot relay ticket at the WS
        // upgrade. Wrap the answerer to record the ticket-granted account id
        // the relay passes through — proving the socket is session-bound.
        $wrappedAnswerer = static function ($client, $cipher, $lead, int $dcId, string $protocol, ?int $ticketAccountId = null) use ($answerer, $probeRelay): void {
            fwrite($probeRelay, json_encode(['granted_account' => $ticketAccountId])."\n");
            ($answerer)($client, $cipher, $lead, $dcId, $protocol, $ticketAccountId);
        };
        $relay = new WebSocketRelayServer('127.0.0.1', 0, answerer: $wrappedAnswerer, ticketValidator: static fn (string $ticket): ?int => $ticket === 'test-ticket' ? 42 : null);
        $listen = $relay->listen();
        $port = $this->listeningPort($listen);

        $client = null;
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($probeTest);
            $relay->serveOnce($listen);
            self::childExit();
        }

        fclose($probeRelay);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $this->performHandshake($client);

            // Header as its own masked WS binary message (see the first test).
            $header = Obfuscated2CodecTest::clientHeader(5, 'abridged');
            $this->sendBinary($client, $header);
            $clientCipher = Obfuscated2Cipher::fromHeader($header);

            $browserKey = $this->dhHandshake($client, $clientCipher, $public);
            $sessionId = random_int(1, PHP_INT_MAX);

            // --- First request: account A (generation 1). ---
            $m1 = $this->clientTimeBase();
            $body1 = EncryptedConnection::buildFirstQueryBody(227, [
                'api_id' => 1234,
                'device_model' => 'PHPUnit',
                'system_version' => 'Linux',
                'app_version' => '1.0.0',
                'system_lang_code' => 'en',
                'lang_pack' => '',
                'lang_code' => 'en',
                'query' => ['_' => 'help.getConfig'],
            ]);
            $this->sendEncryptedEnvelope($client, $clientCipher, $body1, $browserKey, $sessionId, 0, 1, $m1);

            $newSession = $this->decryptServerEnvelope($this->readEnvelope($client, $clientCipher), $browserKey, $sessionId);
            $offset = 0;
            $serverSalt = (int) TLDecoder::decodeObject($newSession['payload'], $offset)['server_salt'];

            $firstReply = $this->decryptServerEnvelope($this->readEnvelope($client, $clientCipher), $browserKey, $sessionId);
            $offset = 0;
            $first = TLDecoder::decodeObject($firstReply['payload'], $offset);
            self::assertSame('rpc_result', $first['_'], 'the FIRST reply must be an rpc_result');
            self::assertSame($m1, $first['req_msg_id'], 'the FIRST reply must echo the first request msg_id');

            $granted = $this->readProbeLine($probeTest);
            self::assertNotNull($granted, 'the relay must pass the ticket-granted account id through');
            self::assertSame(42, $granted['granted_account'], 'the socket must be bound to the ticket-granted account');

            $line1 = $this->readProbeLine($probeTest);
            self::assertNotNull($line1, 'the vault must have written its first dispatch record');
            self::assertSame('A', $line1['scope'], 'the FIRST dispatch must run on account A scope');
            self::assertSame('help.getConfig', $line1['method'], 'the FIRST dispatch must be help.getConfig — the method the browser sent');
            self::assertSame(hash('sha256', $vaultKeyA), $line1['vault_key_sha'], 'the FIRST dispatch must seat with account A vault key, not the other account');

            // --- Account switch: bump the generation (RelayActiveAccount::switchTo). ---
            file_put_contents($genFile, '2');

            // --- Second request: updates.getState on the SAME encrypted
            // session (same browser key + session_id, now with the real salt)
            // — Assertion C: re-seats onto account B, key unchanged. (users.
            // getUsers now answers LOCALLY and never dispatches, so this probe
            // rides the still-dispatched updates.getState instead.) ---
            $m2 = $this->clientTimeBase() + 4;
            $body2 = EncryptedConnection::buildFirstQueryBody(227, [
                'api_id' => 1234,
                'device_model' => 'PHPUnit',
                'system_version' => 'Linux',
                'app_version' => '1.0.0',
                'system_lang_code' => 'en',
                'lang_pack' => '',
                'lang_code' => 'en',
                'query' => ['_' => 'updates.getState'],
            ]);
            $this->sendEncryptedEnvelope($client, $clientCipher, $body2, $browserKey, $sessionId, $serverSalt, 3, $m2);

            $secondReply = $this->decryptServerEnvelope($this->readEnvelope($client, $clientCipher), $browserKey, $sessionId);
            $offset = 0;
            $second = TLDecoder::decodeObject($secondReply['payload'], $offset);
            self::assertSame('rpc_result', $second['_'], 'the SECOND reply must also be an rpc_result');
            self::assertSame($m2, $second['req_msg_id'], 'the SECOND reply must echo the SECOND request msg_id');
            self::assertSame(['_' => 'boolTrue'], $second['result'], 'the canned vault result round-trips on the switched scope too');

            $line2 = $this->readProbeLine($probeTest);
            self::assertNotNull($line2, 'the vault must have written its second dispatch record');
            self::assertSame('B', $line2['scope'], 'the SECOND dispatch must run on account B scope — the generation bump must re-seat it');
            self::assertSame('B', $line2['seated'], 'the dispatch re-seated on the ACTIVE account');
            self::assertSame('updates.getState', $line2['method'], 'the SECOND dispatch must be updates.getState — the probe method still dispatched');
            self::assertSame(hash('sha256', $vaultKeyB), $line2['vault_key_sha'], 'the SECOND dispatch must seat with account B vault key');

            // The browser session never changed — both decrypts used the same
            // key + session_id; the second reply proves the key still seats.
            self::assertSame($sessionId, $secondReply['session_id'], 'the browser session_id must be UNCHANGED across the account switch — only the scope moved');
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($probeTest);
            fclose($listen);
            @unlink($genFile);
            $this->reapChild($pid);
        }
    }

    public function test_local_auth_table_manufactures_already_authorized_answers(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked answerer child');
        }

        // The shipped registry carries no 'account.getAccountTtl' constructor, so
        // the request id is registered here under an id-less canonical line (its
        // crc32, the same convention as help.getConfig). Registered BEFORE the
        // fork so the parent (request encode) and the forked answerer (request
        // decode) agree on the id — the browser-ctor overlay below touches only a
        // scratch payload copy, never this registry.
        TLRegistry::register('account.getAccountTtl = AccountDaysTTL');

        [$private, $public] = self::pair();

        // The fake vault "upstream": only updates.getState must ever be
        // dispatched — every auth.* / local-table method has to be answered
        // locally. The probe socket is the only cross-process channel (the fork
        // is copy-on-write), exactly like the earlier tests.
        [$probeRelay, $probeTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $dispatch = static function (string $method, array $params) use ($probeRelay): array {
            fwrite($probeRelay, json_encode(['method' => $method])."\n");

            // A real updates.state (all non-flag fields the registry requires),
            // so the probe round-trip proves a DISPATCHED method answers with a
            // genuine state object (carrying pts), not a swallowed Bool.
            return [
                '_' => 'updates.state',
                'pts' => 777,
                'qts' => 0,
                'date' => 1758000000,
                'seq' => 9,
                'unread_count' => 0,
            ];
        };

        $answerer = new VaultMtprotoAnswerer($dispatch, $private, ticketRequired: false);
        $relay = new WebSocketRelayServer('127.0.0.1', 0, answerer: $answerer);
        $listen = $relay->listen();
        $port = $this->listeningPort($listen);

        $client = null;
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($probeTest);
            $relay->serveOnce($listen);
            self::childExit();
        }

        fclose($probeRelay);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $this->performHandshake($client);

            $header = Obfuscated2CodecTest::clientHeader(5, 'abridged');
            $this->sendBinary($client, $header);
            $clientCipher = Obfuscated2Cipher::fromHeader($header);

            $browserKey = $this->dhHandshake($client, $clientCipher, $public);
            $sessionId = random_int(1, PHP_INT_MAX);
            $base = $this->clientTimeBase();
            $this->inPlain = '';
            $this->inWire = '';

            // 1. users.getUsers(self) -> root Vector<User>: the KEY regression.
            // A root-vector result must NOT tear the session down, and its single
            // item is the fake self user hand-packed under the BROWSER user ctor id.
            $m1 = $base;
            $this->sendEncryptedEnvelope($client, $clientCipher, $this->firstQueryBody([
                '_' => 'users.getUsers',
                'id' => [['_' => 'inputUserSelf']],
            ]), $browserKey, $sessionId, 0, 1, $m1);

            // The first encrypted envelope: new_session_created (salt handoff)
            // precedes the rpc_result of the very first request.
            $nsc = $this->decryptServerEnvelope($this->nextEnvelope($client, $clientCipher), $browserKey, $sessionId);
            $offset = 0;
            $nscDecoded = TLDecoder::decodeObject($nsc['payload'], $offset);
            self::assertSame('new_session_created', $nscDecoded['_'], 'the FIRST server envelope must be new_session_created (salt handoff) before any rpc_result');
            self::assertSame($sessionId, $nsc['session_id'], 'the new_session_created must carry the session id the browser opened');
            $serverSalt = (int) $nscDecoded['server_salt'];

            $payload1 = $this->readRpcResult($client, $clientCipher, $browserKey, $sessionId, $m1);
            $ctorPos = strpos($payload1, pack('V', 0xB1B8CC83));
            self::assertNotFalse($ctorPos, 'users.getUsers must ship the browser user ctor (user#b1b8cc83) — the root vector no longer tears the session down');
            self::assertSame(pack('V', TLRegistry::VECTOR), substr($payload1, $ctorPos - 8, 4), 'the root Vector wrapper precedes the spliced user');
            self::assertSame(pack('V', 1), substr($payload1, $ctorPos - 4, 4), 'the root vector carries exactly one user');
            self::assertSame(pack('P', 1724372757), substr($payload1, $ctorPos + 12, 8), 'the fake self user id (packLong) follows the spliced ctor — flags:# at +4, flags2:# at +8, then id:long at +12');
            $rpc1 = $this->decodeRpcWithUserCtorOverlay($payload1, $m1);
            self::assertIsArray($rpc1['result'], 'the root Vector<User> must decode');
            self::assertCount(1, $rpc1['result'], 'users.getUsers answers a one-element Vector<User>');
            $user = $rpc1['result'][0];
            self::assertIsArray($user, 'users.getUsers: the single Vector<User> item must decode to an array, not a scalar');
            self::assertSame('user', $user['_'], 'users.getUsers: the spliced item must carry the real user ctor, under the BROWSER id');
            self::assertSame(1724372757, $user['id'], 'users.getUsers: the fake self user id must be the one the answerer packed');
            self::assertTrue($user['self'], 'users.getUsers: the fake self user must be flagged self');

            // 2. updates.getState stays DISPATCHED: the probe records it and the
            // result is a real updates.state (carrying pts) — not a local Bool.
            $m2 = $base + 4;
            $rpc2 = $this->rpcRoundTrip($client, $clientCipher, $browserKey, $sessionId, $serverSalt, 3, $m2, ['_' => 'updates.getState']);
            $state = $rpc2['result'];
            self::assertIsArray($state, 'updates.getState: the DISPATCHED method must answer a real state object, not a swallowed Bool');
            self::assertSame('updates.state', $state['_'], 'updates.getState: the dispatched answer must be updates.state');
            self::assertSame(777, $state['pts'], 'updates.getState: the dispatched answer must carry a REAL pts (777), proving a genuine state round-trip');

            // 3. auth.exportLoginToken is AUTHORITATIVE (verbatim request #4): the
            // ticket already granted the account at the WS upgrade, so the
            // browser's first auth poll is conceded up front — loginTokenSuccess
            // + auth.authorization with the fake self user, NOT a QR loginToken
            // handout.
            $m3 = $base + 8;
            $this->sendEncryptedEnvelope($client, $clientCipher, $this->firstQueryBody([
                '_' => 'auth.exportLoginToken',
                'api_id' => 1,
                'api_hash' => '',
                'except_ids' => [],
            ]), $browserKey, $sessionId, $serverSalt, 5, $m3);
            $rpc3 = $this->decodeRpcWithUserCtorOverlay($this->readRpcResult($client, $clientCipher, $browserKey, $sessionId, $m3), $m3);
            $exported = $rpc3['result'];
            self::assertSame('auth.loginTokenSuccess', $exported['_'], 'exportLoginToken must answer authoritative loginTokenSuccess, never a QR token');
            $exportedAuthorization = $exported['authorization'];
            self::assertIsArray($exportedAuthorization, 'auth.exportLoginToken: the loginTokenSuccess must carry an authorization object');
            self::assertSame('auth.authorization', $exportedAuthorization['_'], 'auth.exportLoginToken: the nested object must be auth.authorization, not a bare Bool');
            $exportedUser = $exportedAuthorization['user'];
            self::assertIsArray($exportedUser, 'auth.exportLoginToken: the authorization must carry the fake self user');
            self::assertSame('user', $exportedUser['_'], 'auth.exportLoginToken: the authorization user must carry the real user ctor');
            self::assertSame(1724372757, $exportedUser['id'], 'auth.exportLoginToken: the authorization user id must be the one the answerer packed');
            self::assertTrue($exportedUser['self'], 'auth.exportLoginToken: the authorization user must be flagged self');

            // 4. auth.importLoginToken(token) -> the same authoritative
            // loginTokenSuccess whose nested auth.authorization carries the
            // fake self user (browser ctor, same raw-bytes + overlay proof as
            // 1). The token value itself is ignored — the hook is
            // already-authorized either way.
            $m4 = $base + 12;
            $this->sendEncryptedEnvelope($client, $clientCipher, $this->firstQueryBody([
                '_' => 'auth.importLoginToken',
                'token' => random_bytes(32),
            ]), $browserKey, $sessionId, $serverSalt, 7, $m4);
            $rpc4 = $this->decodeRpcWithUserCtorOverlay($this->readRpcResult($client, $clientCipher, $browserKey, $sessionId, $m4), $m4);
            $success = $rpc4['result'];
            self::assertSame('auth.loginTokenSuccess', $success['_'], 'auth.importLoginToken: the token must be answered AUTHORITATIVE (loginTokenSuccess), never a QR token handout');
            $authorization = $success['authorization'];
            self::assertIsArray($authorization, 'auth.importLoginToken: the loginTokenSuccess must carry an authorization object');
            self::assertSame('auth.authorization', $authorization['_'], 'auth.importLoginToken: the nested object must be auth.authorization, not a bare Bool');
            $authUser = $authorization['user'];
            self::assertIsArray($authUser, 'auth.importLoginToken: the authorization must carry the fake self user');
            self::assertSame('user', $authUser['_'], 'auth.importLoginToken: the authorization user must carry the real user ctor');
            self::assertSame(1724372757, $authUser['id'], 'auth.importLoginToken: the authorization user id must be the one the answerer packed');
            self::assertTrue($authUser['self'], 'auth.importLoginToken: the authorization user must be flagged self');

            // 5. account.registerDevice -> boolTrue (heartbeat/local ack). The
            // query carries every non-flag field of the layer-229 signature.
            $m5 = $base + 16;
            $rpc5 = $this->rpcRoundTrip($client, $clientCipher, $browserKey, $sessionId, $serverSalt, 9, $m5, [
                '_' => 'account.registerDevice',
                'token_type' => 1,
                'token' => 'relay-probe',
                'app_sandbox' => ['_' => 'boolTrue'],
                'secret' => 'secret',
                'other_uids' => [],
            ]);
            self::assertSame(['_' => 'boolTrue'], $rpc5['result'], 'account.registerDevice: the local table must answer boolTrue (the heartbeat/ack form)');

            // 6. account.getAccountTtl -> accountDaysTTL {days: 365}.
            $m6 = $base + 20;
            $rpc6 = $this->rpcRoundTrip($client, $clientCipher, $browserKey, $sessionId, $serverSalt, 11, $m6, ['_' => 'account.getAccountTtl']);
            $ttl = $rpc6['result'];
            self::assertSame('accountDaysTTL', $ttl['_'], 'account.getAccountTtl: the local table must answer accountDaysTTL');
            self::assertSame(365, $ttl['days'], 'account.getAccountTtl: the manufactured TTL must carry days=365, never a default');

            // The dispatch was hit EXACTLY once (updates.getState) — none of the
            // locally-answered methods ever reached the vault.
            $line = $this->readProbeLine($probeTest);
            self::assertNotNull($line, 'updates.getState must have reached the vault dispatch');
            self::assertSame('updates.getState', $line['method'], 'the ONE dispatch the vault saw must be updates.getState — every other method on this page is answered locally');
            self::assertNull($this->readProbeLine($probeTest, 0.25), 'no locally-answered method may reach the dispatch (registered probe count must be exactly 1)');
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($probeTest);
            fclose($listen);
            $this->reapChild($pid);
        }
    }

    // ---- browser-side wire helpers ---------------------------------------

    /**
     * Completes the client half of the MTProto DH exchange over the WS
     * terminal: req_pq_multi -> resPQ -> req_DH_params -> server_DH_params_ok
     * -> set_client_DH_params -> dh_gen_ok, returning the derived 256-byte
     * browser auth key (== the relay's seated key, proven by nonce echo +
     * new_nonce_hash1 + fingerprint match).
     *
     * @param  resource  $socket
     */
    private function dhHandshake($socket, Obfuscated2Cipher $cipher, string $publicPem): string
    {
        $deadline = microtime(true) + self::DEADLINE_S;

        // Step 1: req_pq_multi -> resPQ.
        $nonce = random_bytes(16);
        $respq = $this->plainRoundTrip($socket, $cipher, 'req_pq_multi', ['nonce' => $nonce], $deadline);
        self::assertSame('resPQ', $respq['_']);
        self::assertSame($nonce, $respq['nonce']);
        $serverNonce = (string) $respq['server_nonce'];
        $pq = (string) $respq['pq'];
        self::assertStringContainsString(
            base64_encode(AuthKeyFactory::fingerprintBytesOf($publicPem)),
            base64_encode(TLEncoder::encodeValue('long', $respq['server_public_key_fingerprints'][0])),
            'resPQ must advertise exactly the relay public key the test encrypts with'
        );

        // Step 2: req_DH_params (RSA-pad the p_q_inner_data_dc).
        [$p, $q] = PqFactorizer::factorize($pq);
        $newNonce = random_bytes(32);
        $inner = TLEncoder::encodeObject('p_q_inner_data_dc', [
            'pq' => $pq, 'p' => $p, 'q' => $q,
            'nonce' => $nonce, 'server_nonce' => $serverNonce, 'new_nonce' => $newNonce,
            'dc' => 5,
        ]);
        $serverDh = $this->plainRoundTrip($socket, $cipher, 'req_DH_params', [
            'nonce' => $nonce,
            'server_nonce' => $serverNonce,
            'p' => $p,
            'q' => $q,
            'public_key_fingerprint' => $respq['server_public_key_fingerprints'][0],
            'encrypted_data' => AuthKeyFactory::rsaPadEncrypt($publicPem, $inner),
        ], $deadline);
        self::assertSame('server_DH_params_ok', $serverDh['_']);
        self::assertSame($nonce, $serverDh['nonce']);
        self::assertSame($serverNonce, $serverDh['server_nonce']);

        $innerDh = AuthKeyFactory::decodeHashPrefixed(AesIge::decrypt(
            (string) $serverDh['encrypted_answer'],
            AuthKeyFactory::tmpAesKey($newNonce, $serverNonce),
            AuthKeyFactory::tmpAesIv($newNonce, $serverNonce),
        ));
        self::assertSame('server_DH_inner_data', $innerDh['_']);
        self::assertSame(3, $innerDh['g']);
        self::assertSame(hex2bin(AuthKeyFactory::KNOWN_DH_PRIME_HEX), $innerDh['dh_prime']);
        self::assertSame(256, strlen((string) $innerDh['g_a']));

        // Step 3: set_client_DH_params -> dh_gen_ok; browserKey = g_a^b.
        $g = new BigInteger((string) $innerDh['g']);
        $prime = new BigInteger(bin2hex((string) $innerDh['dh_prime']), 16);
        $gA = new BigInteger(bin2hex((string) $innerDh['g_a']), 16);
        $b = new BigInteger(bin2hex(random_bytes(32)), 16);
        $gB = str_pad($g->modPow($b, $prime)->toBytes(), 256, "\x00", STR_PAD_LEFT);

        $clientInner = TLEncoder::encodeObject('client_DH_inner_data', [
            'nonce' => $nonce, 'server_nonce' => $serverNonce, 'retry_id' => 0, 'g_b' => $gB,
        ]);
        $prefixed = sha1($clientInner, true).$clientInner;
        $prefixed .= str_repeat("\x00", (16 - (strlen($prefixed) % 16)) % 16);

        $dhGen = $this->plainRoundTrip($socket, $cipher, 'set_client_DH_params', [
            'nonce' => $nonce,
            'server_nonce' => $serverNonce,
            'encrypted_data' => AesIge::encrypt(
                $prefixed,
                AuthKeyFactory::tmpAesKey($newNonce, $serverNonce),
                AuthKeyFactory::tmpAesIv($newNonce, $serverNonce),
            ),
        ], $deadline);
        self::assertSame('dh_gen_ok', $dhGen['_']);
        self::assertSame($nonce, $dhGen['nonce']);
        self::assertSame($serverNonce, $dhGen['server_nonce']);

        $browserKey = str_pad($gA->modPow($b, $prime)->toBytes(), 256, "\x00", STR_PAD_LEFT);
        self::assertSame(256, strlen($browserKey));
        self::assertSame(
            AuthKeyFactory::newNonceHash1($newNonce, $browserKey),
            $dhGen['new_nonce_hash1'],
            'the server seated the SAME auth key the browser derived'
        );

        return $browserKey;
    }

    /**
     * One plain (unencrypted envelope) DH round trip over the WS terminal.
     *
     * @param  resource  $socket
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function plainRoundTrip($socket, Obfuscated2Cipher $cipher, string $method, array $params, float $deadline): array
    {
        $body = TLEncoder::encodeObject($method, $params);
        $envelope = PlainConnection::buildPlainEnvelope($body, PacketCodec::generateMessageId());
        $this->sendBinary($socket, $cipher->xcrypt(FrameCodec::wrapAbridgedPayload($envelope), true));

        $reply = $this->readEnvelope($socket, $cipher, $deadline);
        $plain = PlainConnection::parsePlainEnvelope($reply);
        $offset = 0;

        return TLDecoder::decodeObject($plain, $offset);
    }

    /**
     * Sends one encrypted MTProto envelope (abridged-framed, obfuscated2,
     * masked WS binary).
     *
     * @param  resource  $socket
     */
    private function sendEncryptedEnvelope($socket, Obfuscated2Cipher $cipher, string $body, string $key, int $sessionId, int $salt, int $seqNo, int $msgId): void
    {
        $packet = PacketCodec::encryptPacket(
            payload: $body,
            authKey: $key,
            sessionId: $sessionId,
            serverSalt: $salt,
            seqNo: $seqNo,
            toServer: true,
            messageId: $msgId,
        );
        self::assertSame(0, strlen($packet) % 4);
        $this->sendBinary($socket, $cipher->xcrypt(FrameCodec::wrapAbridgedPayload($packet), true));
    }

    /**
     * Reads one server->client envelope (abridged-framed inside one or more
     * unmasked WS binary messages), accumulating across chunk boundaries.
     *
     * @param  resource  $socket
     */
    private function readEnvelope($socket, Obfuscated2Cipher $cipher, ?float $deadline = null): string
    {
        $deadline ??= microtime(true) + self::DEADLINE_S;
        // A blocking socket makes fread() wait forever and the deadline guard
        // below unreachable (the green WebSocketRelayServerTest sets the same
        // non-blocking mode on its read helpers). Non-blocking + poll keeps
        // every read bounded.
        @stream_set_blocking($socket, false);
        $wire = '';
        $plain = '';
        while (true) {
            $envelope = $this->peelAbridged($plain);
            if ($envelope !== null) {
                return $envelope;
            }

            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                if (microtime(true) >= $deadline) {
                    throw new RuntimeException('VaultMtprotoAnswererTest: WS frame timed out');
                }
                usleep(self::POLL_INTERVAL_US);

                continue;
            }
            $wire .= $chunk;
            while (true) {
                $frame = WebSocketFrameCodec::parseFrame($wire);
                if ($frame === null) {
                    break;
                }
                self::assertFalse($frame['masked'], 'server frames must NOT be masked');
                self::assertSame(WebSocketFrameCodec::OPCODE_BINARY, $frame['opcode']);
                $wire = substr($wire, self::frameLength($frame));
                $plain .= $cipher->xcrypt($frame['payload'], false);
            }
        }
    }

    /**
     * Decrypts one server envelope with the browser key and asserts the
     * expected session id.
     *
     * @return array<string, mixed> PacketCodec::decryptPacket result
     */
    private function decryptServerEnvelope(string $envelope, string $browserKey, int $sessionId): array
    {
        return PacketCodec::decryptPacket($envelope, $browserKey, fromServer: true, expectedSessionId: $sessionId);
    }

    /**
     * Wraps one decoded RPC query in the invokeWithLayer(227)/initConnection
     * frame the browser sends, with the standard probe metadata (mirrors the
     * inline build in the earlier tests).
     *
     * @param  array<string, mixed>  $query
     */
    private function firstQueryBody(array $query): string
    {
        return EncryptedConnection::buildFirstQueryBody(227, [
            'api_id' => 1234,
            'device_model' => 'PHPUnit',
            'system_version' => 'Linux',
            'app_version' => '1.0.0',
            'system_lang_code' => 'en',
            'lang_pack' => '',
            'lang_code' => 'en',
            'query' => $query,
        ]);
    }

    /**
     * Reads the next server->client envelope over the STATEFUL abridged/WS
     * buffers ($this->inWire / $this->inPlain), so frames that share one WS
     * message or TCP segment survive the read of the first (a second rpc_result
     * coalesced behind a new_session_created would otherwise be consumed and
     * dropped by the one-shot readEnvelope). Deadline-guarded exactly like
     * readEnvelope.
     *
     * @param  resource  $socket
     */
    private function nextEnvelope($socket, Obfuscated2Cipher $cipher, ?float $deadline = null): string
    {
        $deadline ??= microtime(true) + self::DEADLINE_S;
        @stream_set_blocking($socket, false);
        while (true) {
            $envelope = $this->peelAbridged($this->inPlain);
            if ($envelope !== null) {
                return $envelope;
            }

            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                if (microtime(true) >= $deadline) {
                    throw new RuntimeException('VaultMtprotoAnswererTest: WS frame timed out');
                }
                usleep(self::POLL_INTERVAL_US);

                continue;
            }
            $this->inWire .= $chunk;
            while (true) {
                $frame = WebSocketFrameCodec::parseFrame($this->inWire);
                if ($frame === null) {
                    break;
                }
                self::assertFalse($frame['masked'], 'server frames must NOT be masked');
                self::assertSame(WebSocketFrameCodec::OPCODE_BINARY, $frame['opcode']);
                $this->inWire = substr($this->inWire, self::frameLength($frame));
                $this->inPlain .= $cipher->xcrypt($frame['payload'], false);
            }
        }
    }

    /**
     * Reads the rpc_result envelope answering $msgId and returns its RAW
     * decrypted payload (rpc_result ctor at offset 0). The answerer also
     * writes a service msgs_ack per request, so any non-rpc_result envelope
     * (the stale ack left behind by the previous read, or frames coalesced
     * into the stateful buffers) is skipped. Only the header is peeled here —
     * the result Object may carry the browser's user ctor id (user#b1b8cc83)
     * the stock registry cannot decode, so the full decode is deferred to
     * decodeRpcWithUserCtorOverlay. The frame MUST arrive: a missing
     * rpc_result (an answerer.closed tear-down) fails the assert, not a hang.
     *
     * @param  resource  $socket
     */
    private function readRpcResult($socket, Obfuscated2Cipher $cipher, string $browserKey, int $sessionId, int $msgId): string
    {
        while (true) {
            $envelope = $this->decryptServerEnvelope($this->nextEnvelope($socket, $cipher), $browserKey, $sessionId);
            $payload = $envelope['payload'];
            $offset = 0;
            if (TLSerializer::unpackInt($payload, $offset) !== TLRegistry::id('rpc_result')) {
                continue; // service message (msgs_ack / nsc) — not the answer
            }
            self::assertSame($msgId, TLSerializer::unpackLong($payload, $offset), 'the rpc_result must echo the request msg_id');

            return $payload;
        }
    }

    /**
     * Decodes an rpc_result payload whose result carries the BROWSER's user
     * ctor id (user#b1b8cc83) the stock registry does not know. Registers a
     * parent-process-only overlay line mapping that id to the same canonical
     * user fields the wire carried (the fork already happened, so the answerer
     * child keeps user#31774388 and the splice is proven genuinely on the
     * wire, not masked), then decodes with the shared TLDecoder.
     *
     * @param  array<string, mixed>  $payload  decrypted rpc_result bytes
     * @return array<string, mixed> $rpc decoded rpc_result
     */
    private function decodeRpcWithUserCtorOverlay(string $payload, int $msgId): array
    {
        if (TLRegistry::nameOf(0xB1B8CC83) === null) {
            TLRegistry::register('user#b1b8cc83'.substr(TLRegistry::signature('user'), strlen('user#31774388')));
        }

        $offset = 0;
        $rpc = TLDecoder::decodeObject($payload, $offset);
        self::assertSame('rpc_result', $rpc['_']);
        self::assertSame($msgId, $rpc['req_msg_id']);

        return $rpc;
    }

    /**
     * One full encrypted RPC round trip: wraps $query, sends it on the session
     * and decodes the answering rpc_result with the registry.
     *
     * @param  resource  $socket
     * @param  array<string, mixed>  $query
     * @return array<string, mixed> decoded rpc_result
     */
    private function rpcRoundTrip($socket, Obfuscated2Cipher $cipher, string $browserKey, int $sessionId, int $salt, int $seqNo, int $msgId, array $query): array
    {
        $this->sendEncryptedEnvelope($socket, $cipher, $this->firstQueryBody($query), $browserKey, $sessionId, $salt, $seqNo, $msgId);
        $offset = 0;

        return TLDecoder::decodeObject($this->readRpcResult($socket, $cipher, $browserKey, $sessionId, $msgId), $offset);
    }

    /**
     * Strips one abridged frame (varint len/4 prefix + payload) from $buffer.
     *
     * @param  string  $buffer  in/out
     */
    private function peelAbridged(string &$buffer): ?string
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
        if ($len < 1 || $len > 2 * 1024 * 1024) {
            throw new RuntimeException(sprintf('VaultMtprotoAnswererTest: invalid abridged frame length %d', $len));
        }
        if (strlen($buffer) < $header + $len) {
            return null;
        }
        $payload = substr($buffer, $header, $len);
        $buffer = substr($buffer, $header + $len);

        return $payload;
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

    /** @param resource $server */
    private function listeningPort($server): int
    {
        $addr = stream_socket_get_name($server, false);
        if ($addr === false) {
            self::fail('could not read the listen address');
        }
        [, $port] = explode(':', $addr);

        return (int) $port;
    }

    /**
     * Performs the RFC6455 client side of the handshake against the relay.
     *
     * @param  resource  $socket
     */
    private function performHandshake($socket): void
    {
        $key = base64_encode(random_bytes(16));
        $this->writeAll($socket, "GET /apiws HTTP/1.1\r\n"
            ."Host: 127.0.0.1\r\n"
            ."Authorization: Bearer test-ticket\r\n"
            ."Upgrade: websocket\r\n"
            ."Connection: Upgrade\r\n"
            ."Sec-WebSocket-Key: {$key}\r\n"
            ."Sec-WebSocket-Protocol: binary\r\n"
            ."Sec-WebSocket-Version: 13\r\n"
            ."\r\n");

        @stream_set_blocking($socket, false);
        $response = '';
        $deadline = microtime(true) + 2.0;
        while (! str_contains($response, "\r\n\r\n")) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('VaultMtprotoAnswererTest: handshake response timed out');
            }
            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                usleep(self::POLL_INTERVAL_US);

                continue;
            }
            $response .= $chunk;
        }

        self::assertStringStartsWith('HTTP/1.1 101', $response, 'relay must answer 101 Switching Protocols');
        self::assertStringContainsString(
            WebSocketFrameCodec::acceptKey($key),
            $response,
            'relay must echo the computed Sec-WebSocket-Accept',
        );
        self::assertStringContainsString(
            'Sec-WebSocket-Protocol: binary',
            $response,
            'relay must echo the negotiated binary subprotocol',
        );
    }

    /**
     * Sends $bytes as one masked WS binary message (RFC6455 client framing).
     *
     * @param  resource  $socket
     */
    private function sendBinary($socket, string $bytes): void
    {
        $this->writeAll($socket, WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $bytes, mask: true));
    }

    /**
     * Reads one newline-terminated dispatch record from the fake vault probe.
     *
     * @param  resource  $socket
     * @return array<string, mixed>|null decoded record, null on timeout
     */
    private function readProbeLine($socket, float $timeout = self::DEADLINE_S): ?array
    {
        @stream_set_blocking($socket, false);
        $deadline = microtime(true) + $timeout;
        while (microtime(true) < $deadline) {
            if (str_contains($this->probeBuffer, "\n")) {
                [$line, $this->probeBuffer] = explode("\n", $this->probeBuffer, 2);

                return json_decode($line, true);
            }
            $chunk = @fread($socket, 4096);
            if ($chunk !== false && $chunk !== '') {
                $this->probeBuffer .= $chunk;

                continue;
            }
            usleep(self::POLL_INTERVAL_US);
        }

        return null;
    }

    /** @param resource $socket */
    private function writeAll($socket, string $bytes): void
    {
        $total = strlen($bytes);
        $written = 0;
        while ($written < $total) {
            $n = @fwrite($socket, substr($bytes, $written));
            if ($n === false || $n === 0) {
                throw new RuntimeException('VaultMtprotoAnswererTest: write failed at offset '.$written);
            }
            $written += $n;
        }
    }

    /**
     * Reaps the answerer child, hard-killing it on timeout so CI never hangs.
     */
    private function reapChild(int $pid): void
    {
        $deadline = microtime(true) + 1.0;
        while (microtime(true) < $deadline) {
            $status = null;
            $reaped = pcntl_waitpid($pid, $status, WNOHANG);
            if ($reaped === $pid) {
                self::assertSame(0, pcntl_wexitstatus($status) ?? -1, 'answerer child must exit cleanly');

                return;
            }
            if ($reaped === -1) {
                return;
            }
            usleep(self::POLL_INTERVAL_US * 5);
        }

        @posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);

        self::fail('answerer child did not exit within the deadline');
    }

    /**
     * Terminates the forked child WITHOUT PHP's shutdown sequence (a forked
     * PHPUnit child would flush buffers and re-run teardown otherwise).
     */
    private static function childExit(): never
    {
        if (extension_loaded('FFI')) {
            try {
                $libc = \FFI::cdef('void _exit(int status);', 'libc.so.6');
                $libc->_exit(0);
            } catch (\Throwable) {
                // FFI disabled or this is not a glibc host — exit normally.
            }
        }

        exit(0);
    }

    private function clientTimeBase(): int
    {
        return ((int) (microtime(true) * 2 ** 32)) & ~3;
    }
}
