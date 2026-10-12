<?php

declare(strict_types=1);

/**
 * Fake proxy tunnel server for StreamSocket proxy unit tests.
 *
 * Spawned as a child process by StreamSocketTest (proc_open + PHP_BINARY).
 * Binds an ephemeral loopback listener, completes the configured proxy handshake
 * with the StreamSocket client, then — for the success roles — echoes one
 * round-trip of tunneled payload bytes back to the client.
 *
 * Talks to the test over STDOUT with P_* lines:
 *   P_READY  {"port": N}   listener bound; the port the test must dial
 *   P_TUNNEL {json}        handshake bytes observed (CONNECT line / SOCKS address)
 *   P_ECHO   {"echoed": N} tunneled payload round-trip completed
 *
 * Usage: php proxy_tunnel_server.php <role> [--user U] [--pass P]
 *
 * Roles:
 *   http               HTTP CONNECT -> 200, echo
 *   http-407           HTTP CONNECT -> 407 Proxy Authentication Required
 *   http-502           HTTP CONNECT -> 502 Bad Gateway
 *   http-auth          HTTP CONNECT with credentials -> expect Proxy-Authorization Basic -> 200, echo
 *   socks5             SOCKS5 no-auth -> success, echo
 *   socks5-auth        SOCKS5 + RFC 1929 (--user/--pass) -> success, echo
 *   socks5-auth-reject SOCKS5 + RFC 1929 -> reject credentials (status 0x01)
 *   socks5-method-deny SOCKS5 greeting -> method 0xFF
 *
 * Exit code 0 on success, 1 on failure. Diagnostics go to STDERR.
 */
function fail(string $message): never
{
    fwrite(STDERR, 'P_FAIL '.$message."\n");
    exit(1);
}

function readExact($stream, int $length): string
{
    $data = '';
    while (strlen($data) < $length) {
        $chunk = fread($stream, $length - strlen($data));
        if ($chunk === false || $chunk === '') {
            fail('EOF while reading '.$length.' bytes');
        }
        $data .= $chunk;
    }

    return $data;
}

function readHead($stream): string
{
    $data = '';
    while (! str_contains($data, "\r\n\r\n")) {
        $chunk = fread($stream, 8192);
        if ($chunk === false || $chunk === '') {
            fail('EOF while reading HTTP response head');
        }
        $data .= $chunk;
    }

    return $data;
}

function echoRoundTrip($client): void
{
    $data = fread($client, 4096);
    if ($data === false || $data === '') {
        fail('no tunneled payload bytes to echo');
    }
    fwrite($client, $data);
    echo 'P_ECHO '.json_encode(['echoed' => strlen($data)])."\n";
    fflush(STDOUT);
    usleep(100_000);
}

[$script, $role] = [$argv[0] ?? '', $argv[1] ?? ''];
$user = null;
$pass = null;
for ($i = 2; $i + 1 < $argc; $i += 2) {
    if ($argv[$i] === '--user') {
        $user = $argv[$i + 1];
    } elseif ($argv[$i] === '--pass') {
        $pass = $argv[$i + 1];
    }
}

if ($role === '') {
    fail('missing role argument');
}

$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
if (! $server) {
    fail("listener bind failed: {$errstr} ({$errno})");
}
[$ip, $port] = explode(':', stream_socket_get_name($server, false));
echo 'P_READY '.json_encode(['port' => (int) $port])."\n";
fflush(STDOUT);

stream_set_timeout($server, 5);
$client = stream_socket_accept($server, 5);
if (! $client) {
    fail('no client connection accepted');
}
stream_set_timeout($client, 5);

switch ($role) {
    case 'http':
        $head = readHead($client);
        $lineEnd = strpos($head, "\r\n");
        $requestLine = $lineEnd === false ? $head : substr($head, 0, $lineEnd);
        echo 'P_TUNNEL '.json_encode(['request' => $requestLine, 'auth' => null])."\n";
        fflush(STDOUT);
        fwrite($client, "HTTP/1.1 200 Connection established\r\n\r\n");
        echoRoundTrip($client);
        break;

    case 'http-auth':
        $head = readHead($client);
        $lineEnd = strpos($head, "\r\n");
        $requestLine = $lineEnd === false ? $head : substr($head, 0, $lineEnd);
        $authStart = strpos($head, 'Proxy-Authorization: ');
        $authLine = null;
        if ($authStart !== false) {
            $authEnd = strpos($head, "\r\n", $authStart);
            $authLine = $authEnd === false ? substr($head, $authStart) : substr($head, $authStart, $authEnd - $authStart);
        }
        echo 'P_TUNNEL '.json_encode(['request' => $requestLine, 'auth' => $authLine])."\n";
        fflush(STDOUT);
        if ($authLine === null) {
            fwrite($client, "HTTP/1.1 407 Proxy Authentication Required\r\n\r\n");
            fail('client did not send Proxy-Authorization');
        }
        fwrite($client, "HTTP/1.1 200 Connection established\r\n\r\n");
        echoRoundTrip($client);
        break;

    case 'http-407':
        readHead($client);
        fwrite($client, "HTTP/1.1 407 Proxy Authentication Required\r\n\r\n");
        break;

    case 'http-502':
        readHead($client);
        fwrite($client, "HTTP/1.1 502 Bad Gateway\r\n\r\n");
        break;

    case 'socks5':
    case 'socks5-auth':
    case 'socks5-auth-reject':
    case 'socks5-method-deny':
        $greeting = readExact($client, 3); // VER NMETHODS METHODS
        if ($greeting[0] !== "\x05" || $greeting[1] !== "\x01") {
            fail('bad SOCKS5 greeting 0x'.bin2hex($greeting));
        }
        $method = $greeting[2];

        if ($role === 'socks5-method-deny') {
            fwrite($client, "\x05\xff");
            break;
        }

        if ($method === "\x02") {
            fwrite($client, "\x05\x02"); // accept username/password method
            readExact($client, 1); // RFC 1929 VER
            $userLength = ord(readExact($client, 1));
            $gotUser = readExact($client, $userLength);
            $passLength = ord(readExact($client, 1));
            $gotPass = readExact($client, $passLength);

            if ($role === 'socks5-auth-reject') {
                fwrite($client, "\x01\x01");
                break;
            }

            if ($gotUser !== (string) ($user ?? '') || $gotPass !== (string) ($pass ?? '')) {
                fwrite($client, "\x01\x01");
                fail('username/password mismatch');
            }
            fwrite($client, "\x01\x00");
        } else {
            fwrite($client, "\x05\x00");
        }

        $connect = readExact($client, 4); // VER CMD RSV ATYP
        if ($connect[0] !== "\x05" || $connect[1] !== "\x01") {
            fail('bad SOCKS5 CONNECT request');
        }
        $atyp = $connect[3];
        if ($atyp === "\x01") {
            $addr = inet_ntop(readExact($client, 4));
            $targetPort = unpack('n', readExact($client, 2))[1];
        } elseif ($atyp === "\x03") {
            $addrLength = ord(readExact($client, 1));
            $addr = readExact($client, $addrLength);
            $targetPort = unpack('n', readExact($client, 2))[1];
        } else {
            fail('unsupported ATYP 0x'.bin2hex($atyp));
        }

        echo 'P_TUNNEL '.json_encode(['atyp' => bin2hex($atyp), 'addr' => $addr, 'port' => $targetPort])."\n";
        fflush(STDOUT);
        fwrite($client, "\x05\x00\x00\x01\x00\x00\x00\x00\x00\x00"); // success, IPv4 zero bind
        echoRoundTrip($client);
        break;

    default:
        fail('unknown role '.$role);
}

fclose($client);
fclose($server);
exit(0);
