<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Closure;
use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\ServerDhHandshake;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\RelayServer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\VaultMtprotoAnswerer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketRelayServer;
use MeRezaRezaei\Teleframe\Daemon\RelayListener;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayTicketBroker;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayVaultTerminalFactory;
use phpseclib3\Crypt\RSA;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * artisan teleframe:relay — the obfuscated2 relay terminal daemon.
 *
 * Reads config('teleframe.relay'): every entry in `accounts` runs one
 * RelayListener whose upstream is the account's real Telegram DC
 * (dc{id}.telegram.org:443, overridable per account via `upstream_host` /
 * globally via `upstream_host` + `upstream_port`). The listeners only serve
 * obfuscated2 clients that can mint a relay ticket (vault scope) — the
 * tgweb getDC() patch points patched web clients here.
 *
 * The client leg is a raw TCP RelayServer by default (native clients) or a
 * WebSocketRelayServer with `--socket=websocket` (browser clients — the
 * tgweb client dials wss://…/apiws, RFC6455 framing around the same
 * obfuscated2 stream).
 *
 * Vault /apiws terminal: when config('teleframe.relay.vault_terminal.enabled')
 * is true, each websocket listener carries a VaultMtprotoAnswerer over the
 * server's answerer seam — the browser seats the relay's own DH handshake
 * (relay-local RSA key from vault_terminal.rsa_key, 2048 bits minimum —
 * W-08) and every RPC is dispatched against the ACTIVE vault account scope
 * (RelayVaultTerminalFactory::dispatch, RelayActiveAccount generation)
 * instead of byte-forwarding to Telegram. auth.exportLoginToken is
 * AUTHORITATIVE (answers loginTokenSuccess immediately, so the browser's
 * first auth poll skips the stock QR screen — the ticket already granted
 * the account at the WS upgrade). The raw-TCP leg always stays the byte
 * proxy; the vault terminal is a browser (/apiws) path.
 *
 * Auth: the RFC6455 upgrade authenticates nobody, so the vault terminal
 * REQUIRES a one-shot relay ticket (minted by POST accounts/{id}/ticket)
 * presented during the WebSocket upgrade (Authorization Bearer or
 * ?ticket=) — the ticket is redeemed once at the socket and binds that
 * socket to the granted session (W-02). The raw-TCP leg is open-request
 * only when it stays loopback-bound: binding a non-loopback host demands
 * TF_RELAY_TCP_SECRET, and a configured secret gates every connection with
 * a pre-header HMAC token (W-10).
 *
 * SIGINT/SIGTERM stop the listeners gracefully when pcntl is available.
 * `--once` runs a single bounded accept pass per listener and exits.
 */
class RelayCommand extends Command
{
    protected $signature = 'teleframe:relay
        {--once : Run one bounded accept pass per listener and exit}
        {--host= : Override the relay listen host}
        {--socket=tcp : Client-leg socket type: tcp (native obfuscated2) or websocket (wss://…/apiws browser)}
        {--account= : Restrict to a single account config (account_id)}';

    protected $description = 'Obfuscated2 relay daemon: per-account listeners, upstream = real DC (TF_RELAY_TCP_SECRET gates non-loopback tcp binds; websocket vault terminal takes a relay ticket)';

    /** @var Closure(string, int, callable(int, string): (resource|null), string, callable|null, int|null, callable|null, string|null, ?string): (RelayServer|WebSocketRelayServer) */
    private $serverFactory;

    /** @var Closure(): RelayVaultTerminalFactory */
    private $vaultFactoryResolver;

    private ?LoggerInterface $logger;

    public function __construct(
        ?callable $serverFactory = null,
        ?LoggerInterface $logger = null,
        ?callable $vaultFactoryResolver = null,
    ) {
        parent::__construct();

        $this->serverFactory = $serverFactory ?? static fn (string $host, int $port, callable $resolver, string $socket, ?callable $answerer = null, ?int $defaultDcId = null, ?callable $ticketValidator = null, ?string $tcpSecret = null, ?string $sponsorChannel = null): RelayServer|WebSocketRelayServer => $socket === 'websocket'
                    ? new WebSocketRelayServer($host, $port, $resolver, $defaultDcId, $answerer, $ticketValidator, $sponsorChannel)
                    : new RelayServer($host, $port, $resolver, $defaultDcId, $tcpSecret, $sponsorChannel);
        $this->vaultFactoryResolver = $vaultFactoryResolver ?? static fn (): RelayVaultTerminalFactory => app(RelayVaultTerminalFactory::class);
        $this->logger = $logger;
    }

    public function handle(): int
    {
        $config = config('teleframe.relay', []);

        if (! ($config['enabled'] ?? false)) {
            $this->components->error('Relay is disabled. Set config("teleframe.relay.enabled") to true (TELEGRAM_RELAY_ENABLED=true).');

            return self::FAILURE;
        }

        $accounts = $config['accounts'] ?? [];
        if ($accounts === [] || ! is_array($accounts)) {
            $this->components->error('No relay accounts configured. Add entries to config("teleframe.relay.accounts").');
            $this->line("Example: ['accounts' => [['account_id' => 123456, 'dc_id' => 2, 'port' => 8443]]]");

            return self::FAILURE;
        }

        $optionAccount = $this->option('account');
        if ($optionAccount !== null) {
            $accounts = array_values(array_filter(
                $accounts,
                static fn (array $account): bool => (string) ($account['account_id'] ?? '') === (string) $optionAccount,
            ));
            if ($accounts === []) {
                $this->components->error("No relay account '{$optionAccount}' configured.");

                return self::FAILURE;
            }
        }

        $host = (string) $this->option('host') ?: (string) ($config['listen_host'] ?? '127.0.0.1');
        $defaultUpstreamHost = $config['upstream_host'] ?? null;
        $defaultUpstreamPort = (int) ($config['upstream_port'] ?? 443);
        $portBase = (int) ($config['port_base'] ?? 0);

        // W-10: the raw-TCP leg gates every connection with a pre-header HMAC
        // token when a shared secret is configured, and an explicit
        // non-loopback bind WITHOUT one would be an anonymous open proxy —
        // refuse it before creating any listener. (RelayServer::listen()
        // enforces the same rule defensively.)
        $tcpSecret = trim((string) ($config['tcp_secret'] ?? (string) env('TF_RELAY_TCP_SECRET', ''))) ?: null;
        if ((string) $this->option('socket') === 'tcp'
            && $tcpSecret === null
            && ! RelayServer::isLoopbackHost($host)) {
            $this->components->error("Relay refused: non-loopback host {$host} requires a TCP accept secret (TF_RELAY_TCP_SECRET / teleframe.relay.tcp_secret).");

            return self::FAILURE;
        }

        // Vault /apiws terminal: when enabled, the websocket listeners hand
        // each parsed browser client to a VaultMtprotoAnswerer instead of
        // dialing Telegram — the relay's own DH handshake (RSA key from
        // vault_terminal.rsa_key) seats the browser, and every RPC dispatches
        // against the ACTIVE vault account scope. A missing/invalid key is
        // surfaced BEFORE any listen socket binds.
        //
        // W-08: the CA 521-bit test roundtrip is rejected here — the terminal
        // key must be at least 2048 bits (ServerDhHandshake enforces the same
        // floor at every handshake).
        //
        // Host apps that published an older teleframe config (no
        // vault_terminal block) still opt in through the TF_RELAY_VAULT_TERMINAL
        // / TF_RELAY_RSA_KEY envs; the key value is the PEM itself or a path
        // to a PEM file.
        $configVault = (array) ($config['vault_terminal'] ?? []);
        $vaultEnabled = (bool) ($configVault['enabled'] ?? (bool) env('TF_RELAY_VAULT_TERMINAL', false));
        $answerer = null;
        $ticketValidator = null;
        if ($vaultEnabled && (string) $this->option('socket') === 'websocket') {
            try {
                $rsaKeyValue = (string) ($configVault['rsa_key'] ?? (string) env('TF_RELAY_RSA_KEY', ''));
                if ($rsaKeyValue !== '' && is_file($rsaKeyValue)) {
                    $rsaKeyValue = (string) file_get_contents($rsaKeyValue);
                }
                $rsa = trim($rsaKeyValue) === '' ? null : RSA::load($rsaKeyValue);
                if (! ($rsa instanceof RSA)) {
                    $this->components->error('Vault terminal failed to start: teleframe.relay.vault_terminal.rsa_key is unset or not a valid RSA private key');

                    return self::FAILURE;
                }
                if ($rsa->getLength() < ServerDhHandshake::MIN_RELAY_RSA_BITS) {
                    $this->components->error("Vault terminal failed to start: relay RSA private key is only {$rsa->getLength()} bits; at least ".ServerDhHandshake::MIN_RELAY_RSA_BITS.' bits required');

                    return self::FAILURE;
                }
                // The vault factory is the single owner of the ACTIVE-account
                // + scope machine: dispatch() seats the vault terminal on the
                // current scope.
                $vaultFactory = ($this->vaultFactoryResolver)();
                $answerer = new VaultMtprotoAnswerer(
                    $vaultFactory->dispatch(),
                    $rsaKeyValue,
                    $this->logger,
                );
                // W-02: the /apiws upgrade authenticates nobody, so the vault
                // terminal demands a one-shot relay ticket at the socket —
                // redeemed once, then the socket is bound to the granted
                // account (and the minting Laravel user) for its lifetime.
                //
                // C5: the ticket is ALSO the read-only source. The console
                // daemon has no HTTP request frame, so the vault terminal's
                // ReadOnlyScope is never armed here — without this the guard
                // was a permanent pass-through in the one process that
                // enforces it. redeemGrant returns the identity PLUS the
                // ticket's read-only intent; the intent is recorded against
                // the per-connection identity in the terminal's
                // RelayReadOnlyGrants registry, and the relay then receives
                // the historical {account_id, user_id} grant. A ticket whose
                // intent cannot be determined registers as READ-ONLY.
                $grants = $vaultFactory->readOnlyGrants();
                $ticketValidator = static function (string $ticket) use ($grants): ?array {
                    $grant = app(RelayTicketBroker::class)->redeemGrant($ticket);
                    if ($grant === null) {
                        return null;
                    }

                    $grants->remember(
                        $grant['account_id'],
                        $grant['user_id'],
                        $grant['read_only'] ?? true,
                    );

                    return ['account_id' => $grant['account_id'], 'user_id' => $grant['user_id']];
                };
            } catch (Throwable $e) {
                $this->components->error("Vault terminal failed to start: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        // Sponsor channel for MTProto proxy promotion
        $sponsorChannel = (string) ($config['sponsor_channel'] ?? (string) env('TF_RELAY_SPONSOR_CHANNEL', '')) ?: null;

        $listeners = [];
        foreach (array_values($accounts) as $index => $account) {
            $dcId = (int) ($account['dc_id'] ?? 1);
            $port = (int) ($account['port'] ?? $portBase + $index);
            $upstreamHost = $account['upstream_host'] ?? $defaultUpstreamHost;
            $upstreamPort = (int) ($account['upstream_port'] ?? $defaultUpstreamPort);
            $upstreamDisplay = $upstreamHost ?? "dc{$dcId}.telegram.org";

            $listeners[] = new RelayListener(
                ($this->serverFactory)($host, $port, $this->upstreamResolver($dcId, $upstreamHost, $upstreamPort), (string) $this->option('socket'), $answerer, $dcId, $ticketValidator, $tcpSecret, $sponsorChannel),
                $this->logger,
            );

            $this->components->twoColumnDetail(
                sprintf('account %s', (string) ($account['account_id'] ?? $index)),
                sprintf('dc%d %s listener %s:%d → upstream %s:%d', $dcId, (string) $this->option('socket'), $host, $port, $upstreamDisplay, $upstreamPort),
            );
        }

        // Surface bind failures before entering the loop.
        foreach ($listeners as $listener) {
            try {
                $listener->listenSocket();
            } catch (Throwable $e) {
                $this->components->error("Relay listener failed to bind: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        if ($this->option('once')) {
            foreach ($listeners as $listener) {
                $listener->runPass();
            }

            return self::SUCCESS;
        }

        $stop = function () use (&$listeners): void {
            foreach ($listeners as $listener) {
                $listener->stop();
            }
        };

        if (\function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            @pcntl_signal(SIGINT, $stop);
            @pcntl_signal(SIGTERM, $stop);
        }

        $this->components->info('Relay terminal listening. Ctrl+C to stop.');
        while (true) {
            $alive = false;
            foreach ($listeners as $listener) {
                $alive = $listener->runPass() || $alive;
            }
            if (! $alive) {
                break;
            }
        }

        $this->components->info('Relay terminal stopped.');

        return self::SUCCESS;
    }

    /** Pins a listener to its account's DC; upstream host/port overridable for loopback tests. */
    private function upstreamResolver(int $accountDcId, ?string $hostOverride, int $portOverride): Closure
    {
        $host = $hostOverride ?? "dc{$accountDcId}.telegram.org";

        return static function (int $dcId, string $protocol) use ($host, $portOverride) {
            $socket = @stream_socket_client("tcp://{$host}:{$portOverride}", $errno, $errstr, 3.0);
            if ($socket === false) {
                return null;
            }
            // The relay terminates the client's obfuscated2 transport and
            // forwards plain (decrypted) framing upstream; the upstream peer
            // (a real DC or the engine's plain dial) expects the abridged
            // init byte before the first frame (FrameCodec::writeInit parity).
            @fwrite($socket, "\xef");

            return $socket;
        };
    }
}
