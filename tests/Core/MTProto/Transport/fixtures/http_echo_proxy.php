<?php

declare(strict_types=1);

/**
 * HTTP CONNECT proxy fixture for the socket-wiring tests: answers 200 for any
 * target and echoes one round-trip of tunneled bytes, so a caller that dials
 * through it can complete a handshake-free payload round trip.
 *
 * Usage: php http_echo_proxy.php <cases>
 * STDOUT: P_PROXY_PORT <port>, P_REQUEST <CONNECT line>, P_CASE<n>_ECHO <bytes>
 */
$cases = (int) ($argv[1] ?? 1);

$proxy = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
if (! $proxy) {
    fwrite(STDERR, "P_FAIL proxy bind: {$errstr} ({$errno})\n");
    exit(1);
}

[, $proxyPort] = explode(':', (string) stream_socket_get_name($proxy, false));
echo 'P_PROXY_PORT '.$proxyPort."\n";
fflush(STDOUT);

for ($case = 1; $case <= $cases; $case++) {
    $client = @stream_socket_accept($proxy, 20);
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
    @fwrite($client, "HTTP/1.1 200 Connection established\r\n\r\n");

    $payload = @fread($client, 4096);
    if ($payload === false || $payload === '') {
        echo "P_CASE{$case}_ECHO 0\n";
        fflush(STDOUT);
        @fclose($client);

        continue;
    }
    @fwrite($client, $payload);
    echo 'P_CASE'.$case.'_ECHO '.strlen($payload)."\n";
    fflush(STDOUT);
    @fclose($client);
}

echo "P_EXIT\n";
fflush(STDOUT);
exit(0);
