<?php

declare(strict_types=1);

/**
 * Malicious-proxy fixture for the peer_name MITM test.
 *
 * Accepts a plain HTTP CONNECT, answers 200 for ANY requested target, then
 * splices the client to a local TLS server — i.e. it lies about where the
 * tunnel goes and terminates TLS with its own certificate. Exactly the
 * full-MITM shape finding C4 describes.
 *
 * Usage: php tls_mitm_proxy.php <targetPort> <cases>
 * STDOUT: P_PROXY_PORT <port>, P_REQUEST <CONNECT line>, P_CASE<n>_DONE <bool>
 */
$targetPort = (int) ($argv[1] ?? 0);
$cases = (int) ($argv[2] ?? 1);

if ($targetPort <= 0 || $targetPort > 65535) {
    fwrite(STDERR, "P_FAIL usage: tls_mitm_proxy.php <targetPort> <cases>\n");
    exit(1);
}

$proxy = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
if (! $proxy) {
    fwrite(STDERR, "P_FAIL proxy bind: {$errstr} ({$errno})\n");
    exit(1);
}

[, $proxyPort] = explode(':', (string) stream_socket_get_name($proxy, false));
echo 'P_PROXY_PORT '.$proxyPort."\n";
fflush(STDOUT);

for ($case = 1; $case <= $cases; $case++) {
    $client = @stream_socket_accept($proxy, 30);
    if (! $client) {
        echo "P_CASE{$case}_NO_CLIENT\n";
        fflush(STDOUT);
        break;
    }

    stream_set_timeout($client, 10);
    $head = '';
    while (! str_contains($head, "\r\n\r\n")) {
        $chunk = @fread($client, 8192);
        if ($chunk === false || $chunk === '') {
            break;
        }
        $head .= $chunk;
    }
    echo 'P_REQUEST '.trim(strtok($head, "\r\n") ?: '')."\n";
    fflush(STDOUT);

    // The lie: every requested target resolves to the local TLS server.
    $upstream = @stream_socket_client("tcp://127.0.0.1:{$targetPort}", $ue, $ues, 5);
    if (! $upstream) {
        @fwrite($client, "HTTP/1.1 502 Bad Gateway\r\n\r\n");
        @fclose($client);
        echo "P_CASE{$case}_NO_UPSTREAM\n";
        fflush(STDOUT);

        continue;
    }
    @fwrite($client, "HTTP/1.1 200 Connection established\r\n\r\n");
    stream_set_blocking($client, false);

    $sawTunnelBytes = false;
    $deadline = microtime(true) + 15;
    while (microtime(true) < $deadline) {
        $read = [$client, $upstream];
        $write = null;
        $except = null;
        if (@stream_select($read, $write, $except, 0, 200000) < 1) {
            continue;
        }
        foreach ($read as $side) {
            $peer = $side === $client ? $upstream : $client;
            $bytes = @fread($side, 16384);
            if ($bytes === false || $bytes === '') {
                continue;
            }
            @fwrite($peer, $bytes);
            if ($side === $upstream) {
                $sawTunnelBytes = true;
            }
        }
        // Once both directions have carried handshake traffic and the client
        // has gone away, the case is over.
        if ($sawTunnelBytes && feof($client)) {
            break;
        }
    }

    echo 'P_CASE'.$case.'_TUNNELED_BYTES '.var_export($sawTunnelBytes, true)."\n";
    fflush(STDOUT);
    @fclose($upstream);
    @fclose($client);
    usleep(200_000);
}

echo "P_EXIT\n";
fflush(STDOUT);
exit(0);
