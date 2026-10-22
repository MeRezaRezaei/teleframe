<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\MTProto\Crypto;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\PlainConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\AesIge;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\AuthKeyFactory;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PqFactorizer;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\ServerDhHandshake;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use phpseclib3\Crypt\RSA;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Server half of the MTProto auth-key exchange:
 *   - transcript replay: req_pq_multi -> req_DH_params -> set_client_DH_params
 *     through the real client (AuthKeyFactory::generate) and through an
 *     independent re-derivation, asserting dh_gen_ok and the seated auth key;
 *   - state machine: out-of-order requests are rejected;
 *   - the RSA keypair is synthesized in-memory with phpseclib — no fixtures.
 */
final class ServerDhHandshakeTest extends TestCase
{
    /** @var array{0: string, 1: string}|null [private PKCS1, public PKCS1] */
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

    public function test_initial_state_and_null_accessors(): void
    {
        $server = new ServerDhHandshake(self::pair()[0]);

        $this->assertSame('awaiting_dh_params', $server->state());
        $this->assertNull($server->authKey());
        $this->assertNull($server->browserNonce());
    }

    /**
     * Transcript replay, server roles: the real client implementation
     * (AuthKeyFactory::generate) drives the full exchange across the wire and
     * only returns once resPQ -> server_DH_params_ok -> dh_gen_ok all
     * validated (nonce/server_nonce echo, g_a range, known dh_prime,
     * new_nonce_hash1). Both sides must derive the identical 256-byte key.
     */
    public function test_full_exchange_against_real_client_derives_the_same_auth_key(): void
    {
        [$private] = self::pair();
        $server = new ServerDhHandshake($private);

        $session = $this->generateViaServer($server);

        $this->assertSame(256, strlen($session->authKey));
        $this->assertSame('seated', $server->state());
        $this->assertSame(256, strlen((string) $server->authKey()));
        $this->assertSame($session->authKey, $server->authKey());
        $this->assertSame(16, strlen((string) $server->browserNonce()));
        $this->assertSame(2, $session->dcId);
    }

    /**
     * Transcript replay, step-by-step: drive each handle() call manually,
     * assert the four response constructors field by field, then re-derive
     * the DH shared secret independently and require it to equal the server's
     * seated authKey() — no AuthKeyFactory::generate in the loop.
     */
    public function test_transcript_each_response_constructor_and_independent_key_derivation(): void
    {
        [$private, $public] = self::pair();
        $server = new ServerDhHandshake($private);
        $nonce = str_repeat("\x5a", 16);

        // --- Step 1: req_pq_multi -> resPQ
        $resPq = (array) $server->handle((object) ['_' => 'req_pq_multi', 'nonce' => $nonce]);
        $this->assertSame('resPQ', $resPq['_'], 'step 1 resPQ: the answer to req_pq_multi must be resPQ');
        $this->assertSame($nonce, $resPq['nonce'], 'step 1 resPQ: the resPQ must echo the request nonce');
        $this->assertSame(16, strlen((string) $resPq['server_nonce']), 'step 1 resPQ: the server nonce must be a fresh 16-byte random');
        $serverNonce = (string) $resPq['server_nonce'];
        $pq = (string) $resPq['pq'];
        $this->assertCount(1, $resPq['server_public_key_fingerprints'], 'step 1 resPQ: exactly ONE server public-key fingerprint is advertised');
        $this->assertSame('awaiting_client_dh', $server->state(), 'step 1 resPQ: having answered resPQ the machine must be awaiting req_DH_params');
        $this->assertSame($nonce, $server->browserNonce(), 'step 1 resPQ: the browser nonce must be the one req_pq_multi carried');

        // --- Step 2: req_DH_params -> server_DH_params_ok
        [$p, $q] = PqFactorizer::factorize($pq);
        $newNonce = str_repeat("\x3c", 32);
        $inner = TLEncoder::encodeObject('p_q_inner_data_dc', [
            'pq' => $pq, 'p' => $p, 'q' => $q,
            'nonce' => $nonce, 'server_nonce' => $serverNonce, 'new_nonce' => $newNonce,
            'dc' => 2,
        ]);
        $serverDh = (array) $server->handle((object) [
            '_' => 'req_DH_params',
            'nonce' => $nonce,
            'server_nonce' => $serverNonce,
            'p' => $p,
            'q' => $q,
            'public_key_fingerprint' => $resPq['server_public_key_fingerprints'][0],
            'encrypted_data' => AuthKeyFactory::rsaPadEncrypt($public, $inner),
        ]);
        $this->assertSame('server_DH_params_ok', $serverDh['_'], 'step 2 server_DH_params_ok: the answer to req_DH_params must be server_DH_params_ok');
        $this->assertSame($nonce, $serverDh['nonce'], 'step 2 server_DH_params_ok: the answer must echo the request nonce');
        $this->assertSame($serverNonce, $serverDh['server_nonce'], 'step 2 server_DH_params_ok: the answer must echo the server nonce resPQ issued');

        $plain = AesIge::decrypt(
            (string) $serverDh['encrypted_answer'],
            AuthKeyFactory::tmpAesKey($newNonce, $serverNonce),
            AuthKeyFactory::tmpAesIv($newNonce, $serverNonce)
        );
        $innerDh = AuthKeyFactory::decodeHashPrefixed($plain);
        $this->assertSame('server_DH_inner_data', $innerDh['_'], 'step 2 server_DH_inner_data: the RSA-padded answer must decrypt to server_DH_inner_data');
        $this->assertSame($nonce, $innerDh['nonce'], 'step 2 server_DH_inner_data: the decrypted inner data must carry the request nonce');
        $this->assertSame($serverNonce, $innerDh['server_nonce'], 'step 2 server_DH_inner_data: the decrypted inner data must carry the server nonce');
        $this->assertSame(3, $innerDh['g'], 'step 2 server_DH_inner_data: g must be 3 — the MTProto DH generator, never a round number');
        $this->assertSame(hex2bin(AuthKeyFactory::KNOWN_DH_PRIME_HEX), $innerDh['dh_prime'], 'step 2 server_DH_inner_data: dh_prime must be the OFFICIAL 2048-bit MTProto prime, not any other');
        $this->assertSame(256, strlen((string) $innerDh['g_a']), 'step 2 server_DH_inner_data: g_a must be a full 256-byte auth key');
        $this->assertIsInt($innerDh['server_time'], 'step 2 server_DH_inner_data: server_time must be an int — an uninitialised read is null, not 0');

        $g = new BigInteger((string) $innerDh['g']);
        $prime = new BigInteger(bin2hex((string) $innerDh['dh_prime']), 16);
        $gA = new BigInteger(bin2hex((string) $innerDh['g_a']), 16);
        $b = new BigInteger(str_repeat("\x11", 32), 256);
        $gB = str_pad($g->modPow($b, $prime)->toBytes(), 256, "\x00", STR_PAD_LEFT);

        // --- Step 3: set_client_DH_params -> dh_gen_ok
        $clientInner = TLEncoder::encodeObject('client_DH_inner_data', [
            'nonce' => $nonce, 'server_nonce' => $serverNonce, 'retry_id' => 0, 'g_b' => $gB,
        ]);
        $prefixed = sha1($clientInner, true).$clientInner;
        $padLen = (16 - (strlen($prefixed) % 16)) % 16;
        $prefixed .= str_repeat("\x00", $padLen);

        $dhGen = (array) $server->handle((object) [
            '_' => 'set_client_DH_params',
            'nonce' => $nonce,
            'server_nonce' => $serverNonce,
            'encrypted_data' => AesIge::encrypt(
                $prefixed,
                AuthKeyFactory::tmpAesKey($newNonce, $serverNonce),
                AuthKeyFactory::tmpAesIv($newNonce, $serverNonce)
            ),
        ]);
        $this->assertSame('dh_gen_ok', $dhGen['_'], 'step 3 dh_gen_ok: the answer to set_client_DH_params must be dh_gen_ok');
        $this->assertSame($nonce, $dhGen['nonce'], 'step 3 dh_gen_ok: the answer must echo the request nonce');
        $this->assertSame($serverNonce, $dhGen['server_nonce'], 'step 3 dh_gen_ok: the answer must echo the server nonce');

        // Independent re-derivation: client key = g_a^b, server key = g_b^a.
        $clientKey = str_pad($gA->modPow($b, $prime)->toBytes(), 256, "\x00", STR_PAD_LEFT);
        $serverKey = (string) $server->authKey();
        $this->assertSame(256, strlen($serverKey), 'derivation: the SEATED auth key must be a full 256 bytes');
        $this->assertSame($clientKey, $serverKey, 'derivation: the INDEPENDENT client re-derivation (g_a^b) must equal the key the server seated (g_b^a)');
        $this->assertSame('seated', $server->state(), 'derivation: having seated a key the machine must report itself seated');
        $this->assertSame($nonce, $server->browserNonce(), 'derivation: seating the key must not disturb the browser nonce req_pq_multi carried');
        $this->assertSame(AuthKeyFactory::newNonceHash1($newNonce, $serverKey), $dhGen['new_nonce_hash1'], 'derivation: new_nonce_hash1 must be computed over the SEATED key — it is what proves both sides derived the same one');
    }

    public function test_rejects_out_of_order_requests(): void
    {
        $server = new ServerDhHandshake(self::pair()[0]);

        // The machine must reject everything before req_pq_multi.
        $this->assertRejected(fn () => $server->handle((object) [
            '_' => 'req_DH_params',
            'nonce' => str_repeat("\x01", 16),
            'server_nonce' => str_repeat("\x02", 16),
            'p' => "\x01",
            'q' => "\x02",
            'public_key_fingerprint' => 0,
            'encrypted_data' => str_repeat("\x00", 256),
        ]), 'req_DH_params out of order');

        $this->assertRejected(fn () => $server->handle((object) [
            '_' => 'set_client_DH_params',
            'nonce' => str_repeat("\x01", 16),
            'server_nonce' => str_repeat("\x02", 16),
            'encrypted_data' => str_repeat("\x00", 32),
        ]), 'set_client_DH_params out of order');

        $this->assertRejected(fn () => $server->handle((object) ['_' => 'no_such_method']), 'unexpected request constructor');

        // A second req_pq_multi after resPQ is a replay, not a restart.
        $server->handle((object) ['_' => 'req_pq_multi', 'nonce' => str_repeat("\x07", 16)]);
        $this->assertRejected(fn () => $server->handle((object) ['_' => 'req_pq_multi', 'nonce' => str_repeat("\x08", 16)]), 'req_pq_multi out of order');

        // Once seated, no further handshake step may run.
        $seated = new ServerDhHandshake(self::pair()[0]);
        $this->generateViaServer($seated);
        $this->assertSame('seated', $seated->state());
        $this->assertRejected(fn () => $seated->handle((object) ['_' => 'req_pq_multi', 'nonce' => str_repeat("\x09", 16)]), 'req_pq_multi out of order');
        $this->assertRejected(fn () => $seated->handle((object) ['_' => 'req_DH_params', 'nonce' => str_repeat("\x09", 16)]), 'req_DH_params out of order');
    }

    public function test_rejects_mismatched_nonces(): void
    {
        $server = new ServerDhHandshake(self::pair()[0]);
        $server->handle((object) ['_' => 'req_pq_multi', 'nonce' => str_repeat("\x07", 16)]);

        $this->assertRejected(fn () => $server->handle((object) [
            '_' => 'req_DH_params',
            'nonce' => str_repeat("\x99", 16),
            'server_nonce' => str_repeat("\x02", 16),
            'p' => "\x01",
            'q' => "\x02",
            'public_key_fingerprint' => 0,
            'encrypted_data' => str_repeat("\x00", 256),
        ]), 'nonce mismatch');

        $this->assertRejected(fn () => $server->handle((object) [
            '_' => 'req_DH_params',
            'nonce' => str_repeat("\x07", 16),
            'server_nonce' => str_repeat("\x99", 16),
            'p' => "\x01",
            'q' => "\x02",
            'public_key_fingerprint' => 0,
            'encrypted_data' => str_repeat("\x00", 256),
        ]), 'server_nonce mismatch');
    }

    public function test_every_handshake_issues_a_fresh_random_semiprime_pq(): void
    {
        [$private] = self::pair();
        $nonce = str_repeat("\x5a", 16);

        $resA = (array) (new ServerDhHandshake($private))->handle((object) ['_' => 'req_pq_multi', 'nonce' => $nonce]);
        $resB = (array) (new ServerDhHandshake($private))->handle((object) ['_' => 'req_pq_multi', 'nonce' => $nonce]);

        $pqA = (string) $resA['pq'];
        $pqB = (string) $resB['pq'];

        $this->assertNotSame($pqA, $pqB, 'pq must be fresh per handshake, not the constant 101*65537');
        $this->assertNotSame(
            ["\x65", "\x01\x00\x01"], // [101, 65537] big-endian, smaller first
            PqFactorizer::factorize($pqA),
            'the factor pair must not be the hardcoded 101/65537'
        );

        foreach ([$pqA, $pqB] as $pq) {
            $bits = (new BigInteger(bin2hex($pq), 16))->getLength();
            $this->assertGreaterThanOrEqual(62, $bits, 'pq must be a ~64-bit semiprime, not a 21-bit constant');
            $this->assertLessThanOrEqual(64, $bits, 'pq stays within the fattable 64-bit window');

            [$p, $q] = PqFactorizer::factorize($pq);
            $this->assertTrue(
                (new BigInteger(bin2hex($pq), 16))->equals(
                    (new BigInteger(bin2hex($p), 16))->multiply(new BigInteger(bin2hex($q), 16))
                ),
                'client-factorable p*q must refactor into the issued pq'
            );
        }
    }

    /** Drives AuthKeyFactory::generate() through the full exchange against the server under test. */
    private function generateViaServer(ServerDhHandshake $server): SessionData
    {
        $factoryClass = get_class(new class extends AuthKeyFactory
        {
            public static ?string $pem = null;

            /** @return list<string> */
            protected static function bundledPublicKeys(): array
            {
                if (self::$pem === null) {
                    throw new RuntimeException('test factory: pem not configured');
                }

                return [self::$pem];
            }
        });
        $factoryClass::$pem = self::pair()[1];

        $conn = new class(fopen('php://memory', 'r')) extends PlainConnection
        {
            public static ?ServerDhHandshake $server = null;

            public function request(string $payload): string
            {
                $server = self::$server;
                if ($server === null) {
                    throw new RuntimeException('fake connection: server not configured');
                }
                $offset = 0;
                $request = TLDecoder::decodeObject($payload, $offset);
                $response = (array) $server->handle((object) $request);

                return TLEncoder::encodeObject((string) ($response['_'] ?? ''), $response);
            }
        };
        $conn::$server = $server;

        return $factoryClass::generate($conn, 2);
    }

    private function assertRejected(callable $op, string $needle): void
    {
        try {
            $op();
            $this->fail('expected a ServerDhHandshake RuntimeException containing "'.$needle.'"');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($needle, $e->getMessage());
        }
    }
}
