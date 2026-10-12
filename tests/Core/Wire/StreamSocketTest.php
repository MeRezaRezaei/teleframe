<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\Wire;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\StreamSocket;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StreamSocketTest extends TestCase
{
    public function test_write_and_read_exact_over_loopback_echo(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertIsResource($server, "loopback server failed: $errstr");
        $addr = stream_socket_get_name($server, false);
        [$host, $port] = explode(':', $addr);

        $client = StreamSocket::createConnection($host, (int) $port, timeout: 5.0);
        $accepted = stream_socket_accept($server, 5.0);

        StreamSocket::write($client, 'hello-frame-bytes');

        // echo server: read then write back
        $got = fread($accepted, 17);
        fwrite($accepted, $got);

        $this->assertSame('hello-frame-bytes', StreamSocket::readExact($client, 17));
        fclose($client);
        fclose($accepted);
        fclose($server);
    }

    public function test_read_throws_on_premature_eof(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('EOF');
        // A closed pipe resource: stream_socket_pair is simplest
        [$a, $b] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
        fclose($b);
        StreamSocket::readExact($a, 64);
    }

    public function test_http_connect_tunnel(): void
    {
        $proxy = $this->startFakeProxy('http');
        try {
            $client = StreamSocket::createConnection('149.154.167.51', 443, [
                'type' => 'http',
                'host' => '127.0.0.1',
                'port' => $proxy['port'],
            ], 5.0);
            $this->assertIsResource($client);
            StreamSocket::write($client, 'ping-http');
            $this->assertSame('ping-http', StreamSocket::readExact($client, 9));
            fclose($client);
        } finally {
            $result = $this->stopFakeProxy($proxy['proc'], $proxy['pipes']);
        }

        $this->assertSame(0, $result['exit'], $result['err']);
        $tunnel = $this->pLine($result['out'], 'P_TUNNEL');
        $this->assertSame('CONNECT 149.154.167.51:443 HTTP/1.1', $tunnel['request']);
        $this->assertSame(9, $this->pLine($result['out'], 'P_ECHO')['echoed']);
    }

    public function test_http_connect_sends_basic_proxy_authorization(): void
    {
        $proxy = $this->startFakeProxy('http-auth', ['user' => 'agent', 'pass' => 'hunter2']);
        try {
            $client = StreamSocket::createConnection('127.0.0.1', 80, [
                'type' => 'http',
                'host' => '127.0.0.1',
                'port' => $proxy['port'],
                'username' => 'agent',
                'password' => 'hunter2',
            ], 5.0);
            $this->assertIsResource($client);
            StreamSocket::write($client, 'auth123');
            $this->assertSame('auth123', StreamSocket::readExact($client, 7));
            fclose($client);
        } finally {
            $result = $this->stopFakeProxy($proxy['proc'], $proxy['pipes']);
        }

        $this->assertSame(0, $result['exit'], $result['err']);
        $tunnel = $this->pLine($result['out'], 'P_TUNNEL');
        $this->assertSame('Proxy-Authorization: Basic '.base64_encode('agent:hunter2'), $tunnel['auth']);
    }

    public function test_http_proxy407_throws(): void
    {
        $proxy = $this->startFakeProxy('http-407');
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('HTTP/1.1 407 Proxy Authentication Required');
            StreamSocket::createConnection('149.154.167.51', 443, [
                'type' => 'http',
                'host' => '127.0.0.1',
                'port' => $proxy['port'],
            ], 5.0);
        } finally {
            $result = $this->stopFakeProxy($proxy['proc'], $proxy['pipes']);
            $this->assertSame(0, $result['exit'], $result['err']);
        }
    }

    public function test_http_proxy502_throws(): void
    {
        $proxy = $this->startFakeProxy('http-502');
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('HTTP/1.1 502 Bad Gateway');
            StreamSocket::createConnection('149.154.167.51', 443, [
                'type' => 'http',
                'host' => '127.0.0.1',
                'port' => $proxy['port'],
            ], 5.0);
        } finally {
            $result = $this->stopFakeProxy($proxy['proc'], $proxy['pipes']);
            $this->assertSame(0, $result['exit'], $result['err']);
        }
    }

    public function test_socks5_no_auth_tunnel(): void
    {
        $proxy = $this->startFakeProxy('socks5');
        try {
            $client = StreamSocket::createConnection('127.0.0.1', 4224, [
                'type' => 'socks5',
                'host' => '127.0.0.1',
                'port' => $proxy['port'],
            ], 5.0);
            $this->assertIsResource($client);
            StreamSocket::write($client, 'socks5ok');
            $this->assertSame('socks5ok', StreamSocket::readExact($client, 8));
            fclose($client);
        } finally {
            $result = $this->stopFakeProxy($proxy['proc'], $proxy['pipes']);
        }

        $this->assertSame(0, $result['exit'], $result['err']);
        $tunnel = $this->pLine($result['out'], 'P_TUNNEL');
        $this->assertSame('01', $tunnel['atyp'], 'IPv4 literal must use ATYP 0x01');
        $this->assertSame('127.0.0.1', $tunnel['addr']);
        $this->assertSame(4224, $tunnel['port']);
    }

    public function test_socks5_domain_method_tunnel(): void
    {
        // Hostname that cannot resolve to an IPv4 forces the RFC 1928 domain method.
        $proxy = $this->startFakeProxy('socks5');
        try {
            $client = StreamSocket::createConnection('teleframe.invalid', 443, [
                'type' => 'socks5',
                'host' => '127.0.0.1',
                'port' => $proxy['port'],
            ], 5.0);
            $this->assertIsResource($client);
            StreamSocket::write($client, 'dom-test');
            $this->assertSame('dom-test', StreamSocket::readExact($client, 8));
            fclose($client);
        } finally {
            $result = $this->stopFakeProxy($proxy['proc'], $proxy['pipes']);
        }

        $this->assertSame(0, $result['exit'], $result['err']);
        $tunnel = $this->pLine($result['out'], 'P_TUNNEL');
        $this->assertSame('03', $tunnel['atyp'], 'unresolvable hostname must use ATYP 0x03 (domain)');
        $this->assertSame('teleframe.invalid', $tunnel['addr']);
        $this->assertSame(443, $tunnel['port']);
    }

    public function test_socks5_username_password_tunnel(): void
    {
        $proxy = $this->startFakeProxy('socks5-auth', ['user' => 'u123', 'pass' => 'p456']);
        try {
            $client = StreamSocket::createConnection('127.0.0.1', 8443, [
                'type' => 'socks5',
                'host' => '127.0.0.1',
                'port' => $proxy['port'],
                'username' => 'u123',
                'password' => 'p456',
            ], 5.0);
            $this->assertIsResource($client);
            StreamSocket::write($client, 'cr-auth!');
            $this->assertSame('cr-auth!', StreamSocket::readExact($client, 8));
            fclose($client);
        } finally {
            $result = $this->stopFakeProxy($proxy['proc'], $proxy['pipes']);
        }

        $this->assertSame(0, $result['exit'], $result['err'], 'RFC 1929 sub-negotiation must pass');
        $this->assertSame('01', $this->pLine($result['out'], 'P_TUNNEL')['atyp']);
    }

    public function test_socks5_credentials_rejected_throws(): void
    {
        $proxy = $this->startFakeProxy('socks5-auth-reject', ['user' => 'u', 'pass' => 'p']);
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('rejected the username/password credentials');
            StreamSocket::createConnection('127.0.0.1', 443, [
                'type' => 'socks5',
                'host' => '127.0.0.1',
                'port' => $proxy['port'],
                'username' => 'u',
                'password' => 'p',
            ], 5.0);
        } finally {
            $result = $this->stopFakeProxy($proxy['proc'], $proxy['pipes']);
            $this->assertSame(0, $result['exit'], $result['err']);
        }
    }

    public function test_socks5_no_acceptable_method_throws(): void
    {
        $proxy = $this->startFakeProxy('socks5-method-deny');
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('no acceptable authentication method');
            StreamSocket::createConnection('127.0.0.1', 443, [
                'type' => 'socks5',
                'host' => '127.0.0.1',
                'port' => $proxy['port'],
            ], 5.0);
        } finally {
            $result = $this->stopFakeProxy($proxy['proc'], $proxy['pipes']);
            $this->assertSame(0, $result['exit'], $result['err']);
        }
    }

    public function test_unsupported_proxy_type_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsupported proxy type "ftp"');
        StreamSocket::createConnection('127.0.0.1', 443, [
            'type' => 'ftp',
            'host' => '127.0.0.1',
            'port' => 1080,
        ], 1.0);
    }

    public function test_missing_proxy_host_or_port_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('proxy config requires a host and a port');
        StreamSocket::createConnection('127.0.0.1', 443, ['type' => 'http'], 1.0);
    }

    public function test_proxy_connection_failure_throws(): void
    {
        // Bind-and-close to obtain a deterministically-closed ephemeral port.
        $closed = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertIsResource($closed, $errstr);
        $addr = stream_socket_get_name($closed, false);
        fclose($closed);
        [, $deadPort] = explode(':', $addr);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to connect to proxy');
        StreamSocket::createConnection('127.0.0.1', 443, [
            'type' => 'http',
            'host' => '127.0.0.1',
            'port' => (int) $deadPort,
        ], 1.0);
    }

    public function test_tls_upgrade_over_tunnel_fails_without_tls_server(): void
    {
        // The tunnel carries no TLS endpoint, so the enable_crypto upgrade that
        // wraps the TUNNELED stream (not the proxy hop) must fail loudly.
        $proxy = $this->startFakeProxy('http');
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('TLS upgrade over the proxy tunnel failed');
            StreamSocket::createConnection('127.0.0.1', 443, [
                'type' => 'http',
                'host' => '127.0.0.1',
                'port' => $proxy['port'],
            ], 5.0, true);
        } finally {
            $result = $this->stopFakeProxy($proxy['proc'], $proxy['pipes']);
            $this->assertSame(0, $result['exit'], $result['err']);
        }
    }

    /**
     * Spawns the fake proxy fixture, waits for its P_READY line and returns the
     * process handle, its pipes and the loopback port to dial.
     *
     * @return array{proc: resource, pipes: array<int, resource>, port: int}
     */
    private function startFakeProxy(string $role, array $credentials = []): array
    {
        $cmd = [PHP_BINARY, __DIR__.'/fixtures/proxy_tunnel_server.php', $role];
        foreach ($credentials as $key => $value) {
            $cmd[] = '--'.$key;
            $cmd[] = $value;
        }
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $descriptors, $pipes, __DIR__);
        if (! is_resource($proc)) {
            $this->fail('failed to spawn the fake proxy fixture');
        }
        fclose($pipes[0]);

        $readyLine = fgets($pipes[1], 8192);
        if (! is_string($readyLine) || ! str_starts_with(trim($readyLine), 'P_READY')) {
            $err = stream_get_contents($pipes[2]);
            $this->fail("fake proxy fixture did not report ready (stderr: {$err})");
        }
        $ready = json_decode(trim(substr(trim($readyLine), strlen('P_READY'))), true, 512, JSON_THROW_ON_ERROR);

        return ['proc' => $proc, 'pipes' => $pipes, 'port' => $ready['port']];
    }

    /**
     * Drains the fixture's stdout/stderr and reaps the process.
     *
     * @param  resource  $proc
     * @param  array<int, resource>  $pipes
     * @return array{out: string, err: string, exit: int}
     */
    private function stopFakeProxy($proc, array $pipes): array
    {
        $deadline = microtime(true) + 6.0;
        while (microtime(true) < $deadline && ! feof($pipes[1])) {
            $read = [$pipes[1]];
            $write = null;
            $except = null;
            if (@stream_select($read, $write, $except, 0, 250_000) > 0) {
                break;
            }
        }
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['out' => $out, 'err' => trim($err), 'exit' => proc_close($proc)];
    }

    /**
     * Extracts the JSON payload of the first P_<marker> line from the fixture output.
     */
    private function pLine(string $out, string $marker): array
    {
        foreach (explode("\n", $out) as $line) {
            if (str_starts_with($line, $marker)) {
                return json_decode(trim(substr($line, strlen($marker))), true, 512, JSON_THROW_ON_ERROR);
            }
        }

        $this->fail("missing {$marker} line in fixture output: {$out}");
    }
}
