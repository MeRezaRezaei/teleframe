<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\StreamSocket;
use PHPUnit\Framework\TestCase;

/**
 * W-24 (senior inspection, security): TLS peer verification on the
 * tunneled target stream must be ON by default, with an explicit
 * TF_INSECURE_TLS opt-out for self-signed dev endpoints. Pure unit tests,
 * hermetic — no sockets.
 */
final class TransportW24Test extends TestCase
{
    public const INSECURE_TLS_KEY = 'TF_INSECURE_TLS';

    // vendor/ is a symlink to the main tree, so vendor/autoload.php resolves
    // StreamSocket to the main tree's unfixed src/. Pin this tree's sources
    // before any test body autoloads the class (see tests/Schema/FixesAutoload.php).
    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 4).'/tests/Schema/FixesAutoload.php';
    }

    protected function tearDown(): void
    {
        putenv(self::INSECURE_TLS_KEY);
        parent::tearDown();
    }

    public function test_default_tls_context_is_verified(): void
    {
        self::assertSame(
            ['verify_peer' => true, 'verify_peer_name' => true],
            StreamSocket::tlsContextOptions(),
            'default context verifies the peer and its name with the system CA store',
        );
        self::assertSame(
            ['verify_peer' => true, 'verify_peer_name' => true],
            StreamSocket::tlsContextOptions(null),
            'null cafile is the verified default',
        );
    }

    public function test_insecure_opt_out_defaults_to_off(): void
    {
        putenv(self::INSECURE_TLS_KEY);
        self::assertFalse(StreamSocket::insecureTlsEnabled(), 'unset env keeps verification on');
    }

    public function test_cafile_is_applied_when_provided(): void
    {
        $options = StreamSocket::tlsContextOptions('/tmp/some-w24-ca.pem');

        self::assertSame(true, $options['verify_peer']);
        self::assertSame(true, $options['verify_peer_name']);
        self::assertSame('/tmp/some-w24-ca.pem', $options['cafile'], 'explicit bundle overrides the system store');
    }

    public function test_insecure_opt_out_accepts_every_truthy_value(): void
    {
        foreach (['1', 'true', 'yes', 'on'] as $value) {
            putenv(self::INSECURE_TLS_KEY.'='.$value);

            self::assertTrue(StreamSocket::insecureTlsEnabled(), "TF_INSECURE_TLS={$value} must enable the opt-out");
            self::assertSame(
                ['verify_peer' => false, 'verify_peer_name' => false],
                StreamSocket::tlsContextOptions('/tmp/ignored-ca.pem'),
                "TF_INSECURE_TLS={$value} disables verification and drops the cafile",
            );
        }
    }

    public function test_insecure_opt_out_is_case_insensitive(): void
    {
        putenv(self::INSECURE_TLS_KEY.'=TRUE');

        self::assertTrue(StreamSocket::insecureTlsEnabled());
    }

    public function test_unrecognized_env_values_keep_verification_on(): void
    {
        foreach (['0', 'false', 'off', 'no', '2', 'random', ''] as $value) {
            putenv(self::INSECURE_TLS_KEY.'='.$value);

            self::assertFalse(StreamSocket::insecureTlsEnabled(), "TF_INSECURE_TLS={$value} must NOT enable the opt-out");
            self::assertSame(true, StreamSocket::tlsContextOptions()['verify_peer']);
        }
    }

    public function test_one_insecure_run_does_not_poison_following_same_process_tests(): void
    {
        putenv(self::INSECURE_TLS_KEY.'=1');
        self::assertFalse(StreamSocket::tlsContextOptions()['verify_peer']);

        putenv(self::INSECURE_TLS_KEY);
        self::assertTrue(StreamSocket::tlsContextOptions()['verify_peer'], 'tearDown must unset the env between tests');
    }
}
