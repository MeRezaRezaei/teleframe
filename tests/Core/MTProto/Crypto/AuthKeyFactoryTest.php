<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\MTProto\Crypto;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\PlainConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\AuthKeyFactory;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Tests\Wire\FakeHandshakeServer;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * PSR-3 logging seam through AuthKeyFactory::generate(): optional logger,
 * silent by default, metadata-only context (lengths / fingerprints / step
 * markers — never nonce or auth-key material), logging never swallows.
 */
class AuthKeyFactoryTest extends TestCase
{
    public function test_generate_logs_full_key_generation_lifecycle(): void
    {
        $logger = new ArrayLogger;
        $session = $this->generateViaFakeServer(new FakeHandshakeServer, $logger);

        $this->assertSame(256, strlen($session->authKey), 'the generated session must carry a full 256-byte auth key');

        $messages = array_column($logger->records, 'message');
        $this->assertContains('teleframe.mtproto.authkey.generate.start', $messages, 'the lifecycle must open with generate.start');
        $this->assertContains('teleframe.mtproto.authkey.dh.params.received', $messages, 'the DH step must log when server params arrive');
        $this->assertContains('teleframe.mtproto.authkey.derived', $messages, 'the derivation step must be logged');
        $this->assertContains('teleframe.mtproto.authkey.generate.done', $messages, 'a successful generation must log generate.done');
        $this->assertNotContains('teleframe.mtproto.authkey.generate.failed', $messages, 'a successful generation must NOT log generate.failed');
        $this->assertNotContains('teleframe.mtproto.authkey.dh.params.invalid', $messages, 'a valid DH exchange must NOT log dh.params.invalid');

        $this->assertLifecycleEvent($logger, 'teleframe.mtproto.authkey.generate.start', ['dc' => 2]);
        $this->assertLifecycleEvent($logger, 'teleframe.mtproto.authkey.dh.params.received', ['dc' => 2], notSame: ['key_fingerprint' => 0]);
        $this->assertLifecycleEvent($logger, 'teleframe.mtproto.authkey.derived', ['key_length' => 256]);
        $this->assertLifecycleEvent($logger, 'teleframe.mtproto.authkey.generate.done', ['key_length' => 256]);

        // never log credential material: every context is length/hash/step metadata
        $secretKeys = ['nonce', 'new_nonce', 'server_nonce', 'auth_key', 'authKey', 'g_a', 'g_b', 'secret'];
        foreach ($logger->records as $record) {
            foreach ($secretKeys as $secret) {
                $this->assertArrayNotHasKey($secret, $record['context'], 'auth-key events must not carry credential material');
            }
        }
    }

    public function test_generate_defaults_to_silent_null_logger(): void
    {
        // no logger means no observable side effect and a complete handshake
        $session = $this->generateViaFakeServer(new FakeHandshakeServer);
        $this->assertSame(256, strlen($session->authKey));
        $this->assertSame(2, $session->dcId);
    }

    public function test_generate_logs_dh_params_invalid_and_rethrows(): void
    {
        $server = new FakeHandshakeServer;
        $server->tamperDhPrime = true;
        $logger = new ArrayLogger;

        try {
            $this->generateViaFakeServer($server, $logger);
            $this->fail('foreign dh_prime must throw');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('dh_prime', $e->getMessage());
        }

        $invalid = $this->recordsOf($logger, 'teleframe.mtproto.authkey.dh.params.invalid');
        $this->assertCount(1, $invalid);
        $this->assertSame('error', $invalid[0]['level']);
        $this->assertSame(2, $invalid[0]['context']['dc']);
        $this->assertSame('dh_params', $invalid[0]['context']['step']);
        $this->assertSame(RuntimeException::class, $invalid[0]['context']['exception']);

        $this->assertCount(
            0,
            $this->recordsOf($logger, 'teleframe.mtproto.authkey.generate.failed'),
            'a DH-parameter violation must surface as dh.params.invalid, not generate.failed'
        );
    }

    public function test_generate_logs_failed_and_rethrows(): void
    {
        $server = new FakeHandshakeServer;
        $server->tamperGenHash1 = true; // valid DH params, wrong derived-key verification
        $logger = new ArrayLogger;

        try {
            $this->generateViaFakeServer($server, $logger);
            $this->fail('mismatched new_nonce_hash1 must throw');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('new_nonce_hash1', $e->getMessage());
        }

        $failed = $this->recordsOf($logger, 'teleframe.mtproto.authkey.generate.failed');
        $this->assertCount(1, $failed);
        $this->assertSame('error', $failed[0]['level']);
        $this->assertSame(2, $failed[0]['context']['dc']);
        $this->assertSame('dh_verify', $failed[0]['context']['step']);
        $this->assertSame(RuntimeException::class, $failed[0]['context']['exception']);

        $this->assertCount(
            0,
            $this->recordsOf($logger, 'teleframe.mtproto.authkey.dh.params.invalid'),
            'a generation failure must surface as generate.failed, not dh.params.invalid'
        );
    }

    private function generateViaFakeServer(FakeHandshakeServer $server, ?ArrayLogger $logger = null): SessionData
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
        $factoryClass::$pem = FakeHandshakeServer::keyPem();

        $conn = new class(fopen('php://memory', 'r')) extends PlainConnection
        {
            public static ?FakeHandshakeServer $server = null;

            public function request(string $payload): string
            {
                $server = self::$server;
                if ($server === null) {
                    throw new RuntimeException('fake connection: server not configured');
                }

                return $server->respond($payload);
            }
        };
        $conn::$server = $server;

        return $factoryClass::generate($conn, 2, $logger);
    }

    /**
     * @return list<array{level: string, message: string, context: array<string, mixed>}>
     */
    private function recordsOf(ArrayLogger $logger, string $message): array
    {
        return array_values(array_filter(
            $logger->records,
            static fn (array $record): bool => $record['message'] === $message
        ));
    }

    /**
     * ONE lifecycle event, asserted as a Custom Assertion.
     *
     * The four bare `foreach ($this->recordsOf(...))` loops this replaces all
     * asserted the same three things at the same three lines, so a failure
     * could only say "Failed asserting that two strings are identical" and
     * name neither the event nor the record.
     *
     * It is also STRONGER: a `foreach` over an empty set asserts nothing, so
     * the old form could only ever inspect a record that happened to exist.
     * The first assertion here requires the record to be there, which is what
     * makes the rest of them mean something.
     *
     * @param  array<string, mixed>  $expected  context key => expected value, asserted on EVERY record of the event
     * @param  array<string, mixed>  $notSame   context key => a value the key must NOT carry, on every record
     */
    private function assertLifecycleEvent(ArrayLogger $logger, string $event, array $expected, array $notSame = []): void
    {
        $records = $this->recordsOf($logger, $event);

        $this->assertNotSame([], $records, "the {$event} event must have been logged at all, or nothing below asserts anything");

        foreach ($records as $i => $record) {
            $this->assertSame('debug', $record['level'], "{$event} record {$i}: level");
            foreach ($expected as $key => $value) {
                $this->assertSame($value, $record['context'][$key] ?? null, "{$event} record {$i}: context[{$key}]");
            }
            foreach ($notSame as $key => $value) {
                $this->assertNotSame($value, $record['context'][$key] ?? null, "{$event} record {$i}: context[{$key}] must not be the placeholder");
            }
        }
    }
}
