<?php

declare(strict_types=1);

/**
 * TLS target for the peer_name MITM test: a real TLS server that presents a
 * self-signed certificate issued FOR ITSELF (CN=127.0.0.1, SAN IP:127.0.0.1).
 *
 * This stands in for the on-path proxy: it hands the client a certificate that
 * chains fine (the test passes it as `cafile`) but says nothing whatsoever
 * about Telegram.
 *
 * Usage: php tls_mitm_target.php <certfile> <keyfile>
 * STDOUT: P_TLS_PORT <port>
 */
$certFile = $argv[1] ?? '';
$keyFile = $argv[2] ?? '';

if ($certFile === '' || $keyFile === '' || ! is_file($certFile) || ! is_file($keyFile)) {
    fwrite(STDERR, "P_FAIL usage: tls_mitm_target.php <certfile> <keyfile>\n");
    exit(1);
}

$server = stream_socket_server(
    'tls://127.0.0.1:0',
    $errno,
    $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
    stream_context_create(['ssl' => [
        'local_cert' => $certFile,
        'local_pk' => $keyFile,
        'allow_self_signed' => true,
    ]])
);

if (! $server) {
    fwrite(STDERR, "P_FAIL target bind: {$errstr} ({$errno})\n");
    exit(1);
}

[, $port] = explode(':', (string) stream_socket_get_name($server, false));
echo 'P_TLS_PORT '.$port."\n";
fflush(STDOUT);

// Serve connections until the harness kills the process. The client aborts the
// handshake the moment the name check fails, so most connections never reach a
// payload read; that is the expected outcome, not a failure.
while (true) {
    $conn = @stream_socket_accept($server, 30);
    if (! $conn) {
        continue;
    }
    stream_set_timeout($conn, 8);
    $data = @fread($conn, 4);
    echo 'P_TARGET_GOT '.strlen((string) $data)."\n";
    fflush(STDOUT);
    if (strlen((string) $data) >= 4) {
        @fwrite($conn, 'PONG');
    }
    @fclose($conn);
}
