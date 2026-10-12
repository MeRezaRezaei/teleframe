<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\MTProto\Crypto;

use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\ServerDhHandshake;
use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Relay RSA key-size floor (finding W-08): the terminal seats browser
 * clients on a relay-local RSA modulus, and a modulus under 2048 bits is
 * trivially factorable. The server must refuse such a key at LOAD time
 * (the first handshake step), never seat a terminal on it.
 *
 * A 512-bit PKCS#1 key plays the weak CA-style roundtrip; the 2048-bit
 * control plays the shipped default. Pure in-process — no network, no
 * credentials, no fork.
 */
final class RelayRsaKeyW08Test extends TestCase
{
    // vendor/ is a symlink to the main tree, so vendor/autoload.php resolves
    // ServerDhHandshake to the main tree's unfixed src/. Pin this tree's
    // sources before any test body autoloads the class.
    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 4).'/tests/Schema/FixesAutoload.php';
    }

    public function test_512_bit_relay_key_is_rejected_at_load(): void
    {
        $weak = RSA::createKey(512)->toString('PKCS1');
        $server = new ServerDhHandshake($weak);

        try {
            $server->handle((object) ['_' => 'req_pq_multi', 'nonce' => str_repeat("\x5a", 16)]);
            self::fail('a 512-bit relay RSA key must never seat a handshake');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('at least '.ServerDhHandshake::MIN_RELAY_RSA_BITS.' bits', $e->getMessage());
        }

        self::assertSame(ServerDhHandshake::MIN_RELAY_RSA_BITS, 2048, 'the documented floor is 2048 bits');
    }

    public function test_2048_bit_relay_key_passes_the_floor(): void
    {
        $strong = RSA::createKey(2048)->toString('PKCS1');
        $server = new ServerDhHandshake($strong);

        $response = (array) $server->handle((object) ['_' => 'req_pq_multi', 'nonce' => str_repeat("\x5a", 16)]);

        self::assertSame('resPQ', $response['_'], 'a 2048-bit relay key must drive the first handshake step');
        self::assertSame('awaiting_client_dh', $server->state(), 'resPQ must advance the handshake to the DH-params step');
    }

    public function test_minimum_bits_constant_is_exposed(): void
    {
        self::assertTrue(ServerDhHandshake::MIN_RELAY_RSA_BITS >= 2048, 'the floor may never drop below 2048');
    }
}
