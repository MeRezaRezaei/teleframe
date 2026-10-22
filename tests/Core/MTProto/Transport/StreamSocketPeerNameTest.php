<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\PlainConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\StreamSocket;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * C4 (CRITICAL, full MITM of the tunneled stream) + H1 (unwired/unencrypted
 * proxy feature) + H2 (silent process-global TLS kill switch).
 *
 * The peer_name test is END-TO-END wherever possible: a real TLS server presents
 * a real self-signed certificate for itself, a real HTTP CONNECT proxy lies
 * about the target and splices the client to it, and the client must refuse the
 * session. Where a handshake is not exercised the assertions are made on the
 * context options directly, and say so.
 */
final class StreamSocketPeerNameTest extends TestCase
{
    public const INSECURE_TLS_KEY = 'TF_INSECURE_TLS';

    private const PROCESS_STARTUP_POLL_US = 20_000;

    private const PROCESS_OUTPUT_POLL_US = 50_000;

    /** @var list<array{proc: resource, pipes: array<int, resource>}> */
    private array $children = [];

    private ?string $certDir = null;

    /** @var array<string, string> base name => [cert, key] */
    private array $certs = [];

    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 4).'/tests/Schema/FixesAutoload.php';
    }

    protected function tearDown(): void
    {
        putenv(self::INSECURE_TLS_KEY);

        foreach ($this->children as $child) {
            foreach ($child['pipes'] as $pipe) {
                if (is_resource($pipe)) {
                    @fclose($pipe);
                }
            }
            if (is_resource($child['proc'])) {
                @proc_terminate($child['proc']);
                @proc_close($child['proc']);
            }
        }
        $this->children = [];

        if ($this->certDir !== null && is_dir($this->certDir)) {
            foreach ((array) glob($this->certDir.'/*') as $file) {
                if (is_string($file)) {
                    @unlink($file);
                }
            }
            @rmdir($this->certDir);
            $this->certDir = null;
        }
        $this->certs = [];

        parent::tearDown();
    }

    // ---------------------------------------------------------------- C4 unit

    public function test_peer_name_is_the_telegram_dc_not_the_proxy(): void
    {
        // A bare DC IP cannot authenticate a name, so it collapses to the DC name.
        self::assertSame('dc.telegram.org', StreamSocket::resolvePeerName('149.154.167.51'));
        self::assertSame(
            StreamSocket::DEFAULT_TLS_PEER_NAME,
            StreamSocket::resolvePeerName('149.154.167.51', '127.0.0.1'),
            'an explicit IP peer name is refused too — a proxy address must never be pinnable'
        );
        // A real hostname is used as-is.
        self::assertSame('dc.example.org', StreamSocket::resolvePeerName('dc.example.org'));
        self::assertSame('lab.internal', StreamSocket::resolvePeerName('149.154.167.51', 'lab.internal'));
    }

    public function test_tls_context_carries_peer_name_for_the_tunnelled_target(): void
    {
        $proxy = ['type' => 'http', 'host' => '127.0.0.1', 'port' => 1080];
        $peerName = StreamSocket::resolvePeerName('149.154.167.51', null);
        $options = StreamSocket::tlsContextOptions(null, $peerName, null, null);

        self::assertSame('dc.telegram.org', $options['peer_name'] ?? null);
        self::assertTrue($options['verify_peer']);
        self::assertTrue($options['verify_peer_name']);
        self::assertNotContains(
            $proxy['host'],
            $options,
            'the proxy host must not appear anywhere in the TLS context'
        );
        self::assertNotSame($proxy['host'], $options['peer_name']);
    }

    // ------------------------------------------------------------- C4 end-to-end

    public function test_tunnelled_tls_rejects_a_proxy_presenting_its_own_certificate(): void
    {
        $mitm = $this->startMitm(1);
        $cert = $this->cert('proxy');

        $thrown = null;
        try {
            // The client believes it is dialling Telegram DC 2; the proxy lies.
            $socket = StreamSocket::createConnection(
                host: '149.154.167.51',
                port: 443,
                proxy: $mitm['proxy'],
                timeout: 5.0,
                tls: true,
                cafile: $cert[0],
            );
            @fclose($socket);
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown, 'a proxy terminating TLS with its own certificate must be rejected');
        self::assertStringContainsString('TLS upgrade over the proxy tunnel failed', $thrown->getMessage());

        $last = error_get_last();
        self::assertNotFalse($last, 'OpenSSL must have reported why the certificate was refused');
        self::assertStringContainsString(
            'did not match expected CN=`dc.telegram.org',
            $last['message'],
            'the rejection must be the NAME check against the Telegram DC, not an incidental failure'
        );

        $report = $this->drain($mitm['report'], 1);
        self::assertStringContainsString('CONNECT 149.154.167.51:443', $report, 'the tunnel really was dialled as the DC');
        self::assertStringContainsString('P_CASE1_TUNNELED_BYTES true', $report, 'the client did present a ClientHello through the tunnel');
    }

    public function test_the_pre_fix_context_accepts_that_same_certificate(): void
    {
        // Guards the meaning of the test above: the context the old code built
        // (verify_peer + verify_peer_name, NO peer_name) completes the handshake
        // against the same attacker certificate, because PHP then validates the
        // name against the transport URI — the proxy.
        $mitm = $this->startMitm(1);
        $cert = $this->cert('proxy');

        $context = stream_context_create(['ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'cafile' => $cert[0],
        ]]);

        $socket = stream_socket_client(
            'tcp://'.$mitm['proxy']['host'].':'.$mitm['proxy']['port'],
            $errno,
            $errstr,
            5.0,
            STREAM_CLIENT_CONNECT,
            $context
        );
        self::assertIsResource($socket, "legacy dial failed: {$errstr}");

        @fwrite($socket, "CONNECT 149.154.167.51:443 HTTP/1.1\r\nHost: 149.154.167.51:443\r\n\r\n");
        $head = '';
        while (! str_contains($head, "\r\n\r\n")) {
            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $head .= $chunk;
        }
        self::assertStringContainsString('200', $head, 'the fixture must accept the CONNECT');

        // This is the pre-fix context verbatim: a full MITM of the "TLS" session.
        $established = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        self::assertTrue(
            $established,
            'the legacy context is expected to ACCEPT the proxy certificate — that is the hole being closed'
        );
        $crypto = stream_get_meta_data($socket)['crypto'] ?? [];
        self::assertArrayNotHasKey('peer_name', $crypto, 'the legacy context leaves the peer name unset');
        @fclose($socket);

        $this->drain($mitm['report'], 1);
    }

    public function test_direct_tls_with_a_matching_peer_name_completes_and_round_trips(): void
    {
        // Control: the machinery (chain, handshake, name check) is sound — only
        // the NAME differs from the rejection above.
        $target = $this->startTlsTarget('lab');
        $cert = $this->cert('lab');

        $socket = StreamSocket::createConnection(
            host: '127.0.0.1',
            port: $target,
            timeout: 5.0,
            tls: true,
            cafile: $cert[0],
            peerName: 'lab.internal',
        );

        self::assertIsResource($socket);
        self::assertArrayHasKey('crypto', stream_get_meta_data($socket), 'TLS must actually be on the direct path');

        StreamSocket::write($socket, 'PING');
        self::assertSame('PONG', StreamSocket::readExact($socket, 4));
        @fclose($socket);
    }

    public function test_direct_path_does_not_silently_ignore_a_tls_request(): void
    {
        $target = $this->startTlsTarget('lab');
        $cert = $this->cert('lab');

        // Before the fix the $proxy === null branch returned without ever
        // reading $tls, so this returned a PLAINTEXT socket and wrote 'PING' to
        // a TLS server. Assert the upgrade actually happened.
        $socket = StreamSocket::createConnection(
            host: '127.0.0.1',
            port: $target,
            proxy: null,
            timeout: 5.0,
            tls: true,
            cafile: $cert[0],
            peerName: 'lab.internal',
        );

        $crypto = stream_get_meta_data($socket)['crypto'] ?? null;
        self::assertNotNull($crypto, 'a caller asking for TLS must not silently get plaintext');
        // NB: PHP's crypto meta does NOT echo the peer name back (the reviewer
        // saw peer_name NULL even on a verified session), so presence of crypto
        // plus a completed round trip is the observable here.
        self::assertStringStartsWith('TLS', (string) ($crypto['protocol'] ?? ''));
        StreamSocket::write($socket, 'PING');
        self::assertSame('PONG', StreamSocket::readExact($socket, 4));
        @fclose($socket);
    }

    public function test_direct_tls_failure_is_reported_not_swallowed(): void
    {
        // A real TLS endpoint, but asked for under the wrong name: the direct
        // path must throw, not hand back an unverified socket.
        $target = $this->startTlsTarget('lab');
        $cert = $this->cert('lab');

        try {
            $socket = StreamSocket::createConnection(
                host: '127.0.0.1',
                port: $target,
                proxy: null,
                timeout: 5.0,
                tls: true,
                cafile: $cert[0],
                peerName: 'dc.telegram.org',
            );
            @fclose($socket);
            self::fail('a name mismatch on the direct path must not be accepted');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('TLS upgrade on the direct connection failed', $e->getMessage());
        }
    }

    // ------------------------------------------------------------------- H1 wiring

    public function test_client_encrypted_path_honours_the_proxy_configuration(): void
    {
        $proxy = $this->startEchoProxy(1);
        $client = $this->client($proxy);

        // Without the proxy the same dial must fail (closed local port), so a
        // success here can only come from the proxy actually being used.
        $dial = new ReflectionMethod($client, 'connectEncrypted');
        $dial->setAccessible(true);

        $conn = $dial->invoke($client, $client->getSession(), '127.0.0.1', 443);
        self::assertInstanceOf(EncryptedConnection::class, $conn);

        $report = $this->drain($this->lastReport(), 1);
        self::assertStringContainsString('CONNECT 127.0.0.1:443', $report, 'connectEncrypted must pass the proxy through');
    }

    public function test_encrypted_connection_connect_honours_the_proxy_configuration(): void
    {
        $proxy = $this->startEchoProxy(1);
        $session = new SessionData(2, str_repeat('1', 32));

        $conn = EncryptedConnection::connect(
            $session,
            '127.0.0.1',
            443,
            10.0,
            null,
            ['type' => 'http', 'host' => '127.0.0.1', 'port' => $proxy],
        );

        self::assertInstanceOf(EncryptedConnection::class, $conn);
        $report = $this->drain($this->lastReport(), 1);
        self::assertStringContainsString('CONNECT 127.0.0.1:443', $report);
    }

    public function test_plain_connection_connect_honours_the_proxy_configuration(): void
    {
        $proxy = $this->startEchoProxy(1);

        $conn = PlainConnection::connect(
            '127.0.0.1',
            443,
            10.0,
            null,
            ['type' => 'http', 'host' => '127.0.0.1', 'port' => $proxy],
        );

        self::assertInstanceOf(PlainConnection::class, $conn);
        $report = $this->drain($this->lastReport(), 1);
        self::assertStringContainsString('CONNECT 127.0.0.1:443', $report);
    }

    public function test_client_dials_without_a_proxy_when_none_is_configured(): void
    {
        // The counterpart: with no proxy configured the call goes straight out
        // and (here, to a closed local port) fails as a direct-dial failure.
        $client = $this->client();
        $dial = new ReflectionMethod($client, 'connectEncrypted');
        $dial->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MTProto connect to 127.0.0.1:1 failed');

        $dial->invoke($client, $client->getSession(), '127.0.0.1', 1);
    }

    public function test_proxy_errors_are_no_longer_silent(): void
    {
        // Before the fix $logger was not forwarded to connectViaProxy, so the
        // proxy connect-failure log was unreachable dead code.
        $logger = new ArrayLogger;
        $closed = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        self::assertIsResource($closed, $errstr);
        [, $deadPort] = explode(':', (string) stream_socket_get_name($closed, false));
        @fclose($closed);

        try {
            StreamSocket::createConnection(
                host: '149.154.167.51',
                port: 443,
                proxy: ['type' => 'http', 'host' => '127.0.0.1', 'port' => (int) $deadPort],
                timeout: 1.0,
                tls: true,
                logger: $logger,
            );
            self::fail('a dead proxy must throw');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Failed to connect to proxy', $e->getMessage());
        }

        $messages = array_column($logger->records, 'message');
        self::assertContains('teleframe.mtproto.socket.connect', $messages, 'the proxy failure must be logged');
        $record = null;
        foreach ($logger->records as $entry) {
            if ($entry['message'] === 'teleframe.mtproto.socket.connect' && $entry['level'] === 'error') {
                $record = $entry;
            }
        }
        self::assertNotNull($record, 'the proxy error log line was dead code before this fix');
        self::assertArrayHasKey('proxy', $record['context']);
    }

    public function test_sub_second_timeout_does_not_mean_block_forever(): void
    {
        $port = $this->startEchoProxy(1);
        $proxy = ['type' => 'http', 'host' => '127.0.0.1', 'port' => $port];

        // stream_set_timeout(0) means "block forever", so a (int) cast of a
        // sub-1.0 value had to be floored, not truncated.
        $streamTimeout = new ReflectionMethod(StreamSocket::class, 'streamTimeout');
        $streamTimeout->setAccessible(true);
        self::assertSame(1, $streamTimeout->invoke(null, 0.4));
        self::assertSame(1, $streamTimeout->invoke(null, 0.0));
        self::assertSame(1, $streamTimeout->invoke(null, 1.0));
        self::assertSame(5, $streamTimeout->invoke(null, 4.2));

        $connectTimeout = new ReflectionMethod(StreamSocket::class, 'connectTimeout');
        $connectTimeout->setAccessible(true);
        self::assertGreaterThan(0.0, $connectTimeout->invoke(null, 0.0));

        $socket = StreamSocket::createConnection(
            host: '149.154.167.51',
            port: 443,
            proxy: $proxy,
            timeout: 0.4,
        );
        self::assertIsResource($socket);
        @fclose($socket);
        $this->drain($this->lastReport(), 1);
    }

    // ------------------------------------------------------------------- H2 opt-out

    public function test_insecure_opt_out_is_loud(): void
    {
        putenv(self::INSECURE_TLS_KEY.'=1');
        $logger = new ArrayLogger;

        $options = StreamSocket::tlsContextOptions('/tmp/ignored.pem', 'dc.telegram.org', null, $logger);

        self::assertFalse($options['verify_peer']);
        self::assertFalse($options['verify_peer_name']);
        self::assertNotEmpty(
            array_filter(
                $logger->records,
                fn (array $r): bool => $r['level'] === 'warning'
                    && $r['message'] === 'teleframe.mtproto.socket.tls_verification_disabled'
            ),
            'disabling verification must never be silent'
        );
    }

    public function test_explicit_verify_peer_pin_is_loud_too(): void
    {
        $logger = new ArrayLogger;
        $options = StreamSocket::tlsContextOptions(null, 'dc.telegram.org', false, $logger);

        self::assertFalse($options['verify_peer']);
        self::assertNotEmpty(array_filter($logger->records, fn (array $r): bool => $r['level'] === 'warning'));
    }

    public function test_verification_pin_is_per_call_and_not_sticky(): void
    {
        // A process-wide mutable static used to mean one insecure construction
        // disabled verification forever, with nothing able to re-enable it.
        $this->assertFalse(
            (new \ReflectionClass(StreamSocket::class))->hasProperty('defaultVerifyPeer'),
            'the process-wide verifyPeer static must be gone'
        );
        self::assertSame(
            ['verify_peer' => false, 'verify_peer_name' => false],
            StreamSocket::tlsContextOptions(null, 'dc.telegram.org', false, null),
            'an explicit false applies to the call it is passed to'
        );
        self::assertSame(
            ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => 'dc.telegram.org'],
            StreamSocket::tlsContextOptions(null, 'dc.telegram.org', null, null),
            'the next call is verified again — nothing can latch'
        );
    }

    public function test_explicit_true_pin_forbids_the_environment_opt_out(): void
    {
        putenv(self::INSECURE_TLS_KEY.'=1');
        $logger = new ArrayLogger;

        $options = StreamSocket::tlsContextOptions(null, 'dc.telegram.org', true, $logger);

        self::assertTrue($options['verify_peer'], 'a true pin must win over TF_INSECURE_TLS');
        self::assertTrue($options['verify_peer_name']);
        self::assertSame(
            [],
            array_filter($logger->records, fn (array $r): bool => $r['level'] === 'warning'),
            'nothing was disabled, so nothing should be warned about'
        );
    }

    public function test_ca_bundle_still_fails_closed(): void
    {
        // Preserved behaviour (reviewer-confirmed): a missing or bogus CA bundle
        // must reject rather than fall back to something permissive.
        $mitm = $this->startMitm(1);

        $thrown = null;
        try {
            $socket = StreamSocket::createConnection(
                host: '149.154.167.51',
                port: 443,
                proxy: $mitm['proxy'],
                timeout: 5.0,
                tls: true,
                cafile: '/nonexistent/teleframe-ca-bundle.pem',
            );
            @fclose($socket);
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown, 'an unusable CA bundle must fail closed');
        $this->drain($mitm['report'], 1);
    }

    // ------------------------------------------------------------------- helpers

    private function client(?int $proxyPort = null): Client
    {
        $session = new SessionData(2, str_repeat('1', 32));
        $client = new Client(1, str_repeat('0', 32), $session, false);
        if ($proxyPort !== null) {
            $client->setProxy(['type' => 'http', 'host' => '127.0.0.1', 'port' => $proxyPort]);
        }

        return $client;
    }

    /**
     * Generates (once per test) a self-signed certificate.
     *
     * @param  string  $name  'proxy' → CN=127.0.0.1 + SAN IP:127.0.0.1 (a proxy
     *                        presenting its own address); 'lab' → CN=lab.internal
     * @return array{0: string, 1: string} [cert path, key path]
     */
    private function cert(string $name): array
    {
        if (isset($this->certs[$name])) {
            return $this->certs[$name];
        }

        if ($this->certDir === null) {
            $openssl = trim((string) @shell_exec('command -v openssl 2>/dev/null'));
            if ($openssl === '') {
                self::markTestSkipped('the openssl CLI is required to mint the test certificate');
            }
            $dir = sys_get_temp_dir().'/teleframe-peer-name-'.bin2hex(random_bytes(6));
            if (! @mkdir($dir, 0700) && ! is_dir($dir)) {
                self::markTestSkipped('could not create a temp dir for the test certificate');
            }
            $this->certDir = $dir;
        }

        $subject = $name === 'lab' ? '/CN=lab.internal' : '/CN=127.0.0.1';
        $san = $name === 'lab' ? 'DNS:lab.internal' : 'IP:127.0.0.1';
        $cert = $this->certDir."/{$name}.pem";
        $key = $this->certDir."/{$name}.key";

        $cmd = sprintf(
            '%s req -x509 -newkey rsa:2048 -keyout %s -out %s -days 2 -nodes -subj %s -addext %s 2>&1',
            $openssl,
            escapeshellarg($key),
            escapeshellarg($cert),
            escapeshellarg($subject),
            escapeshellarg('subjectAltName='.$san)
        );
        exec($cmd, $out, $code);
        if ($code !== 0 || ! is_file($cert) || ! is_file($key)) {
            self::markTestSkipped('openssl could not mint a test certificate: '.implode(' ', $out));
        }

        return $this->certs[$name] = [$cert, $key];
    }

    /**
     * Starts the TLS target and the lying proxy in front of it.
     *
     * @return array{proxy: array{type: string, host: string, port: int}, report: resource}
     */
    private function startMitm(int $cases): array
    {
        $cert = $this->cert('proxy');
        $targetPort = $this->spawn(
            [PHP_BINARY, __DIR__.'/fixtures/tls_mitm_target.php', $cert[0], $cert[1]],
            'P_TLS_PORT'
        );
        $proxyPort = $this->spawn(
            [PHP_BINARY, __DIR__.'/fixtures/tls_mitm_proxy.php', (string) $targetPort, (string) $cases],
            'P_PROXY_PORT'
        );

        $index = count($this->children) - 1;

        return [
            'proxy' => ['type' => 'http', 'host' => '127.0.0.1', 'port' => $proxyPort],
            'report' => $this->children[$index]['pipes'][1],
        ];
    }

    private function startTlsTarget(string $name): int
    {
        $cert = $this->cert($name);

        return $this->spawn(
            [PHP_BINARY, __DIR__.'/fixtures/tls_mitm_target.php', $cert[0], $cert[1]],
            'P_TLS_PORT'
        );
    }

    private function startEchoProxy(int $cases): int
    {
        return $this->spawn(
            [PHP_BINARY, __DIR__.'/fixtures/http_echo_proxy.php', (string) $cases],
            'P_PROXY_PORT'
        );
    }

    /**
     * The STDOUT pipe of the most recently spawned fixture.
     *
     * @return resource
     */
    private function lastReport()
    {
        return $this->children[count($this->children) - 1]['pipes'][1];
    }

    /**
     * Spawns a fixture, waits for its ready line and returns the announced port.
     *
     * @param  list<string>  $cmd
     */
    private function spawn(array $cmd, string $marker): int
    {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $descriptors, $pipes, __DIR__);
        if (! is_resource($proc)) {
            self::fail('failed to spawn '.basename($cmd[1]));
        }
        @fclose($pipes[0]);
        $this->children[] = ['proc' => $proc, 'pipes' => $pipes];

        $deadline = microtime(true) + 10.0;
        $line = '';
        while (microtime(true) < $deadline && $line === '') {
            $line = (string) fgets($pipes[1], 8192);
            if ($line === '' && feof($pipes[1])) {
                $err = (string) stream_get_contents($pipes[2]);
                self::fail(basename($cmd[1])." did not report ready (stderr: {$err})");
            }
            if ($line === '') {
                usleep(self::PROCESS_STARTUP_POLL_US);
            }
        }

        $trimmed = trim($line);
        if (! str_starts_with($trimmed, $marker)) {
            self::fail(basename($cmd[1])." printed \"{$trimmed}\", expected {$marker}");
        }

        return (int) trim(substr($trimmed, strlen($marker)));
    }

    /**
     * Reads whatever the fixture has emitted so far and reaps it.
     *
     * @param  resource  $pipe
     */
    private function drain($pipe, int $cases): string
    {
        $out = '';
        $deadline = microtime(true) + 8.0;
        while (microtime(true) < $deadline) {
            stream_set_blocking($pipe, false);
            $chunk = (string) stream_get_contents($pipe);
            $out .= $chunk;
            if (substr_count($out, 'P_EXIT') >= 1 || substr_count($out, '_DONE') + substr_count($out, '_ECHO') >= $cases) {
                break;
            }
            usleep(self::PROCESS_OUTPUT_POLL_US);
        }
        stream_set_blocking($pipe, true);
        $out .= (string) stream_get_contents($pipe);

        return $out;
    }
}
