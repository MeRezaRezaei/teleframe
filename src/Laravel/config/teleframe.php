<?php

declare(strict_types=1);

use MeRezaRezaei\Teleframe\Backup\Pruner;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;

/*
 * teleframe configuration.
 *
 * Merged from teleclient (archived after Phase 2 module merge):
 *
 * - schema_sources (string|null): directory holding the .tl layer sources
 *   the mirror regenerates from. Defaults to this package's committed
 *   copies of the owner's full v227 mirror (schema/sources/
 *   TL_telegram_v227.tl + TL_mtproto_v1.tl + TL_secret.tl, MIT). Set this
 *   only to pin a different checkout.
 * - ship_namespaces (list<string>): curated migration dial — TL namespaces
 *   whose per-type migrations are copied into src/Laravel/Migrations/ inside
 *   the package by `php bin/regenerate --ship` (the provider publishes them
 *   via loadMigrationsFrom). The full layer mirror always stays in src/Schema/Generated/.
 *   Comma-separated env override, e.g.
 *   TELEFRAME_SHIP_NAMESPACES="auth,messages,users".
 * - bus: bus.connection is the illuminate/redis connection name the bus
 *   reads/writes through, and it is the ONLY key here with a reader.
 *   bus.stream / bus.group / bus.reload_channel / bus.redis_client are
 *   INERT — the bus core uses Bus\StreamSchema constants directly
 *   (dead-config audit 2026-09-27). Host apps override the driver by
 *   configuring their own redis service, never through this file.
 * - daemon: the accounts the multi-account supervisor drives. Each entry is
 *   an AccountWorker config — {account_id (positive int, REQUIRED),
 *   session_string (a credential: keep it in env/secrets, never committed),
 *   dc?, api_id?, api_hash?}.
 * - backfill: defaults for teleframe:backfill when the CLI option is
 *   omitted/empty.
 * - backup: encrypted channel vaults. 'driver' picks the vault transport
 *   for teleframe:backup — 'memory' (default; offline) or 'telegram'
 *   (real private-channel vault via daemon.accounts). 'account' is the
 *   daemon.accounts account_id the telegram driver resolves its session
 *   through. 'chunk_size' is the content-addressed chunk split, in BYTES
 *   (4194304 = 4 MiB; a dedup granularity, NOT a Telegram document cap —
 *   the document cap belongs to teleframe.tstore / ChunkPlanner: 1.953125
 *   GiB free / 3.90625 GiB premium).
 *   Each set in 'sets' lists 'paths' (files/directories, walked
 *   recursively) and substring 'excludes'.
 */

$shipEnv = env('TELEFRAME_SHIP_NAMESPACES');

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Credentials (for Mini App HMAC authentication & Bot API)
    |--------------------------------------------------------------------------
    */
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

    /*
     * Refuse the webhook outright when no secret is configured.
     *
     * The controller already compares the secret correctly, in constant time,
     * and before acting on the payload. The gap was what happens with NO
     * secret at all: the comparison was skipped, so the endpoint became an
     * unauthenticated write into the host's update pipeline. Anyone who
     * learned the URL could POST a fabricated `update_id` and have it
     * dispatched into handlers, the mirror, a Centrifugo publish and a TStore
     * write — with no signal that anything was forged.
     *
     * Default TRUE, so the safe posture is the default one. Set it to false
     * ONLY when something in front of the webhook already authenticates the
     * caller (a reverse-proxy IP allowlist, a private network, mTLS). With it
     * off the endpoint is still unauthenticated; the controller logs a loud
     * error on every request so the choice is never invisible.
     */
    'webhook_require_secret' => (bool) env('TELEFRAME_WEBHOOK_REQUIRE_SECRET', true),

    /*
    |--------------------------------------------------------------------------
    | Default MTProto Client Settings (from https://my.telegram.org)
    |--------------------------------------------------------------------------
    */
    'api_id' => env('TELEGRAM_API_ID'),
    'api_hash' => env('TELEGRAM_API_HASH'),
    'dc_id' => (int) env('TELEGRAM_DC_ID', 2),
    'live_mode' => env('TELEFRAME_LIVE', false),

    /*
    |--------------------------------------------------------------------------
    | Primary account (identity + account defaulting)
    |--------------------------------------------------------------------------
    | The account_id used when no explicit account is named (IdentityConfig,
    | AccountContext). Null = "first account" fallback.
    */
    'primary_account_id' => env('TELEFRAME_PRIMARY_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Identity (Phase 5b — telegram identity guards + channels)
    |--------------------------------------------------------------------------
    | - default_sender: Bot API token used when a Telegram channel/notification
    |   names no sender (TELEFRAME_IDENTITY_DEFAULT_SENDER).
    | - miniapp_auth_max_age: Telegram Mini App initData freshness window in
    |   seconds (default 1800 = 30 min session lifetime); auth_date absent or
    |   older than the window => the tg-webapp guard rejects the replay.
    */
    'identity' => [
        'default_sender' => env('TELEFRAME_IDENTITY_DEFAULT_SENDER'),
        'miniapp_auth_max_age' => (int) env('TELEFRAME_MINIAPP_AUTH_MAX_AGE', 1800),
    ],

    /*
    |--------------------------------------------------------------------------
    | BotMap (named bot registry — Phase 5f)
    |--------------------------------------------------------------------------
    | HMAC secret guarding the BotMap HTTP surface
    | (teleframe.botmap.secret / TELEFRAME_BOTMAP_SECRET).
    */
    'botmap' => [
        'secret' => env('TELEFRAME_BOTMAP_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Handler deduplication (cross-session, multi-account)
    |--------------------------------------------------------------------------
    | - ttl: deduplication window in seconds for the cross-session store.
    | - scope: 'account' (same Telegram account across sessions) or
    |   'bot-assistant' (bot assistants mirroring a user account).
    */
    'deduplication' => [
        'ttl' => (int) env('TELEFRAME_DEDUP_TTL', 60),
        'scope' => env('TELEFRAME_DEDUP_SCOPE', 'account'),
        'prefix' => env('TELEFRAME_DEDUP_PREFIX', 'tg:bus:dedup'),
    ],
    'handler' => [
        'dedup_ttl' => (int) env('TELEFRAME_DEDUP_TTL', 60),
        'dedup_scope' => env('TELEFRAME_DEDUP_SCOPE', 'account'),
        'deduplication_ttl' => (int) env('TELEFRAME_DEDUP_TTL', 60),
        'deduplication_scope' => env('TELEFRAME_DEDUP_SCOPE', 'account'),
        'deduplication' => [
            'ttl' => (int) env('TELEFRAME_DEDUP_TTL', 60),
            'scope' => env('TELEFRAME_DEDUP_SCOPE', 'account'),
            'prefix' => env('TELEFRAME_DEDUP_PREFIX', 'tg:bus:dedup'),
        ],
    ],
    'multi_session' => [
        'dedup_ttl' => (int) env('TELEFRAME_DEDUP_TTL', 60),
        'dedup_scope' => env('TELEFRAME_DEDUP_SCOPE', 'account'),
        'deduplication_ttl' => (int) env('TELEFRAME_DEDUP_TTL', 60),
        'deduplication_scope' => env('TELEFRAME_DEDUP_SCOPE', 'account'),
    ],

    /*
    |--------------------------------------------------------------------------
    | BotMap (named bot registry — Phase 5f)
    |--------------------------------------------------------------------------
    */
    'user_session' => env('TELEGRAM_USER_SESSION'),
    'bot_session' => env('TELEGRAM_BOT_SESSION'),

    /*
    |--------------------------------------------------------------------------
    | Credential Vault (DB-encrypted apps + accounts)
    |--------------------------------------------------------------------------
    | Only the DEFAULT account label lives in env
    | (TELEFRAME_DEFAULT_ACCOUNT_ID); every secret lives in the
    | telegram_apps / telegram_accounts tables with `encrypted` casts.
    */
    'vault' => [
        'default_account' => env('TELEFRAME_DEFAULT_ACCOUNT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Realtime (Centrifugo projection — Phase 4)
    |--------------------------------------------------------------------------
    | url = Centrifugo HTTP API endpoint (e.g. http://localhost:8000/api).
    | apikey = API key for the publish call; secret = signing secret (only
    | needed if Centrifugo is configured to verify JWT signatures on
    | publishes). Leave url empty to keep the projection silent (the
    | UpdateStored → Centrifugo listener is then not registered).
    */
    'realtime' => [
        'url' => env('TELEFRAME_CENTRIFUGO_URL', ''),
        'apikey' => env('TELEFRAME_CENTRIFUGO_APIKEY', ''),
        'secret' => env('TELEFRAME_CENTRIFUGO_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging (PSR-3 seam)
    |--------------------------------------------------------------------------
    | FQCN of a PSR-3 logger class (default '' → Psr\Log\NullLogger, i.e. the
    | engine is SILENT unless you opt in). When you publish this config and
    | set e.g. TELEFRAME_LOGGER=Monolog\Logger::class, the class is
    | instantiated with `new` and used everywhere the engine logs. A binding
    | of Psr\Log\LoggerInterface you register in your own service provider
    | always takes precedence over this value.
    */
    'logging' => [
        'logger' => env('TELEFRAME_LOGGER', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard (Horizon-style web UI, opt-in via Teleframe::routes())
    |--------------------------------------------------------------------------
    | Register the package-owned dashboard routes from your app's service
    | provider:
    |
    |     use MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe;
    |     Teleframe::routes(); // GET /teleframe + JSON API under it
    |
    | - prefix: route prefix for the dashboard index + JSON API group.
    | - middleware: guard protecting the whole group. Default ['web', 'auth']
    |   = the HOST's own login protects the dashboard (auth is host business,
    |   same as Laravel Horizon). Token-based hosts may pass
    |   Teleframe::routes(middleware: ['auth:sanctum']).
    */
    'dashboard' => [
        'prefix' => env('TELEFRAME_DASHBOARD_PREFIX', 'teleframe'),
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Web Client (embedded official client — Phase 1)
    |--------------------------------------------------------------------------
    | Serves the rebuilt official Telegram Web client (telegram-tt / Web A)
    | from the package's built static bundle (tgweb/dist) under a single
    | route, registered from your app's provider:
    |
    |     Route::telegramWeb();            // GET /tg/{path?} (SPA fallback)
    |     Route::telegramWeb('web/tg');    // custom prefix
    |
    | - enabled: off by default; the route 404s until the bundle exists and
    |   this flag is on (TELEGRAM_WEB_ENABLED=true).
    | - dist_path: override the default package tgweb/dist location.
    |   (api_id / api_hash for the bundle come from the top-level TELEGRAM_API_ID
    |   / TELEGRAM_API_HASH at tgweb build time; the route prefix is set via
    |   Route::telegramWeb('web/tg').)
    |
    | Callers may attach their own middleware (web, auth, tg.miniapp, ...):
    |
    |     Route::telegramWeb()->middleware(['web', 'auth']);
    */
    'telegram_web' => [
        'enabled' => env('TELEGRAM_WEB_ENABLED', false),
        'dist_path' => env('TELEGRAM_WEB_DIST'),
        // Control layer ("sit in the middle"): where a patched web client
        // connects to OUR relay instead of Telegram's DCs. Empty = not yet
        // provisioned; the accounts API then omits the live_endpoint field.
        'live_endpoint' => env('TELEGRAM_WEB_LIVE_ENDPOINT'),
        // Lifetime of a minted per-account relay ticket (single-use).
        'ticket_ttl_minutes' => (int) env('TELEGRAM_RELAY_TICKET_TTL_MINUTES', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Relay daemon (obfuscated2 transport terminal — `teleframe:relay`)
    |--------------------------------------------------------------------------
    |
    | One RelayListener per `accounts` entry. Each listener serves obfuscated2
    | clients (the patched tgweb getDC() choke point) and terminates them at
    | the account's real Telegram DC: dc{dc_id}.telegram.org:443 unless
    | `upstream_host` / `upstream_port` override it (loopback testing).
    |
    | 'port_base': base port for listeners without their own `port`;
    |             0 = ephemeral (each listener binds a free port).
    */
    'relay' => [
        'enabled' => env('TELEGRAM_RELAY_ENABLED', false),
        'listen_host' => env('TELEGRAM_RELAY_LISTEN_HOST', '127.0.0.1'),
        'port_base' => (int) env('TELEGRAM_RELAY_PORT_BASE', 0),
        'upstream_host' => env('TELEGRAM_RELAY_UPSTREAM'),
        'upstream_port' => (int) env('TELEGRAM_RELAY_UPSTREAM_PORT', 443),
        // ['account_id' => int, 'dc_id' => int, 'port' => int?, 'upstream_host' => string?, 'upstream_port' => int?]
        'accounts' => [],
        // Vault /apiws terminal: instead of byte-forwarding the browser to
        // Telegram, play the relay's own DH handshake and dispatch every RPC
        // against the ACTIVE vault account scope (VaultMtprotoAnswerer).
        // 'rsa_key' is the relay-local PEM private key whose {fingerprint,
        // n, e} the patched tgweb build registers as a trusted SERVER_KEY.
        'vault_terminal' => [
            'enabled' => (bool) env('TF_RELAY_VAULT_TERMINAL', false),
            'rsa_key' => env('TF_RELAY_RSA_KEY', ''),
            // Host policy plane (container binding, not an env key): bind
            // `teleframe.relay.dispatch-middleware` to a LIST of callables
            // `(string $method, array $params, RelayDispatchContext $ctx,
            // callable $next): array` and the terminal dispatch runs them as
            // an onion around the vault terminal. $ctx carries the identity
            // the WS ticket granted at the socket — the vault account_id plus
            // the Laravel user id captured when the mint route ran
            // (RelayTicketController binds Auth::id() into the ticket) —
            // plus the shared ReadOnlyScope. Each middleware decides per RPC
            // or delegates with $next($method, $params). Unbound (the
            // default) keeps the historical ReadOnlyVaultDispatch terminal
            // byte-for-byte.
        ],

        /*
         * Read-only enforcement for the vault terminal (finding C5).
         *
         * The vault terminal refuses every state-changing MTProto RPC that is
         * not on the READ-ONLY allow-list (ReadOnlyVaultDispatch) when the
         * connection serving it holds a READ-ONLY grant. The grant is bound
         * into the one-shot relay TICKET at mint time and recorded per
         * connection when the socket redeems it — NOT by a per-request HTTP
         * scope frame, which never runs inside the `teleframe:relay` console
         * daemon. Every value below defaults to the ENFORCING, deny-by-default
         * answer: a grant that cannot be determined is read-only, so the guard
         * never decides "allow" by accident.
         */
        'read_only' => [
            // Arm the grant-derived guard at all. true = a read-only ticket is
            // enforced; false = the terminal stops consulting the per-connection
            // grant (UNSAFE — the guard becomes a pass-through, which is the
            // state this finding was written about).
            'enforce' => (bool) env('TELEFRAME_RELAY_READ_ONLY_ENFORCE', true),

            // A ticket minted from a read-only session (a Laravel user session
            // on the dashboard stack, which the tg.readonly middleware arms) is
            // a READ-ONLY ticket. true = the session-backed dashboard client
            // may read the account but never write to it. false = let a
            // session-backed user drive the account in full (UNSAFE unless the
            // host has its own authorization floor in front of the mint route).
            'session_minted_tickets' => (bool) env('TELEFRAME_RELAY_READ_ONLY_SESSION_TICKETS', true),

            // What a grant the daemon cannot determine (no recorded grant, a
            // grant older than its retention window, a malformed identity)
            // answers. 'readonly' = deny by default; 'allow' = fail OPEN and
            // lose the guard for that RPC (UNSAFE).
            'undetermined_grant' => (string) env('TELEFRAME_RELAY_READ_ONLY_UNDETERMINED', 'readonly'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Proxy Configuration (MTProto + Bot API + WEB)
    |--------------------------------------------------------------------------
    | Default proxy type: socks5, http, mtproto, web
    | WEB proxy uses the tdesktop-web-proxy-bridge-v1 protocol (Telegram Desktop 7.1+)
    | - carrier: https, https-lanes, websocket, websocket-lanes, auto
    | - bridge_derivation: frozen v1 label (tdesktop-web-proxy-bridge-v1)
    | - max_frame_size: 1 MiB default
    | - chunk_size: 64 KiB default
    | - initial_window: 4 MiB default
    */
    'proxy' => [
        'default' => env('TELEFRAME_PROXY_TYPE', 'mtproto'),
        'web' => [
            'carrier' => env('TELEFRAME_WEB_CARRIER', 'auto'),
            'bridge_derivation' => 'tdesktop-web-proxy-bridge-v1',
            'max_frame_size' => 1024 * 1024,
            'chunk_size' => 64 * 1024,
            'initial_window' => 4 * 1024 * 1024,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WEB Proxy Server (tdesktop-web-proxy-bridge-v1 server-side)
    |--------------------------------------------------------------------------
    | Runs a WEB proxy server that clients can connect to (inbound).
    | - enabled: set true to enable the server
    | - listen_host: bind host (default 127.0.0.1)
    | - port: bind port (default 443)
    | - carrier: https, https-lanes, websocket, websocket-lanes, auto
    | - sponsor_channel: optional sponsor channel username (e.g., '@mychannel')
    | - tls_cert_file: path to TLS certificate for HTTPS/WSS
    | - tls_key_file: path to TLS private key for HTTPS/WSS
    */
    'web_proxy_server' => [
        'enabled' => env('TELEFRAME_WEB_PROXY_SERVER_ENABLED', false),
        'listen_host' => env('TELEFRAME_WEB_PROXY_SERVER_HOST', '127.0.0.1'),
        'port' => (int) env('TELEFRAME_WEB_PROXY_SERVER_PORT', 8443),
        'carrier' => env('TELEFRAME_WEB_PROXY_SERVER_CARRIER', 'auto'),
        'sponsor_channel' => env('TELEFRAME_WEB_PROXY_SERVER_SPONSOR_CHANNEL'),
        'tls_cert_file' => env('TELEFRAME_WEB_PROXY_SERVER_TLS_CERT'),
        'tls_key_file' => env('TELEFRAME_WEB_PROXY_SERVER_TLS_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Mirror (regeneration sources + curated migration dial)
    |--------------------------------------------------------------------------
    */
    'schema_sources' => env('TELEFRAME_SCHEMA_SOURCES'),

    'ship_namespaces' => $shipEnv === null
        ? SchemaRegenerator::DEFAULT_SHIP_NAMESPACES
        : array_values(array_filter(array_map('trim', explode(',', $shipEnv)))),

    /*
    |--------------------------------------------------------------------------
    | Redis Bus (update stream / hot-reload channel / route table)
    |--------------------------------------------------------------------------
    | ONLY `connection` is read (TeleframeServiceProvider.php:222 and
    | SupervisorServiceProvider.php:90).
    |
    | stream / group / reload_channel / redis_client are INERT (dead-config
    | audit 2026-09-27: declared, documented, READ BY NOTHING). The bus core
    | is config-free by design and uses the constants directly —
    | MeRezaRezaei\Teleframe\Bus\StreamSchema::STREAM / ::GROUP /
    | ::RELOAD_CHANNEL appear literally across src/Teleframe/Bus/* and
    | src/Laravel/Console/DaemonCommand.php. redis_client is read nowhere at
    | all: the Redis driver is the host's own redis service, not this key.
    |
    | They are kept as the documented record of the wire names — per the
    | repo hard rule, the Redis wire keys (tg:stream:updates, group
    | teleclient, tg:bus:routes, the DL stream, the reload channel) are
    | opaque forward-compat state and must not be renamed. But do NOT expect
    | to change them here: setting TELEFRAME_REDIS or editing these rows
    | changes nothing at runtime.
    |
    | TO MAKE THEM REAL, thread the values in place of the StreamSchema
    | constants in src/Teleframe/Bus/*.php — that is a core-layer change, not
    | a config one. */
    'bus' => [
        // INERT — read by nothing; the wire names live in Bus\StreamSchema.
        'stream' => StreamSchema::STREAM,
        // INERT — read by nothing; the consumer group is Bus\StreamSchema::GROUP.
        'group' => StreamSchema::GROUP,
        // INERT — read by nothing; the reload channel is StreamSchema::RELOAD_CHANNEL.
        'reload_channel' => StreamSchema::RELOAD_CHANNEL,
        // The ONLY key in this block with a reader.
        'connection' => 'default',
        // INERT — read by nothing. The Redis driver is the HOST's own redis
        // service; setting TELEFRAME_REDIS changes no driver anywhere.
        'redis_client' => env('TELEFRAME_REDIS', 'predis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supervisor (teleframe:supervise — the package's OWN process supervisor)
    |--------------------------------------------------------------------------
    |
    | 'processes' is the set of long-running package commands the supervisor
    | keeps alive: 'daemon', 'ingest', 'tstore-work', 'relay' (the closed
    | catalog in MeRezaRezaei\Teleframe\Supervisor\ProcessCatalog). A name
    | outside the catalog is a LOUD failure, never a silent skip. The host
    | wires NOTHING: no config/horizon.php, no polling job.
    |
    | 'restart_delay_seconds' is the base of the exponential restart ladder,
    | 'max_backoff_seconds' its ceiling, 'max_restarts' the restarts allowed
    | per process (0 = unlimited). 'env' holds per-process environment
    | overrides, keyed by process name.
    */
    'supervisor' => [
        'processes' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TELEFRAME_SUPERVISOR_PROCESSES', 'daemon,ingest'))
        ))),
        'restart_delay_seconds' => (int) env('TELEFRAME_SUPERVISOR_RESTART_DELAY', 5),
        'max_backoff_seconds' => (int) env('TELEFRAME_SUPERVISOR_MAX_BACKOFF', 300),
        'max_restarts' => (int) env('TELEFRAME_SUPERVISOR_MAX_RESTARTS', 0),
        'env' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Daemon (multi-account supervisor registry)
    |--------------------------------------------------------------------------
    */
    'daemon' => [
        'accounts' => [
            // [
            //     'account_id' => 501558149,
            //     'session_string' => env('TELEGRAM_SESSION_501558149'),
            //     'dc' => 2, 'api_id' => null, 'api_hash' => null,
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backfill (history fetch defaults)
    |--------------------------------------------------------------------------
    */
    'backfill' => [
        'request_budget' => env('TELEFRAME_BACKFILL_BUDGET', 25),
        'flood_cap_seconds' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup (encrypted channel vaults)
    |--------------------------------------------------------------------------
    | 'chunk_size' is in BYTES and is the content-addressed dedup chunk for
    | teleframe:backup. The default 4194304 is 4 MiB (4 * 1024 * 1024) — the
    | nearest round number to a quarter of 16 MiB — and it is a DEDUP
    | GRANULARITY, not a Telegram document cap: a one-line edit re-uploads
    | one chunk rather than a whole file.
    |
    | It is deliberately NOT the owner's "4 gig chunks" figure
    | (verbatim 2026-09-24:40). That capability is TStore's
    | teleframe.tstore path, which chunks into real Telegram documents via
    | Store\ChunkPlanner: FREE_CAP = 512 KiB * 4000 = 2097152000 bytes =
    | 1.953125 GiB (the DEFAULT) and PREMIUM_CAP = 512 KiB * 8000 =
    | 4194304000 bytes = 3.90625 GiB (opt-in, premium: true). Neither is a
    | round GiB figure, because 512 KiB does not divide 1 GiB. It cannot
    | apply here: each backup chunk is uploaded as
    | ONE whole Telegram message, so message count equals chunk count no
    | matter what this is set to, and this path buffers each file whole
    | rather than streaming it. See BackupRunner::DEFAULT_CHUNK_SIZE.
    |
    | The only hard ceiling is Telegram's document cap, read from
    | Store\ChunkPlanner::PREMIUM_CAP (4194304000 bytes = 3.90625 GiB) at run time — never restated
    | as a literal here, so the two modules cannot drift apart.
    |
    | - driver: 'telegram' (DEFAULT) is the only DURABLE driver. 'memory' is
    |   a PROCESS-LOCAL InMemoryVault kept for offline tests: it is not a
    |   backup, nothing survives the process, and it used to be the shipped
    |   default — so a default install printed a green "backup set=..."
    |   having stored nothing at all. It is now opt-in twice over: set
    |   TELEFRAME_BACKUP_DRIVER=memory AND TELEFRAME_BACKUP_ALLOW_VOLATILE=1
    |   to acknowledge, in configuration, that a run will not persist. A
    |   backup that stores nothing must never be the DEFAULT answer.
    | - account: the daemon.accounts account_id whose channel hosts the set
    |   (required by the telegram driver; unset => run refuses loudly).
    | - passphrase: THE single secret for this vault, read from
    |   TELEFRAME_BACKUP_PASSPHRASE. It seals BOTH the chunks (the command
    |   reads it from the environment / --passphrase-file / hidden prompt)
    |   AND the manifest index (here, for the vault's AEAD key). The two used
    |   to come from DIFFERENT keys — chunks from TELEFRAME_BACKUP_PASSPHRASE,
    |   the index from TELEFRAME_TSTORE_PASSPHRASE — so `run` wrote chunks
    |   under one secret and could not read back its own index under another,
    |   and verify/restore/prune of a set you had just created could not
    |   work. This key is the source of truth. teleframe.tstore.passphrase is
    |   still ACCEPTED as an alias when this one is empty, but setting BOTH
    |   to different values is refused loudly at bind time, naming both keys.
    | - allow_volatile_vault: the explicit acknowledgement that the memory
    |   driver stores nothing. False by default.
    */
    'backup' => [
        'driver' => env('TELEFRAME_BACKUP_DRIVER', 'telegram'),
        'account' => env('TELEFRAME_BACKUP_ACCOUNT'),
        'passphrase' => env('TELEFRAME_BACKUP_PASSPHRASE', ''),
        'allow_volatile_vault' => (bool) env('TELEFRAME_BACKUP_ALLOW_VOLATILE', false),
        'chunk_size' => env('TELEFRAME_BACKUP_CHUNK_SIZE', 4194304), // bytes; 4 MiB — see the section note above
        // Hours after which `teleframe:backup status` calls the newest
        // manifest STALE and exits non-zero. This is an SLA, not a warning:
        // one missed daily cycle should be red. See
        // BackupCommand::DEFAULT_MAX_AGE_HOURS for the code-side default.
        'max_age_hours' => (int) env('TELEFRAME_BACKUP_MAX_AGE_HOURS', 24),
        // How many NEWEST generations a prune leaves RESTORABLE. Retention,
        // not display: the prune keep-set is the union of these generations'
        // chunk addresses, so every generation inside the window can still be
        // restored and everything outside it loses its restorability.
        //
        // The shipped default is 3, NOT 1. Chunks are content-addressed and
        // BackupRunner skips a chunk the previous manifest already references,
        // so an unchanged file costs ONE document in every generation —
        // retention adds only what actually CHANGED between the retained
        // runs, roughly (N-1) runs' worth of delta, not N copies of the set.
        // Against that, a corrupt or partial generation is undetectable from
        // the vault and only shows up on a restore attempt, usually a day or
        // two later. Keeping 2 leaves a single fallback, so one bad newest
        // generation spends the entire safety net at once; keeping 3 leaves
        // two independent fallbacks, which is the minimum for this to be a
        // safety net rather than a coin flip. Minimum is 1 — a lower value is
        // raised, never honoured as 0, because "keep zero" means deleting
        // every chunk in the set.
        //
        // Overridden per-run by `teleframe:backup prune --keep-generations=N`.
        // See Pruner::RECOMMENDED_KEEP_GENERATIONS for the same reasoning at
        // the code site, so the two cannot drift apart silently.
        'keep_generations' => (int) env('TELEFRAME_BACKUP_KEEP_GENERATIONS', Pruner::RECOMMENDED_KEEP_GENERATIONS),
        // OPT-IN, off by default. When true the package registers its own
        // `teleframe:backup run --set=default` schedule (dailyAt 03:17,
        // withoutOverlapping, onOneServer) so "backups stopped running" is
        // caught by the staleness check above even on a host that never wired
        // cron by hand. Default-on would be a red cron on every host: an
        // unset sets.default.paths makes `run` refuse, so a default-on schedule
        // would fail loudly forever on a package that is installed but unused.
        'scheduled' => (bool) env('TELEFRAME_BACKUP_SCHEDULED', false),
        'sets' => [
            'default' => [
                'paths' => [
                    // Nothing is backed up until this is filled in, and
                    // `teleframe:backup run` says exactly that (naming
                    // teleframe.backup.sets.default.paths) rather than
                    // throwing a bare "setConfig must list at least one
                    // path" from deep inside the runner. A sensible host
                    // pattern: base_path('...') entries go here.
                ],
                'excludes' => [],
                // premium: read by BackupRunner::premiumFlag() off this very
                // row, and it selects which per-document cap
                // assertChunkSize() enforces. Declared NULL-on-purpose:
                // premiumFlag() returns false for null and for false alike,
                // so this default lands on the same path it took before the
                // key was declared here.
                'premium' => (bool) env('TELEFRAME_BACKUP_SETS_DEFAULT_PREMIUM', false),
                // peer_type / peer_id: read by the
                // teleclient.backup.vault-factory bind in
                // TeleframeServiceProvider (see the telegram branch — the row
                // teleframe.backup.sets.<setId> is fetched, these two leaves
                // are pulled off it and handed to TelegramVault::forScope()
                // as its peerType/peerId). The destination belongs to the SET,
                // not to the account, which is why one process can back a free
                // account to a broadcast channel and a premium account to a
                // group.
                //
                // Both declared NULL on purpose, NOT defaulted to a value:
                // the bind writes `$setRow['peer_type'] ?? PEER_CHANNEL` and
                // `$setRow['peer_id'] ?? null`, so a null here lands exactly
                // on TelegramVault's own defaults — PEER_CHANNEL, peerId
                // null, find-or-create by title — which is the path this line
                // took before the keys existed. Baking PEER_CHANNEL in would
                // make that fallback unreachable and turn an unset row into a
                // hard bind.
                'peer_type' => null,
                'peer_id' => null,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | TStore (Telegram-backed encrypted archive — S1–S11)
    |--------------------------------------------------------------------------
    | - account_id: the MTProto account whose dialogs host the store buckets
    |   (teleframe-store:* channels). Required for put/get to resolve a
    |   channel factory scope; '' = unconfigured (facade calls throw).
    | - passphrase: default object passphrase ('' = raw by default).
    | - cache: 'memory' (default; in-heap TTL+LRU) or 'minio' (path-style
    |   127.0.0.1:9010, graceful when down — reads fall through to the wire).
    | - bucket_prefix: channel title prefix used for classification.
    | - premium: chunk-cap switch. FALSE by default, so a default install
    |   chunks at FREE_CAP = 2097152000 bytes (1.953125 GiB); set
    |   TELEFRAME_TSTORE_PREMIUM=1 only when the account_id's account really
    |   is premium, which raises the cap to PREMIUM_CAP = 4194304000 bytes
    |   (3.90625 GiB). See ChunkPlanner for the exact byte math.
    | - peer_target: routes a bucket to a configured channel/group
    |   instead of the bucket's own channel. Honoured: the manifest
    |   (TFSTORE1), the registry (TFREGISTRY1) and every chunk document are
    |   written to THAT peer, so a restore finds all of them together
    |   (reads fall back to the bucket's own channel for objects written
    |   before a target was configured). The account is joined to the peer
    |   and its post rights probed BEFORE the first upload, so an unusable
    |   destination is refused up front rather than failing mid-upload.
    |   Supported keys: bucket, peer_id, access_hash, peer_type
    |   (channel|group), mode (raw|sealed, default sealed), passphrase,
    |   members (user ids/usernames to invite). Leave null (the default)
    |   to store to the bucket's own channel. A target with no access_hash,
    |   or one the account cannot post in, is REFUSED loudly — as is a
    |   group in raw mode, which must be sealed. Set
    |   TELEFRAME_TSTORE_PEER_TARGET to a JSON object for this.
    | - queue: 'sync' (inline) or 'amqp' (ext-amqp, durable tstore.upload);
    |   enqueue defers uploads to `teleframe:tstore work`.
    | - schedules: 'null' (the shipped default — probe only) or 'temporal'
    |   (127.0.0.1:7233, default namespace). READ as of the dead-config audit
    |   2026-09-27; it was declared, documented and read by nothing until then.
    |   See the comment on the key itself for the refusal posture.
    */
    'tstore' => [
        'account_id' => (int) env('TELEFRAME_TSTORE_ACCOUNT', 0),
        /* READ BY NOTHING ON THE `teleframe:tstore put` PATH, and that is not
         |   an accident. This is the LEGACY ALIAS for the BACKUP vault's
         |   secret (teleframe.backup.passphrase / TELEFRAME_BACKUP_PASSPHRASE),
        |   which seals the manifest INDEX. The tstore OBJECT path seals with a
        |   DIFFERENT secret, read as TELEGRAM_TSTORE_PASSPHRASE by
        |   TstoreCommand::passphrase() — env -> --passphrase-file -> prompt.
        |
        |   The two names differ by four characters and mean different things,
        |   which is a trap worth naming: an operator who sets
        |   TELEFRAME_TSTORE_PASSPHRASE expecting `tstore put` to seal gets RAW
        |   objects, silently. See resolveBackupPassphrase(), which refuses two
        |   DIFFERENT values across the backup key and this alias. */
        'passphrase' => env('TELEFRAME_TSTORE_PASSPHRASE', ''),
        'cache' => env('TELEFRAME_TSTORE_CACHE', 'memory'),
        /* S3 OBJECT STORAGE (Cycle 38 — Goal D, verbatim 2026-09-24:40 "THE S3
           IS GOOD" and 2026-09-27:628 "we must get ready for first version
           usage").

           These three were LITERALS in the provider's S3GatewayContract binding
           while only the credentials were env-driven — so a host that read the
           operator's line and pointed at real S3 received a client dialling
           LOCALHOST MinIO, failing only at first use, and a custom S3 endpoint
           was unreachable by configuration at all. The defaults below are the
           exact literals that were there before, so an unconfigured host
           behaves byte-identically.

           They drive TWO consumers, which is the point: the tstore hot cache
           (teleframe.tstore.cache=minio) AND the durable backup tier
           (teleframe.backup.driver=s3 — see S3DurableVault). The endpoint is
           blank-safe: set it to real AWS/B2/Backblaze and the same gateway
           serves both, so "the S3 is good" means what the operator said.

           LEAVE endpoint AT THE DEFAULT only for a local MinIO on 127.0.0.1.
           An empty endpoint string is NOT "use the cloud default" — it is an
           address that cannot be dialled, and it fails at first use like the
           literal did. For real S3, set the region explicitly too: us-east-1
           is MinIO's default, not AWS's. */
        's3' => [
            'endpoint' => env('TELEFRAME_TSTORE_S3_ENDPOINT', 'http://127.0.0.1:9010'),
            'region' => env('TELEFRAME_TSTORE_S3_REGION', 'us-east-1'),
            'bucket' => env('TELEFRAME_TSTORE_S3_BUCKET', 'teleframe-tstore'),
            'key' => env('TELEFRAME_TSTORE_MINIO_KEY', 'minioadmin'),
            'secret' => env('TELEFRAME_TSTORE_MINIO_SECRET', 'minioadmin'),
            // Path-style addressing. MinIO on 127.0.0.1 ignores virtual hosts,
            // and it is the shipped default's transport. Real AWS supports
            // path-style too, so a host that does NOT need virtual-host style
            // (which requires a bucket name that is a valid DNS label) can
            // leave this alone.
            'use_path_style' => (bool) env('TELEFRAME_TSTORE_S3_PATH_STYLE', true),
        ],
        'cache_ttl_seconds' => (int) env('TELEFRAME_TSTORE_CACHE_TTL', 600),
        'cache_max_bytes' => (int) env('TELEFRAME_TSTORE_CACHE_MAX_BYTES', 67108864),
        /* INERT (dead-config audit 2026-09-27: read by NOTHING). The channel
           title prefix is MeRezaRezaei\Teleframe\Store\ChannelRegistry::
           PREFIX_STORE = 'teleframe-store:' (a class constant), used by
           ChannelRegistry::prefixFor/titleFor/classify. Setting
           TELEFRAME_TSTORE_BUCKET_PREFIX changes no title and no
           classification — the registry and the writer both use the constant,
           so they stay self-consistent, but this key is a promise the code
           does not keep. Kept as the documented record of the wire prefix.
           TO WIRE IT: pass this value into ChannelRegistry instead of the
           constant — a src/Teleframe change, out of this file's hands. */
        'bucket_prefix' => env('TELEFRAME_TSTORE_BUCKET_PREFIX', 'teleframe-store:'),
        'premium' => (bool) env('TELEFRAME_TSTORE_PREMIUM', false),
        'peer_target' => env('TELEFRAME_TSTORE_PEER_TARGET'),
        'queue' => env('TELEFRAME_TSTORE_QUEUE', 'sync'),
        // AMQP creds default to RabbitMQ's stock guest/guest for local dev.
        // W-27: in non-dev environments the host app MUST validate these and
        // fail fast (clear exception) before `teleframe:tstore work` dials the
        // broker — guest/guest must never reach production. Set
        // TELEFRAME_TSTORE_AMQP_USER/PASSWORD explicitly (or use a dedicated
        // broker identity) outside local development.
        'amqp' => [
            'host' => env('TELEFRAME_TSTORE_AMQP_HOST', '127.0.0.1'),
            'port' => (int) env('TELEFRAME_TSTORE_AMQP_PORT', 5672),
            'vhost' => env('TELEFRAME_TSTORE_AMQP_VHOST', '/'),
            // Empty by default, NOT 'guest'. An empty value used to silently
            // MEAN guest/guest, so a host that configured nothing got default
            // broker credentials — and RabbitMQ's default guest account is
            // refused on any non-loopback host anyway. AmqpUploadQueue now
            // fails loudly if guest/guest is used against a remote broker.
            // For a local broker, set both explicitly.
            'user' => env('TELEFRAME_TSTORE_AMQP_USER', ''),
            'password' => env('TELEFRAME_TSTORE_AMQP_PASSWORD', ''),
            // Socket-level facts. Without them a half-open connection reports
            // itself as connected and is only discovered on the first write, at
            // which point the publish blocks for the OS TCP timeout — the
            // "RabbitMQ hung, the app is stuck" first-run experience.
            'heartbeat' => (int) env('TELEFRAME_TSTORE_AMQP_HEARTBEAT', 30),
            'connect_timeout' => (float) env('TELEFRAME_TSTORE_AMQP_CONNECT_TIMEOUT', 3.0),
            'read_timeout' => (float) env('TELEFRAME_TSTORE_AMQP_READ_TIMEOUT', 5.0),
            'write_timeout' => (float) env('TELEFRAME_TSTORE_AMQP_WRITE_TIMEOUT', 5.0),
            // Wall-clock budget for a publish to succeed or give up.
            'publish_retry_budget_seconds' => (float) env('TELEFRAME_TSTORE_AMQP_PUBLISH_BUDGET', 5.0),
        ],
        /* WIRED (dead-config audit 2026-09-27; these three keys were DEAD
           until then). The tstore schedule seam used to be hardcoded off:
           TeleframeServiceProvider.php bound teleclient.tstore.schedules to
           `new NullSchedulePublisher` in a closure that took no config at all,
           so TELEFRAME_TSTORE_SCHEDULES=temporal changed nothing and nothing
           read the DSN or the namespace.

           (This block used to add that NullSchedulePublisher's refusal message
           told the operator to set this key. It no longer does — Cycle 19
           removed that mention rather than wiring the key, which was the right
           call then. The message is in Store\Schedules\, not this slice, and
           it is left as Cycle 19 left it; what is enforced from here is the
           invariant that it never names a key nothing reads —
           tests/Config/SchedulesDriverWiringTest.php.)

           All three are now read by TeleframeServiceProvider, at RESOLVE time
           (deferred — nothing dials at boot, the smoke gate boots no
           container). Posture, identical to the two wirings Cycle 19 already
           fixed (teleframe.app.schedules and
           teleframe.store.backup.schedule.publisher):
             'null'      the shipped default → NullSchedulePublisher, and
                         install() honestly reports 'skipped'. UNCHANGED: a
                         package that starts arming recurring jobs the moment
                         it is installed is a surprise, and that ruling is in
                         the ledger.
             'temporal'  a host-bound TemporalScheduleClient WINS verbatim;
                         else TemporalClientResolver builds the package's own
                         SDK-backed client from schedules_dsn +
                         schedules_namespace; with neither it REFUSES HERE, by
                         name, naming temporal/sdk. It never degrades to
                         'null', because a host that believes its jobs recur
                         while the publisher says 'skipped' is the worst
                         available failure available.
             anything    LOUD RuntimeException at resolve time.
             else

           The task queue is the publisher's literal 'default' and is NOT a
           key here: reading an undeclared one is the defect this file's
           sibling wiring had to undo (AppServiceProvider::registerSchedules()).
           Bind your own client for a different queue. */
        'schedules' => env('TELEFRAME_TSTORE_SCHEDULES', 'null'),
        /* WIRED — read by the 'temporal' branch above and handed to the
           Temporal client as the FRONTEND address. Nothing in this package
           dials it; the resolved client does. */
        'schedules_dsn' => env('TELEFRAME_TSTORE_TEMPORAL_DSN', '127.0.0.1:7233'),
        /* WIRED — the Temporal ACCOUNTING NAMESPACE, and the resolver's own
           second argument: it becomes the namespace the SDK client is built
           with (ClientOptions::withNamespace). Default 'default', which is
           what the resolver assumes anyway; set it when your Temporal cluster
           namespaces accounts. Ignored when a host binds its own client, since
           that client already carries its namespace. */
        'schedules_namespace' => env('TELEFRAME_TSTORE_TEMPORAL_NAMESPACE', 'default'),
        // W9: optional live indexing — index media from data-channel updates
        // as they land (S3/S11). Off by default: reconciliation via the
        // `teleframe:tstore index` one-shot is always available and exact;
        // the listener is a promptness optimization for busy data channels.
        'listen' => (bool) env('TELEFRAME_TSTORE_LISTEN', false),

        /* THE RECLAIM POSTURE — what the store is allowed to do to your local
           disk. Read by TeleframeServiceProvider and handed to the
           StoreService ctor, so every reclaimLocal() call and every read path
           inherits it. Values (ReferencePolicy::fromString, which refuses
           anything else LOUDLY by name):
             retain-local               THE DEFAULT, and the point of shipping
                                        it as the default: the store never
                                        touches your filesystem. Byte-for-byte
                                        the pre-cold-copy behaviour.
             drop-local-after-index     frees a local file ONLY once a local
                                        index row is proven durable. Never
                                        before — that ordering is the class.
             cold-remote-authoritative  the COLD posture: the remote (Telegram)
                                        tier is the authority, the local index
                                        is a rebuildable cache, and a local file
                                        may be freed once the REMOTE document is
                                        proven addressable with no local row
                                        involved.

           WHY THE DEFAULT IS NOT THE COLD ONE, and this is not a bug: a
           package that starts unlinking a host's local files on install, on a
           path reachable from a facade, a command and a reclaim loop, is a
           decision no operator made. So the cold behaviour is NAMED and
           opt-in. The honest cost: out of the box the store still retains
           local files, so "delete it locally, it's still in Telegram" is not
           true until you turn it on here.

           'trust the remote' and 'delete the local copy' are SEPARATE
           predicates (ReferencePolicy::trustsRemote() / reclaims()), and this
           key sets BOTH at once only by naming one posture that means both.
           Neither is ever inferred from the other. */
        'reference_policy' => env('TELEFRAME_REFERENCE_POLICY', ReferencePolicy::RETAIN_LOCAL),
    ],
];
