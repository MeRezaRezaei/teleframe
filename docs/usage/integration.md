# Integration guide — installing Teleframe into a consuming Laravel app

> **Everything in this guide was read out of `src/` on the day it was
> written.** Commands come from the `$signature` of each command class,
> config keys from `src/Laravel/config/*.php`, methods from the class the
> facade resolves. Where a code sample is marked **not verified**, it is
> because it could not be confirmed against the shipped source — do not
> trust it until you check it yourself.
>
> Feature-level documentation lives beside this file
> ([notify.md](notify.md), [store-backup.md](store-backup.md),
> [mtproto.md](mtproto.md), [app-framework.md](app-framework.md),
> [passport.md](passport.md)). This file is about **installing the package
> into a host application**, not about what each feature does.

---

## 1. Install

```bash
composer require merezarezaei/teleframe
```

> **What a dist install actually contains.** `composer.json`'s
> `archive.exclude` drops `/docs`, `/tests`, `/examples`, `/bootstrap` and
> `/storage`. Every path this guide names under `docs/` is therefore a
> **repo working copy** path, not something you will find in
> `vendor/merezarezaei/teleframe/`. `bin/` is **not** dropped wholesale: the
> dev scripts are excluded one by one, but **`bin/teleframe-worker` ships**,
> because it is the only way to serve a Temporal tick — so
> `vendor/merezarezaei/teleframe/bin/teleframe-worker` is present in a dist
> install and you do not copy it in from a repo checkout. What ships is
> `src/` plus the packaged `schema/` TL sources — and `src/Schema/` is
> deliberately *not* excluded, because the TL layer, the method artefacts,
> the NF5 mirror catalogue (`src/Schema/schema/mirror-catalog.json`) and the
> migrations are the package. Nothing under `docs/` is read at runtime: the
> mirror catalogue used to live at
> `.brain/superpowers/specs/2026-09-11-telegram-mirror-catalog.json` and was
> therefore absent from every dist install, which broke the whole NF5
> ingest path at the first update while `tl()` kept working.

### Platform floor

Read from `composer.json` `require`:

| Requirement | Constraint |
| --- | --- |
| `php` | **`^8.3`** |
| `laravel/framework` | **`^13.0`** |

There is no PHP 8.2 path, and `composer` will refuse anything older. The
README badge (`PHP >= 8.3`) and `composer.json` currently agree; if they ever
disagree again, `composer.json` is the authority.

### Required PHP extensions

All from `composer.json` `require` — these are hard requirements, not
suggestions:

`ext-bcmath`, `ext-dom`, `ext-fileinfo`, `ext-gmp`, `ext-hash`,
`ext-json`, `ext-libxml`, `ext-mbstring`, `ext-openssl`, `ext-pdo`,
`ext-sodium`, `ext-zlib`

`ext-gmp` and `ext-bcmath` in particular are the crypto floor for the
MTProto key handshake; a stock `php:8.3-cli` image does not ship either and
`composer install` will fail without them.

Three more extensions are **not** in `require` because the features that need
them are opt-in, and each one fails loudly at the point of use rather than at
install. `ext-amqp` is the exception: it is now **declared in `suggest`** —
*"Pecl AMQP - required by the tstore and notify RabbitMQ queue drivers.
Without it those two drivers refuse to arm."* Set
`teleframe.tstore.queue=amqp` or `teleframe.notify.queue=amqp` and you owe
yourself `ext-amqp`; leave both alone and you owe it nothing. Also not in
`require`: `ext-redis` (only if you set `teleframe.bus.redis_client=phpredis`;
the default `predis` is pure PHP), and `pcntl` (signal handling in
`teleframe:ingest` / `teleframe:supervise`; without it those loops warn
instead of trapping SIGTERM).

### Runtime dependencies

From `composer.json` `require` (non-extension):

`centrifugal/phpcent ^6.0`, `laravel/framework ^13.0`,
`phpseclib/phpseclib ^3.0`, `psr/log ^3.0`,
`spatie/laravel-data ^4.0`, **`temporal/sdk ^2.19`**,
`vlucas/phpdotenv ^5.6`.

> **`temporal/sdk` is in `require`, not `require-dev`.** It used to be a dev
> dependency, which meant `composer install --no-dev` produced an install with
> no SDK and every Temporal path unreachable. It now installs with the
> package, so you do **not** run `composer require temporal/sdk` yourself and
> you never need to check whether the SDK is present. The one thing you still
> need is a **reachable Temporal server** — see §6.6.

> **`predis/predis ^3.6` is *not* in `require`.** It is in `require-dev` and
> in `suggest`. That matters because `teleframe.bus.redis_client` defaults to
> `'predis'` — the *preferred* driver name, not a shipped one. `composer
> require merezarezaei/teleframe` gives you no Redis client at all; the
> package ships in-memory bus implementations and the console still runs.
> Before you point the bus at a real Redis, either
> `composer require predis/predis` or set
> `TELEFRAME_REDIS=phpredis` and install `ext-redis`. The suggested blurb
> ("faster bus/queue throughput") is optimistic: without one of the two, the
> bus cannot talk to Redis at all.

### Service discovery

The package declares **six** providers and **seven** aliases under
`composer.json` → `extra.laravel.providers` / `extra.laravel.aliases`, so
Laravel auto-registers them. **If your host disables package discovery**
(`extra.laravel.dont-discover` in the *host's* `composer.json`), register
them by hand in `bootstrap/providers.php`:

```php
MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider::class,
MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider::class,
MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider::class,
MeRezaRezaei\Teleframe\Laravel\AppServiceProvider::class,
MeRezaRezaei\Teleframe\Laravel\PassportServiceProvider::class,
MeRezaRezaei\Teleframe\Laravel\SupervisorServiceProvider::class,
```

> **The `Providers\` segment is real for exactly one of those six.**
> `TeleframeServiceProvider` lives in `src/Laravel/Providers/` and its
> namespace ends in `\Providers\`. The other five live directly in
> `src/Laravel/` and their namespace does **not**. Copying the
> `...\Providers\NotifyServiceProvider::class` shape for all of them — a very
> easy thing to do, because five of the six do carry that segment — gives you
> a class that does not exist, and Laravel's discovery would fail the boot
> outright rather than skip it.

Aliases: `TF`, `Teleframe`, `TStore`, `Notify`, `StoreBackup`,
`TelegramApp`, `Passport` → `MeRezaRezaei\Teleframe\Laravel\Facades\*`.
The guide below always uses the fully-qualified facade class, so you never
depend on an alias being registered.

> **Namespace trap.** `MeRezaRezaei\Teleframe\Laravel\AppServiceProvider`
> is the *package's* Telegram app-framework provider. It is not Laravel's
> `Illuminate\Foundation\Support\Providers\AppServiceProvider`. If you add
> the providers by hand, use the FQCN.

### Migrations

`TeleframeServiceProvider::boot()` calls
`loadMigrationsFrom(dirname(__DIR__).'/Migrations')`, so the package's
migrations run as part of your normal `php artisan migrate`. There is
nothing to publish. `src/Laravel/Migrations/` currently holds **27**
migrations; the vault tables you need for `teleframe:login` +
`teleframe:vault-*` are `create_telegram_apps_table`,
`create_telegram_accounts_table` and `add_user_id_to_telegram_accounts_table`.

```bash
php artisan migrate
```

### Optional route macros

Registered by `TeleframeServiceProvider` when the app is not running in the
console:

- `Route::telegramWebhook(string $uri = 'telegram/webhook')` → POST to
  `TelegramWebhookController`
- `Route::telegramWeb(string $uri = 'tg')` → GET
  `{uri}/{path?}` (SPA fallback) to `TelegramWebController`
- middleware alias `tg.miniapp` → `VerifyMiniAppInitData`
- middleware alias `tg.readonly` → `ScopeTelegramReadOnly`
- middleware alias `tg.auth` → `RequireTelegramUserSession`

> ### `Route::telegramWeb()` is deny-by-default
>
> The macro ships its own guard stack: `['tg.auth', 'tg.readonly']`, and
> **the order is the posture**.
>
> `tg.auth` (`RequireTelegramUserSession`) returns **401** for an
> unauthenticated request, and the controller never runs. It also fails
> *closed*: if your auth resolver itself throws, you still get a 401 —
> a broken guard backend must never degrade into an allow. The refusal body
> is a fixed `{"message":"Unauthenticated."}` that names no account, no
> session and no vault row.
>
> `tg.readonly` then opens the read-only scope frame — but note it is **not
> itself a guard**: it only does anything for a request that already has a
> Laravel user session, which is precisely why `tg.auth` exists ahead of it.
>
> If you need a genuinely standalone, session-less deployment, opt out
> explicitly: `Route::telegramWeb()->withoutMiddleware('tg.auth')`. Do that
> as a decision, not by accident.

The embedded web client (`Route::telegramWeb()`) is also **off by default**
and 404s until you set `TELEGRAM_WEB_ENABLED=true` *and* build the bundle —
see the boundaries section of the root README.

---

## 2. Publish the config

```bash
php artisan vendor:publish --tag=teleframe-config
```

There are **five** publishable config files. `--tag=teleframe-config` is an
**umbrella tag and lands all five** — that is what the command above does, and
it is the whole onboarding step. Each file also has its own per-file tag if you
want exactly one. Each publishes to a path whose filename chain matches the
config key its provider merges under, so editing a published file takes effect.

| Publishes to | Merged under key | Registered by | Tags that land it |
| --- | --- | --- | --- |
| `config/teleframe.php` | `teleframe` | `TeleframeServiceProvider` | `teleframe-config`, umbrella (**all five**) |
| `config/teleframe/notify.php` | `teleframe.notify` | `NotifyServiceProvider` | `teleframe-config`, `teleframe-notify-config` |
| `config/teleframe/store/backup.php` | `teleframe.store.backup` | `StoreBackupServiceProvider` | `teleframe-config`, `teleframe-store-backup-config` |
| `config/teleframe/app.php` | `teleframe.app` | `AppServiceProvider` | `teleframe-config`, `teleframe-app-config` |
| `config/teleframe/passport.php` | `teleframe.passport` | `PassportServiceProvider` | `teleframe-config`, `teleframe-passport-config` |

> ### Migrating from the old flat publish targets
>
> Earlier revisions published the four secondary configs to flat filenames
> (`config/teleframe-notify.php`, `config/teleframe-app.php`,
> `config/teleframe-store-backup.php`, `config/teleframe-passport.php`).
> Laravel's config loader keys a file by its path, so those files loaded
> under `teleframe-notify` / `teleframe-app` / … and **editing them had no
> effect** — the package defaults kept winning under the real keys.
>
> If you published under the old names, **move your edits** into the new
> files and delete the old ones:
>
> ```bash
> php artisan vendor:publish --tag=teleframe-notify-config --force
> # then fold any local edits from config/teleframe-notify.php into
> # config/teleframe/notify.php, and rm config/teleframe-notify.php
> ```
>
> The same applies to the `app`, `passport` and `store-backup` tags. A
> leftover flat file is inert but misleading — it will keep reading back
> your edits under a key nothing looks at.

### 2.1 `teleframe.*` — `src/Laravel/config/teleframe.php`

| Key | Default | What the default means |
| --- | --- | --- |
| `bot_token` | `env('TELEGRAM_BOT_TOKEN')` = `null` | Bot API token. `null` ⇒ every Bot-API call and the `tg.miniapp` HMAC check fails. |
| `webhook_secret` | `env('TELEGRAM_WEBHOOK_SECRET')` = `null` | Shared secret on inbound webhook payloads. |
| `api_id` | `env('TELEGRAM_API_ID')` = `null` | From <https://my.telegram.org>. Required by `Teleframe::user()` — it throws `RuntimeException` if empty. |
| `api_hash` | `env('TELEGRAM_API_HASH')` = `null` | Same; required together with `api_id`. |
| `dc_id` | `env('TELEGRAM_DC_ID', 2)` = `2` | Default Telegram Data Center for new sessions. |
| `live_mode` | `env('TELEFRAME_LIVE', false)` | Opt-in for live gates that talk to real Telegram. |
| `primary_account_id` | `env('TELEFRAME_PRIMARY_ACCOUNT_ID')` = `null` | The account used when none is named. `null` = "first account" fallback. |
| `identity.default_sender` | `env('TELEFRAME_IDENTITY_DEFAULT_SENDER')` = `null` | Bot token used when a channel/notification names no sender. |
| `identity.miniapp_auth_max_age` | `env('TELEFRAME_MINIAPP_AUTH_MAX_AGE', 1800)` | Mini App `initData` freshness window, seconds. Absent or older ⇒ the `tg.miniapp` guard rejects the replay. |
| `botmap.secret` | `env('TELEFRAME_BOTMAP_SECRET')` = `null` | HMAC secret guarding the BotMap HTTP surface. |
| `user_session` | `env('TELEGRAM_USER_SESSION')` = `null` | Exported session string. **A credential** — `.env` only. |
| `bot_session` | `env('TELEGRAM_BOT_SESSION')` = `null` | Exported MTProto bot session. **A credential.** |
| `vault.default_account` | `env('TELEFRAME_DEFAULT_ACCOUNT_ID')` = `null` | The *label* of the vault account to use when none is named. Every secret lives in the DB vault, not in env. |
| `realtime.url` | `env('TELEFRAME_CENTRIFUGO_URL', '')` | Centrifugo HTTP API endpoint. `''` ⇒ the `UpdateStored` → Centrifugo listener is **not registered** and the projection is silent. |
| `realtime.apikey` | `env('TELEFRAME_CENTRIFUGO_APIKEY', '')` | Publish API key. |
| `realtime.secret` | `env('TELEFRAME_CENTRIFUGO_SECRET', '')` | Only needed if Centrifugo verifies JWT signatures on publish. |
| `logging.logger` | `env('TELEFRAME_LOGGER', '')` | FQCN of a PSR-3 logger. `''` ⇒ `NullLogger` — **the engine is completely silent by default.** A `Psr\Log\LoggerInterface` binding in your own provider always wins over this. |
| `dashboard.prefix` | `env('TELEFRAME_DASHBOARD_PREFIX', 'teleframe')` | Route prefix for `Teleframe::routes()`. |
| `dashboard.middleware` | `['web', 'auth']` | Guard on the dashboard group — **your** login, like Laravel Horizon. Token hosts should pass `Teleframe::routes(prefix: 'api', middleware: ['auth:sanctum'])`. |
| `telegram_web.enabled` | `env('TELEGRAM_WEB_ENABLED', false)` | Embedded web client. `false` ⇒ the route 404s even if registered. |
| `telegram_web.dist_path` | `env('TELEGRAM_WEB_DIST')` = `null` | Override the package `tgweb/dist` location. |
| `telegram_web.live_endpoint` | `env('TELEGRAM_WEB_LIVE_ENDPOINT')` = `null` | Where a patched web client connects to your relay. Empty ⇒ the accounts API omits `live_endpoint`. |
| `telegram_web.ticket_ttl_minutes` | `env('TELEGRAM_RELAY_TICKET_TTL_MINUTES', 10)` | Lifetime of a minted single-use relay ticket. |
| `relay.enabled` | `env('TELEGRAM_RELAY_ENABLED', false)` | Master switch for `teleframe:relay`. |
| `relay.listen_host` | `env('TELEGRAM_RELAY_LISTEN_HOST', '127.0.0.1')` | Loopback default. Non-loopback binds are gated by `TF_RELAY_TCP_SECRET`. |
| `relay.port_base` | `env('TELEGRAM_RELAY_PORT_BASE', 0)` | `0` = each listener binds a free ephemeral port. |
| `relay.upstream_host` | `env('TELEGRAM_RELAY_UPSTREAM')` = `null` | Override the upstream real DC. Default: `dc{dc_id}.telegram.org:443`. |
| `relay.upstream_port` | `env('TELEGRAM_RELAY_UPSTREAM_PORT', 443)` | Upstream port. |
| `relay.accounts` | `[]` | Rows of `['account_id' => int, 'dc_id' => int, 'port' => int?, 'upstream_host' => string?, 'upstream_port' => int?]` — one listener per row. |
| `relay.vault_terminal.enabled` | `env('TF_RELAY_VAULT_TERMINAL', false)` | Play the relay's own DH handshake and answer RPCs from the vault account instead of forwarding bytes to Telegram. |
| `relay.vault_terminal.rsa_key` | `env('TF_RELAY_RSA_KEY', '')` | Path to the relay-local PEM private key registered as a trusted `SERVER_KEY` in a patched tgweb build. |
| `schema_sources` | `env('TELEFRAME_SCHEMA_SOURCES')` = `null` | Directory of `.tl` sources to regenerate from. `null` = the package's committed mirror. |
| `ship_namespaces` | `SchemaRegenerator::DEFAULT_SHIP_NAMESPACES`, overridable by comma-separated `TELEFRAME_SHIP_NAMESPACES` | TL namespaces whose migrations `teleframe:regenerate --ship` copies into `src/Laravel/Migrations/`. |
| `bus.stream` | `StreamSchema::STREAM` | Redis stream name (wire-compatible constant; do not rename). |
| `bus.group` | `StreamSchema::GROUP` | Consumer group name. |
| `bus.reload_channel` | `StreamSchema::RELOAD_CHANNEL` | Hot-reload pub/sub channel. |
| `bus.connection` | `'default'` | Which `illuminate/redis` connection the bus reads/writes through. |
| `bus.redis_client` | `env('TELEFRAME_REDIS', 'predis')` | `predis` (pure PHP — **you must install it yourself**, it is not a hard dependency) or `phpredis` (extension). |
| `supervisor.processes` | `env('TELEFRAME_SUPERVISOR_PROCESSES', 'daemon,ingest')` | Which shipped long-running processes `teleframe:supervise` keeps alive. The closed catalog is `daemon`, `ingest`, `tstore-work`, `relay`. A name outside it is a **loud** failure, never a silent skip. |
| `supervisor.restart_delay_seconds` | `env('TELEFRAME_SUPERVISOR_RESTART_DELAY', 5)` | Base of the exponential restart ladder. |
| `supervisor.max_backoff_seconds` | `env('TELEFRAME_SUPERVISOR_MAX_BACKOFF', 300)` | Ceiling on that ladder. |
| `supervisor.max_restarts` | `env('TELEFRAME_SUPERVISOR_MAX_RESTARTS', 0)` | Restarts allowed per process. `0` = unlimited. |
| `supervisor.env` | `[]` | Per-process environment overrides, keyed by process name. |
| `daemon.accounts` | `[]` | The multi-account supervisor's dialable set. Each row needs `account_id` (positive int) and `session_string` (**credential**); `dc`, `api_id`, `api_hash` optional. |
| `backfill.request_budget` | `env('TELEFRAME_BACKFILL_BUDGET', 25)` | Per-run request budget; the headroom rule halves it. |
| `backfill.flood_cap_seconds` | `3600` | Flood-wait ceiling. |
| `backup.driver` | `env('TELEFRAME_BACKUP_DRIVER', 'telegram')` | The accepted set is exactly **`memory`**, **`telegram`** and **`s3`** — nothing else. Any other value is refused by name at bind time (`unknown backup driver "…" — expected memory\|telegram\|s3`). `telegram` is the default: a real private-channel vault. `s3` is the durable tier that stores **off** Telegram, over the `teleframe.tstore.s3` object gateway (endpoint/region/bucket/key/secret live there, so `tstore.cache=minio` and `backup.driver=s3` address one bucket). `memory` is process-local, stores nothing, and needs `TELEFRAME_BACKUP_ALLOW_VOLATILE=1` to be used at all. `ftp` and `rsync` were **never** implemented — do not set them. |
| `backup.account` | `env('TELEFRAME_BACKUP_ACCOUNT')` = `null` | The `daemon.accounts` `account_id` the `telegram` driver resolves its session through. |
| `backup.chunk_size` | `env('TELEFRAME_BACKUP_CHUNK_SIZE', 4194304)` | Content-addressed chunk split (4 MiB). |
| `backup.sets` | one `default` set with `paths => []`, `excludes => []` | Each set lists `paths` (walked recursively) and substring `excludes`. **The runner refuses to upload with an empty `paths`.** |
| `backup.max_age_hours` | `BackupCommand::DEFAULT_MAX_AGE_HOURS` = `24` | Staleness bound for `teleframe:backup status`. Read with a default, so it works undeclared, but publish it if you want a bound other than a day. A **daily** backup that misses one cycle is a red status, on purpose — see the note below. |
| `tstore.account_id` | `(int) env('TELEFRAME_TSTORE_ACCOUNT', 0)` | The MTProto account whose dialogs host the store buckets. `0` = unconfigured ⇒ `put`/`get` throw. |
| `tstore.passphrase` | `env('TELEFRAME_TSTORE_PASSPHRASE', '')` | Default object passphrase. `''` = raw mode by default. |
| `tstore.cache` | `env('TELEFRAME_TSTORE_CACHE', 'memory')` | `memory` (in-heap TTL+LRU) or `minio` (path-style `127.0.0.1:9010`; degrades gracefully when down). |
| `tstore.cache_ttl_seconds` | `env('TELEFRAME_TSTORE_CACHE_TTL', 600)` | Hot-cache TTL. |
| `tstore.cache_max_bytes` | `env('TELEFRAME_TSTORE_CACHE_MAX_BYTES', 67108864)` | Hot-cache byte ceiling (64 MiB). |
| `tstore.bucket_prefix` | `env('TELEFRAME_TSTORE_BUCKET_PREFIX', 'teleframe-store:')` | Channel title prefix used for classification. |
| `tstore.premium` | `env('TELEFRAME_TSTORE_PREMIUM', false)` | Raises the 4 GiB manager caps. |
| `tstore.queue` | `env('TELEFRAME_TSTORE_QUEUE', 'sync')` | `sync` (inline) or `amqp` (needs `ext-amqp` + a reachable broker; durable `tstore.upload`). `amqp` defers uploads to `teleframe:tstore work`. |
| `tstore.amqp.host` / `.port` / `.vhost` | `127.0.0.1` / `5672` / `/` | Broker address the `amqp` upload queue dials. |
| `tstore.amqp.user` / `.password` | `''` / `''` — **empty, not `guest`/`guest`** | An empty value is *treated* as `guest`/`guest` at the dial, so nothing is hidden — but the pair is now **refused** against any non-loopback host. `AmqpUploadQueue::assertUsableIdentity()` throws by name: *"refusing to dial the amqp upload queue at &lt;host&gt; with the stock guest/guest broker identity: anyone who can publish to tstore.upload could drive the worker."* Set `TELEFRAME_TSTORE_AMQP_USER` / `..._PASSWORD` for anything that is not a local broker. |
| `tstore.amqp.heartbeat` | `env('TELEFRAME_TSTORE_AMQP_HEARTBEAT', 30)` | AMQP heartbeat interval, seconds. |
| `tstore.amqp.connect_timeout` | `env('TELEFRAME_TSTORE_AMQP_CONNECT_TIMEOUT', 3.0)` | Dial timeout, seconds. Without socket-level timeouts a half-open connection reports itself as connected and is only discovered on the first write, at which point the publish blocks for the OS TCP timeout — the "RabbitMQ hung, the app is stuck" first-run experience. |
| `tstore.amqp.read_timeout` | `env('TELEFRAME_TSTORE_AMQP_READ_TIMEOUT', 5.0)` | Read timeout, seconds. |
| `tstore.amqp.write_timeout` | `env('TELEFRAME_TSTORE_AMQP_WRITE_TIMEOUT', 5.0)` | Write timeout, seconds. |
| `tstore.amqp.publish_retry_budget_seconds` | `env('TELEFRAME_TSTORE_AMQP_PUBLISH_BUDGET', 5.0)` | Wall-clock budget for one publish to succeed or give up. |
| `tstore.schedules` | `env('TELEFRAME_TSTORE_SCHEDULES', 'null')` | `null` (off, the shipped default) or `temporal`. Read by `TeleframeServiceProvider`, which prefers a host-bound `RecurringScheduleClient` and otherwise builds the package's own SDK-backed client from `schedules_dsn` + `schedules_namespace`; with neither it **refuses by name at resolve time** rather than degrading to `null`. Setting it does NOT arm anything by itself: arming is an explicit operator action, `teleframe:tstore schedules --install` (backup seam: `teleframe:tstore schedule --install`; notify: `teleframe:notify schedules --install`). Deliberate, so installing the package cannot start firing jobs. The task queue is **not** a key here — see §6.6. |
| `tstore.schedules_dsn` | `env('TELEFRAME_TSTORE_TEMPORAL_DSN', '127.0.0.1:7233')` | Temporal DSN for the above. |
| `tstore.schedules_namespace` | `env('TELEFRAME_TSTORE_TEMPORAL_NAMESPACE', 'default')` | Temporal accounting namespace handed to `TemporalClientResolver`. |
| `tstore.listen` | `env('TELEFRAME_TSTORE_LISTEN', false)` | Live media indexing from data-channel updates. Off by default; `teleframe:tstore index` is the exact reconciliation path. |

### 2.2 `teleframe.notify.*` — `src/Laravel/config/notify.php`

| Key | Default | What the default means |
| --- | --- | --- |
| `default_chat` | `env('TELEFRAME_NOTIFY_CHAT', '')` | `@channel`, chat id, or forum supergroup id. `''` = unconfigured: the facade still resolves and is callable, **but sends throw on first transport use** — never a silent drop. This is the single most common first-run failure. |
| `inline_budget` | `env('TELEFRAME_NOTIFY_INLINE_BUDGET', 800)` | Chars of brief+metadata kept inline; the long body goes to an HTML attachment. |
| `attachment_dir` | `env('TELEFRAME_NOTIFY_ATTACHMENT_DIR', '')` | Temp dir for rendered attachments. `''` = system temp. |
| `premium` | `env('TELEFRAME_NOTIFY_PREMIUM', false)` | Raises capability limits. Per-send override: `PendingNotify::premium(bool)`. |
| `exceptions.enabled` | `env('TELEFRAME_NOTIFY_EXCEPTIONS', false)` | Bind to the host exception handler. **Off by default** — mailing every exception to a chat is an operator decision. |
| `exceptions.level` | `env('TELEFRAME_NOTIFY_EXCEPTIONS_LEVEL', 'error')` | PSR-3 level at which exceptions are reported. |
| `exceptions.topic` | `env('TELEFRAME_NOTIFY_EXCEPTIONS_TOPIC', '')` | Topic override; `''` = the level's topic. |
| `queue` | `env('TELEFRAME_NOTIFY_QUEUE', 'auto')` | `auto` (the shipped default), `laravel`, `amqp` or `sync`; anything else throws. **`auto` tries RabbitMQ BEFORE the host queue**: it selects the AMQP driver when `ext-amqp` is loaded *and* a broker host is configured, and only then falls back to the host's own Laravel queue, and only then to inline `sync`. It used to prefer the Laravel queue, so configuring a broker silently did nothing. `teleframe:notify work` prints the resolved driver, and the same description is bound as `teleclient.notify.queue.report` so a health check can show you the same answer. |
| `amqp.host` / `.port` / `.vhost` | `127.0.0.1` / `5672` / `/` | Broker address the notify AMQP driver dials. |
| `amqp.user` / `.password` | `''` / `''` — **empty, not `guest`/`guest`** | Same guard as the store path: `NotifyServiceProvider` calls `AmqpUploadQueue::assertUsableIdentity()` before arming, so **stock credentials against a REMOTE broker are refused**, loudly and by name. Set `TELEFRAME_NOTIFY_AMQP_USER` / `..._PASSWORD` for anything that is not a local broker. |
| `amqp.heartbeat` | `env('TELEFRAME_NOTIFY_AMQP_HEARTBEAT', 30)` | AMQP heartbeat interval, seconds. |
| `amqp.connect_timeout` | `env('TELEFRAME_NOTIFY_AMQP_CONNECT_TIMEOUT', 3.0)` | Dial timeout, seconds. |
| `amqp.read_timeout` | `env('TELEFRAME_NOTIFY_AMQP_READ_TIMEOUT', 5.0)` | Read timeout, seconds. |
| `amqp.write_timeout` | `env('TELEFRAME_NOTIFY_AMQP_WRITE_TIMEOUT', 5.0)` | Write timeout, seconds. |
| `queue_connection` / `queue_name` | `''` / `tframe.notify` | Only used by the `laravel` driver. `''` follows the host's `queue.default`. |
| `queue_fallback` | `env('TELEFRAME_NOTIFY_QUEUE_FALLBACK', 'sync')` | What `auto` does when the host has **no** async transport: `sync` keeps notifications working (inline, plus a one-shot warning naming this key) or `throw` for a strict opt-in. |
| `schedules` | `env('TELEFRAME_NOTIFY_SCHEDULES', 'null')` | `null` (probe only, the shipped default) or `temporal`. Read by `NotifyServiceProvider` with the same posture as `tstore.schedules`; arming is the explicit `teleframe:notify schedules --install`. |
| `schedules_dsn` | `env('TELEFRAME_NOTIFY_TEMPORAL_DSN', '127.0.0.1:7233')` | Temporal DSN. |
| `topics` | 11 seed rows: `app.debug`…`app.emergency` (thread_id 1–8), `backup`, `backup.missing`, `report` | Merged **over** `Topic::defaults()` (`src/Teleframe/Notify/Topic.php:58`) — there is no `TopicRegistry` class. A forum supergroup maps each PSR-3 level to its own `message_thread_id`. |
| `capabilities` | `[]` | Merge overrides for the built-in `CapabilityTable` (text_message 4096, caption 1024, media_album 10, file_upload 50 MiB bot / 2 GiB mtproto, send_rate 30 msg/s, per_chat_rate 1 msg/s, topic_budget 60 msg/h — premium multipliers applied, rate-row **enforcement** is wave 3). |

### 2.3 `teleframe.store.backup.*` — `src/Laravel/config/store-backup.php`

| Key | Default | What the default means |
| --- | --- | --- |
| `enabled` | `env('TELEFRAME_STORE_BACKUP_ENABLED', true)` | Master switch. `false` = service still constructs, watchdog + schedule install skipped. |
| `schedule.enabled` | `env('TELEFRAME_STORE_BACKUP_SCHEDULE_ENABLED', true)` | Gates the schedule + watchdog wiring. |
| `schedule.interval_seconds` | `env('TELEFRAME_STORE_BACKUP_INTERVAL', 86400)` | Daily by default. |
| `schedule.publisher` | `env('TELEFRAME_STORE_BACKUP_SCHEDULES', 'null')` | `null` (the one-line cron posture) or `temporal`. **`temporal` is no longer "wave 2" and no longer a wall**: a host-bound `RecurringScheduleClient` wins verbatim, otherwise the package's own `temporal/sdk`-backed client is used. `temporal/sdk ^2.19` is in **`require`**, so it is already installed — there is nothing to `composer require`, and nothing dials at boot. With neither client available the key **refuses at boot by name** and never degrades to `null`. **No live-Temporal-server verification exists in this repo** — the path is pinned by tests with an injected client and by the lazy/no-dial construction gate. |
| `schedule.dsn` | `env('TELEFRAME_STORE_BACKUP_TEMPORAL_DSN', '127.0.0.1:7233')` | Temporal DSN. |
| `schedule.watchdog_expect_multiplier` | `env('TELEFRAME_STORE_BACKUP_WATCHDOG_MULT', 2)` | Expect-every = interval × this. Floor of 3600 s enforced in the provider. |
| `schedule.package_schedule.enabled` | `env('TELEFRAME_STORE_BACKUP_PACKAGE_SCHEDULE_ENABLED', false)` | **`false` by default, and it has to stay that way.** When `true`, the package registers `teleframe:tstore backup` on **Laravel's own scheduler** (`dailyAt` → `withoutOverlapping()` → `onOneServer()`), so a host runs `php artisan schedule:run` and **deletes its cron line**. It is gated on this key and nothing else: `schedule.enabled` above governs the *watchdog*, and reusing it would silently repurpose a documented switch. Turn it on only after reading the double-fire note in [host-install.md](host-install.md) — the shipped posture is a hand-written crontab line, cron is invisible to the Laravel scheduler, and **no config key can discover it**, so a double-fire is the operator's to avoid. |
| `schedule.package_schedule.daily_at` | `env('TELEFRAME_STORE_BACKUP_PACKAGE_SCHEDULE_DAILY_AT', '03:47')` | 24-hour `H:i`, deliberately off the file-vault sibling's `03:17` so a host running both does not stack two database dumps on one minute. An unparseable value (including `99:99`) **throws at console boot by name** rather than registering at a time you did not ask for. |
| `watchdog.alert_log` | `env('TELEFRAME_STORE_BACKUP_WATCHDOG_ALERT_LOG', '')` | **Where the no-show finding is written down.** `''` = `storage/teleframe/watchdog-alerts.jsonl` under the host, falling back to the system temp directory when there is no application base path (a bare console). A `FileWatchAlertSink` appends one JSON object per line and never rewrites it — the dumbest durable thing available on purpose, because its failure domain must not be the queue failure being reported. The record is written **first and unconditionally**; the `{topic}.missing` Telegram notification is an enhancement on top and is allowed to fail. Point it at a mounted volume if system temp is ephemeral, or at whatever your log shipper already tails. `teleframe:notify`'s bare-console fallback watchdog resolves the **same** key to the same default, so a finding is in the same file on either path. |
| `target.account_id` | `env('TELEFRAME_STORE_BACKUP_ACCOUNT', 0)` | `0` = reuse `teleframe.tstore.account_id`. |
| `target.bucket` | `env('TELEFRAME_STORE_BACKUP_BUCKET', 'default')` | Registry title becomes `teleframe-backup:default`. |
| `target.peer_type` | `env('TELEFRAME_STORE_BACKUP_PEER_TYPE', 'channel')` | `channel` or `group`. |
| `target.passphrase` | `env('TELEFRAME_STORE_BACKUP_PASSPHRASE', '')` | Seal passphrase. `target.mode` defaults to `sealed` — the "put pass on whatever you send there" posture. |
| `target.members` | `[]` | User ids/usernames to invite. **Never session strings.** |
| `targets` | `[]` | Multi-target rows: `bucket`, `peer_id`, `access_hash`, `peer_type`, `mode`, `passphrase`, `members`. Non-owned peers **require** `mode=sealed`; config-merge refuses raw for them. When empty, the provider builds the registry from the singular `target` block. |
| `database.driver` | `env('TELEFRAME_STORE_BACKUP_DB_DRIVER', '')` | `''` = unconfigured. The service still constructs; **throws loud on first `dump()`**. This is the second most common first-run failure. |
| `database.host` / `.port` / `.database` / `.user` / `.socket` | `''` / `0` / `''` / `''` / `''` | `''` = read the host's DB config at run time. |
| `database.tables` | `[]` | Optional whitelist. `[]` = whole database. |
| — | *(no key)* | **There is deliberately no `TELEFRAME_STORE_BACKUP_PASSWORD` key.** DB passwords are env-at-run only (`MYSQL_PWD` / `PGPASSWORD` in the forked dump process env) — never argv, never config, never logs. |
| `spool_dir` | `env('TELEFRAME_STORE_BACKUP_SPOOL_DIR', '')` | `''` = `storage/framework/teleframe-dumps`, created 0700. |
| `queue` | `env('TELEFRAME_STORE_BACKUP_QUEUE', 'sync')` | `sync` or `amqp`, reusing the tstore upload seams. |

### 2.4 `teleframe.passport.*` — `src/Laravel/config/passport.php`

**Runtime posture: inert by default.** `enabled=false` means the module is
fully constructible (every seam resolves to a memory/fake default) but
nothing runs; `login()` throws only when actually called.

| Key | Default | What the default means |
| --- | --- | --- |
| `enabled` | `env('TELEFRAME_PASSPORT_ENABLED', false)` | Master switch. `false` ⇒ `Passport::login()` throws `PassportLoginException('passport login not enabled (configure teleframe.passport)')`. |
| `private_key_path` | `env('TELEFRAME_PASSPORT_PRIVATE_KEY', '')` | A **path** to the bot RSA private key PEM, `0600`-checked by `PassportKeyLoader`. Never the PEM itself. |
| `account_id` | `env('TELEFRAME_PASSPORT_ACCOUNT_ID', 0)` | Explicit tenant. `0` falls back to the Identity primary-account rule. |
| `nonce_ttl` | `env('TELEFRAME_PASSPORT_NONCE_TTL', 900)` | Issue/consume TTL, seconds. |
| `nonce_store` | `env('TELEFRAME_PASSPORT_NONCE_STORE', '')` | `''` binds the in-process `MemoryNonceRegistry`. A PSR-16 cache key binds `CacheNonceRegistry` — **durable**, so nonces survive between the issuing web session and the webhook. Use this in production. |
| `allowed_types` | `['personal_details', 'email', 'phone']` | Allow-list of accepted `SecureValue` types. |
| `user_lookup` | `env('TELEFRAME_PASSPORT_USER_LOOKUP', 'session')` | `session` (bind to the nonce-issued userId — the shipped default) or `field` (bind to a verified `email`/`phone` claim inside the decrypted payload). **`field` is shipped and reachable**, but the provider only builds `FieldIdentityResolver` when `require_verified` is on; without it, selecting `field` throws. |
| `user_lookup_field` | `env('TELEFRAME_PASSPORT_USER_LOOKUP_FIELD', 'email')` | Only used when `user_lookup=field`. The resolver also matches only `email` and `phone`; a payload carrying both is refused rather than one being preferred. |
| `require_verified` | `env('TELEFRAME_PASSPORT_REQUIRE_VERIFIED', false)` | The switch that says the host has already verified the claim it is about to trust. It gates the `field` resolver, and nothing else. |
| `email_column` | `env('TELEFRAME_PASSPORT_EMAIL_COLUMN', 'email')` | Host users-table column the `field` resolver matches `email` against. |
| `phone_column` | `env('TELEFRAME_PASSPORT_PHONE_COLUMN', 'phone')` | Host users-table column the `field` resolver matches `phone` against. |
| `require_password_confirmation` | `env('TELEFRAME_PASSPORT_REQUIRE_PASSWORD', false)` | Optional SRP/2FA re-confirm. `false` = skip. |

### 2.5 `teleframe.app.*` — `src/Laravel/config/app.php`

| Key | Default | What the default means |
| --- | --- | --- |
| `apps` | `[]` | `AppDefinition` seeds, registered at provider boot. Rows map 1:1 onto `AppDefinition::define()`: `name`, `mode` (`bot`\|`user`), `triggers`, `calls`. A trigger row is `{match, handler, priority?, on_own?}` — `match` uses the shipped matcher grammar (`*`, exact, `FooBar*`, `/start %s`), `handler` is a PSR-11 invokable string, `[Class::class, 'method']`, or a closure (config files are executed PHP, so closures are legal rows). A call row is `{name, interval_seconds, method, params?}`. **Invalid rows throw loud at resolve time.** |
| `schedules` | `env('TELEFRAME_APP_SCHEDULES', 'null')` | `null` (probe only) or `temporal`. **`temporal` is no longer "wave 2"**: a host-bound `RecurringScheduleClient` wins verbatim, otherwise the package's own `temporal/sdk`-backed client is used. `temporal/sdk ^2.19` is in **`require`**, so it is already installed — there is nothing to `composer require`, and nothing dials at boot. With neither client available the key **refuses at boot by name** and never degrades to `null` — for a recurring app call, silently disabling the schedule while the host believes it is armed is the worst available failure. **No live-Temporal-server verification exists in this repo.** |
| `schedules_dsn` | `env('TELEFRAME_APP_TEMPORAL_DSN', '127.0.0.1:7233')` | Temporal DSN. |
| `transport` | `null` | Optional `callable(AppMode): mixed` resolver override. Absent ⇒ the provider builds one from `TeleframeClient` (`bot` → `TeleframeClient::bot()`, `user` → `TeleframeClient::user()`), resolved at call time; unconfigured throws loud. |

### 2.6 Other publishable assets

| Tag | Publishes to |
| --- | --- |
| `teleframe-dashboard` | `resources/views/vendor/teleframe/dashboard.blade.php` |
| `teleframe-miniapp` | `base_path()` — the **contents** of the package's `Stubs/miniapp` directory, so you get `base_path()/package.json`, `base_path()/vite.config.js` and `base_path()/resources/…` (the Vue example, the theme CSS, the Blade view). It does **not** create a `base_path()/miniapp` subdirectory; if you want one, move the published tree into it. |

Views are *also* loadable un-published under the `teleframe` view namespace
(`loadViewsFrom`), so you only need `teleframe-dashboard` if you want to
edit the Blade.

---

## 3. Connect a Telegram account

> ### 🔴 This step creates credentials
>
> `teleframe:login` prints the **full exported session string** to your
> terminal and offers to write it into `.env`. A session string is a
> **bearer credential**: anyone holding it has your Telegram account.
>
> - Never commit `.env`. Never put a session string in a config file that
>   is tracked, in a Blade view, in JavaScript, or in any client-side
>   bundle.
> - Prefer the **DB-encrypted vault** (`--account=LABEL`) over the `.env`
>   flow. Secrets then live in the `telegram_apps` / `telegram_accounts`
>   tables behind `encrypted` casts, and only the *label* is in env.
> - `examples/.env.example` gets a **key with an empty value**, never a
>   value. (It is `examples/`, not the project root — and `/examples` is on
>   `archive.exclude`, so it is a repo artifact, not something a dist
>   consumer receives.)
> - This is enforced in the package by `php bin/scan-credentials.php` (the
>   first step of `composer verify`), which fails the build if session
>   strings or `.env` files are committed. See also the credential rules in
>   `AGENTS.md` and `docs/usage/README.md`.

### 3.1 Register the app (from my.telegram.org)

```bash
php artisan teleframe:vault-add-app myapp --api-id=API_ID --api-hash=API_HASH
```

Omit the flags and it prompts. `api_id`/`api_hash` are **application
credentials** — treat them as secrets too.

If you would rather not use the DB vault, put them straight in `.env` as
`TELEGRAM_API_ID` / `TELEGRAM_API_HASH` and skip this step.

### 3.2 Register the account

```bash
php artisan teleframe:vault-add-account main --app=myapp --type=user
php artisan teleframe:vault-list          # labels only, no secrets
```

Options: `--telegram-id=` (numeric, can be set after login),
`--bot-token=` (for `--type=bot`), `--dc=2`.

### 3.3 Log in

Phone flow — interactive; you will be asked for the code Telegram sends,
and then the 2FA cloud password if the account has one. The number is
**prompted for**, not passed on the command line, so it never lands in your
shell history:

```bash
php artisan teleframe:login --app=myapp --account=main
```

If you must script it, `--phone=` takes an international number with
country code — but understand that it is then visible in `ps` output and in
your shell history for the life of the box, which is why the prompt is the
default.

QR flow — scan with an already-signed-in Telegram app. Nothing is typed on
the keyboard, which makes it the safer default for a shared machine:

```bash
php artisan teleframe:login --qr --app=myapp --account=main
```

Bot flow over MTProto:

```bash
php artisan teleframe:login --bot --app=myapp --account=main
```

Other flags: `--dc=2` (Data Center 1–5).

The command resolves `api_id`/`api_hash` from the vault app you name with
`--app=`, falling back to `TELEGRAM_API_ID` / `TELEGRAM_API_HASH`, and
prompts for them if both are absent.

### 3.4 Confirm it works

```bash
php artisan teleframe:doctor
php artisan teleframe:doctor --dc=2 --bot
php artisan teleframe:doctor --account=1     # probe the STORED session, not a throwaway key
```

`teleframe:doctor` reads `teleframe.api_id` / `teleframe.api_hash` (it
handshakes with Telegram's DC, so it is not a no-credential check). In an
interactive terminal it **prompts** for either one that is missing. Under
`-n` / `--no-interaction` (CI, Docker, cron) it does not prompt: it prints a
one-line "not set" diagnostic and exits `1`, so a non-interactive run fails
legibly instead of aborting mid-prompt.

> **The message names the wrong env keys — do not follow it.** The
> non-interactive diagnostic literally says
> `teleframe:doctor needs TELEFRAME_API_ID and TELEFRAME_API_HASH … Not set.`
> Those two keys are read by **nothing** in this package. The command reads
> `config('teleframe.api_id')` and `config('teleframe.api_hash')`, which
> resolve `TELEGRAM_API_ID` and `TELEGRAM_API_HASH`. Setting the
> `TELEFRAME_`-prefixed pair will not make the doctor pass, and will not
> satisfy anything else either. Same trap in the `--bot` branch, which asks
> for `TELEFRAME_BOT_TOKEN` where the real key is `TELEGRAM_BOT_TOKEN`. The
> two spellings in the shipped source are wrong; the config table in §2.1 is
> right.

`teleframe:doctor` verifies live MTProto connectivity and needs **no
account** — it is the cheapest way to prove your network, your `api_id` /
`api_hash` and your extensions are all right before you go hunting for a
credential problem.

### 3.4a `teleframe:doctor --account=` and the bus probe

By default the doctor handshakes with a **throwaway** auth key. That proves
your network and your `api_id`/`api_hash`, and it **cannot** tell you whether
a stored session is still valid — it went green for a revoked session,
because it never used one.

`--account=<id>` probes the **stored** session of a `telegram_accounts.id`
instead, so a terminated or revoked key surfaces as the real
`AuthKeyException` rather than a green run:

```bash
php artisan teleframe:doctor --account=1
```

It refuses loudly if no `telegram_accounts` row has that id, or if the row has
no stored session string, or if the session is unreadable — all three exit
`1` rather than falling back to the throwaway key, because a silent fallback
is exactly the false green this flag exists to remove.

The doctor also runs a **bounded Redis/bus probe**, in this order, each call
capped by `--bus-timeout` seconds so a hung Redis cannot hang the command:

| Probe | What it tells you |
| --- | --- |
| `PING` | Is Redis reachable at all. Unreachable ⇒ `FAIL` and a non-zero exit, because ingest is then provably dead. |
| `XLEN tg:stream:updates` | How deep the update backlog is. |
| `XINFO GROUPS tg:stream:updates` | Whether consumer group **`teleclient`** is present. A missing group is reported as an explicit `FINDING`, not a pass — a missing group is what silently starves `teleframe:ingest`, and the bus may legitimately not be running. |
| `XLEN tg:stream:dead-letter` | Unconsumed dead-letter entries. Non-zero is a `FINDING` you should look at, not a failure. |

> **Reading the bus probe.** `OK` on the `PING`/`XLEN` lines means the call
> worked. `FINDING` means the call *worked but the thing it looked for is
> wrong or missing* — that is the line that matters, and `FINDING` is not a
> pass. `FAIL` means the call itself did not complete. The exit code is `0`
> whenever Redis answered, so **do not gate a monitor on the exit code alone
> — read the `FINDING` lines.**

### 3.5 Point the rest of the package at the account

```bash
php artisan teleframe:vault-use-default main
```

This rewrites `TELEFRAME_DEFAULT_ACCOUNT_ID` in `.env` to the numeric
Telegram user id behind that label. It is the `teleframe.vault.default_account`
value, and it is what `Teleframe::userFromVault()` with no argument
resolves.

---

## 4. Facade quick start

Every sample below was read off the class the facade actually resolves, not
off a docblock. Where a facade's own `@method` annotation contradicts the
resolved class, the resolved class wins and the difference is called out.

### 4.1 `Teleframe` / `TF` — the client

`MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe` is the Laravel facade
over `MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient`. `TF` is
declared as `class TF extends Teleframe`, so every example below works
identically through `TF`.

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe;

// One real call: get the signed-in user's own profile.
$me = Teleframe::user(accountId: ACCOUNT_ID, dcId: 2)->call('users.getUsers', [
    'id' => [ACCOUNT_ID],
]);
```

**With nothing configured:** `Teleframe::user()` throws
`RuntimeException('Telegram API ID and API Hash are required...')` if
`api_id`/`api_hash` are empty. `Teleframe::userFromVault()` with an empty
vault delegates to the same `user()` and therefore throws the same way.
Nothing dials until you make a call, so construction is always safe.

Other verified entry points on the same facade:

```php
Teleframe::fromSession($sessionString, apiId: API_ID, apiHash: API_HASH);
Teleframe::forAccount(accountId: ACCOUNT_ID, dcId: 2);   // alias of user()
Teleframe::bot();                                         // Bot API over HTTP
Teleframe::botMtproto();                                  // bot over MTProto
Teleframe::userFromVault('main');
Teleframe::botFromVault('mybot', transport: 'mtproto');
Teleframe::schemaLayer();                                 // => int, the catalog layer
Teleframe::routes();                                      // the dashboard group
```

`Teleframe::routes()` registers the Horizon-style dashboard (index + JSON
API) under `teleframe.dashboard.prefix` with `teleframe.dashboard.middleware`.
Call it once from your own provider's `boot()`.

`TeleframeClient` also carries `dispatch(array $request): array` and
`callMany(array $requests): array` for the raw wire.

> **Do not confuse the two `Teleframe` classes.**
> `MeRezaRezaei\Teleframe\Teleframe` (resolved with `app(Teleframe::class)`)
> is the *compose-not-own* facade: `onMessage()`, `on()`, `observe()`,
> `ingest()`, `ingestResponse()`, `run()`, `user()`, `chat()`, `channel()`,
> `route()`, `send()`, `backup()`, `store()`, `storeBackup()`, `app()`,
> `passport()`, `daemon()`, `schemaLayer()`. The one in
> `Laravel\Facades\` is the client facade documented here. See
> [app-framework.md](app-framework.md).

### 4.2 `TStore` — the Telegram-backed object archive

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\TStore;

TStore::put(bucket: 'assets', name: 'invoice.pdf', source: '/path/to/invoice.pdf');
```

Verified methods on the resolved `MeRezaRezaei\Teleframe\Store\StoreService`:
`put()`, `get()`, `exists()`, `size()`, `delete()`, `buckets()`, `objects()`,
`syncRegistry()`, `indexDataChannel()`, `publishByReference()`,
`archiveMessageMedia()`, `reclaimLocal()`, `verify()`, `prune()`.

> **The `TStore` facade's own docblock also advertises a `service()`
> method. It does not exist on `StoreService`**, so `TStore::service()` is a
> fatal, not a forwarder. Every method in the list above is real; that one is
> not.

**With nothing configured** (`teleframe.tstore.account_id` is `0`, the
default): the service **still resolves** — memory index, sync queue, and a
channel factory that throws on first bucket use. So
`TStore::buckets()` and `php artisan teleframe:tstore buckets` work before
any credential exists; `put()`/`get()` throw
`RuntimeException('tstore channel factory needs teleframe.tstore.account_id
(a daemon.accounts account_id) — configure it or bind … yourself.')`.

### 4.3 `Notify` — the notification/logger core

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\Notify;
use MeRezaRezaei\Teleframe\Notify\Level;

Notify::log(Level::Info, 'backup completed', ['bytes' => 48127]);
```

Verified methods on the resolved `NotifyService`: `log(Level $level, string
$brief, array $context = [], ?string $topic = null): string`, `to(string
$topic, string $brief, array $context = []): PendingNotify`, `report(string
$title, array $sections, array $context = []): string`, plus `handle()` and
`registry()`.

> **The `Notify` facade's docblock also lists `service(): NotifyService`.
> There is no `service()` on `NotifyService`**, so `Notify::service()` fatals
> rather than forwarding. Reach the service through the container binding
> `teleclient.notify.service` if you need the object itself.

For a long body with a file attachment, chain the real `PendingNotify` API:

```php
use MeRezaRezaei\Teleframe\Notify\AttachmentFile;

Notify::to('app.warning', 'ingest stalled', ['account_id' => ACCOUNT_ID])
    ->attach(new AttachmentFile(
        name: 'stalled.html',
        path: '/tmp/stalled.html',
        mime: 'text/html',
        bytes: filesize('/tmp/stalled.html'),
    ))
    ->chat('@ops-log', threadId: 12)
    ->send();
```

`PendingNotify`'s verified surface: `topic()`, `level()`, `to()`, `chat()`,
`with()`, `premium()`, `attach()`, `send()`, `queue()`, `toWireArray()`.

> **Correction worth knowing:** the `Notify` facade's own docblock shows
> `->attachFile('stalled.html', $html, 'text/html')` and
> `->threadId(12)`. **Neither method exists.** The real methods are
> `attach(AttachmentFile $file)` and
> `chat(int|string $chat, ?int $threadId = null)`. The sample above is the
> working form.

**With nothing configured** (`teleframe.notify.default_chat` is `''`, the
default): the facade resolves and is callable; **sends throw on first
transport use**, never silent-drop. See Troubleshooting §7.

### 4.4 `Passport` — Telegram Passport login

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\Passport;

// Issue the nonce you hand to the Telegram Web App button.
$nonce = Passport::issueNonce(userId: ACCOUNT_ID);
```

Verified methods: `issueNonce(int $userId): string`,
`login(array $update, int $sessionUserId): LoginResult` (the session id is
required and non-nullable — see the binding note below),
`handleUpdate(array $update, ?int $sessionUserId = null): ?LoginResult`
(null only for updates that carry no credentials; with credentials and no
session it throws), `service(): PassportLoginService`.

**With nothing configured** (`enabled` is `false`, the default): the module
is fully constructible — every seam resolves to a memory/fake default — but
`login()` throws
`PassportLoginException('passport login not enabled (configure
teleframe.passport)')` on first call. Every refusal in the login chain
throws `PassportLoginException`; logging never swallows.

> ### The Passport HTTP routes ship with the package
>
> `DashboardRoutes::register()` registers them — the authenticated pair
> `teleframe.passport.show` / `.start` / `.callback` and the opt-in
> anonymous login pair `teleframe.passport.login.start` / `.login.callback`,
> both served by
> `MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\PassportController`.
> Register the dashboard routes (or the whole group) rather than calling the
> facade from endpoints of your own: the nonce's session binding is enforced
> inside the login service, and the anonymous pair carries the
> `login_rate_limit` throttle. See
> [telegram-passport.md](../telegram-passport.md) §3.2 and
> [passport.md](passport.md).

### 4.5 `TelegramApp` — the declarative app framework

```php
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\Laravel\Facades\TelegramApp;

TelegramApp::register(
    AppDefinition::define('echo-bot', AppMode::Bot, triggers: [
        ['match' => '/start', 'handler' => \App\Telegram\EchoHandler::class],
    ])
);
```

Verified methods on the resolved `AppRuntime`: `register(AppDefinition): string`,
`runCall(string $app, string $call): mixed`, `apps(): array`,
`installCalls(): array`, `registry(): AppRegistry`, `responder(): AppResponder`,
`schedules(): SchedulePublisher`. `AppMode` is a backed enum with cases `Bot`
and `User`.

> **Two corrections worth knowing.** The `TelegramApp` facade's docblock lists
> `define()`, `list()` and `calls()`. **None of the three exist on
> `AppRuntime`** (there is no `__call` to forward them). The real API is
> `AppDefinition::define()` as a static on the definition class,
> `AppRuntime::apps()` (not `list()`) and `AppRuntime::installCalls()`
> (not `calls()`). The same docblock also lists
> `service(): AppRuntime` — **that one is missing from `AppRuntime` too**, so
> `TelegramApp::service()` fatals. The sample above uses the working form.

**With nothing configured** (`teleframe.app.apps` is `[]`, the default):
the runtime resolves, `apps()` returns `[]`, and the provider's default
`transport` resolver throws loud at call time because
`TeleframeClient::bot()`/`user()` have nothing to dial — never a silent
drop.

### 4.6 `StoreBackup` — the DB-dump half of TStore

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\StoreBackup;

$report = StoreBackup::backup();                     // one run, default target
$rows   = StoreBackup::runs('teleframe-backup:default');
$spec   = StoreBackup::schedule();                   // install payload
```

`backup()` and `schedule()` are **real static methods on the facade**, not
`__callStatic` forwards. `backup(?DatabaseTarget $target = null, bool
$queued = false): BackupRunReport` resolves the config-built default
`DatabaseTarget` when you pass nothing; `backup(null)` on the raw service
throws, because the core never fabricates a config-free target.
`schedule(): BackupScheduleSpec` is built from
`teleframe.store.backup.schedule.interval_seconds`. `runs(string $bucket =
'', int $limit = 10): array` and `service(): DatabaseBackupService` forward
to the service root.

**With nothing configured** (`teleframe.store.backup.database.driver` is
`''`, the default): the service **still resolves**, but the first actual
dump throws loud. There is no silent no-op. Also: this service registers a
watchdog by default (`schedule.enabled` defaults to `true`), so a missed
heartbeat will alert on the `backup.missing` topic — which, if
`teleframe.notify.default_chat` is still unset, will make the failure
louder, not quieter.

---

## 5. Artisan commands

Verified against the `$signature` of every class in `src/Laravel/Console/`,
plus `src/Teleframe/Notify/NotifyCommand.php` — **22 commands total**.

| Command | What it does |
| --- | --- |
| `teleframe:login` | Interactive MTProto 2.0 login: user phone, QR code scan, or bot token. **Creates a credential** (see §3). |
| `teleframe:doctor` | Verify live MTProto connectivity. Needs no account by default. `--bot`, `--dc=2`, `--account=` (probe a **stored** `telegram_accounts.id` session instead of a throwaway key), `--bus-timeout=3`. Also runs a bounded Redis/bus probe — see §3.4. |
| `teleframe:poll` | Listen for Bot API updates over long-polling (local development). `--bot=`, `--timeout=30`, `--limit=100`. |
| `teleframe:vault-add-app {label}` | Store a my.telegram.org app (`api_id`/`api_hash`) in the encrypted DB vault. Prompts if the flags are omitted. |
| `teleframe:vault-add-account {label}` | Register a named user/bot account in the encrypted DB vault. `--app=`, `--type=user\|bot`, `--telegram-id=`, `--bot-token=`, `--dc=2`. |
| `teleframe:vault-list` | List vault apps + accounts. Labels only, no secrets. |
| `teleframe:vault-use-default {idOrLabel}` | Point `TELEFRAME_DEFAULT_ACCOUNT_ID` in `.env` at one vault account. |
| `teleframe:ingest` | Consume the Redis bus stream into Postgres truth. `--once`, `--max=0`. Creates the consumer group itself on startup (`XGROUP CREATE … MKSTREAM`, `BUSYGROUP`-tolerant), so a **fresh Redis needs no manual `XGROUP ADD`** — it used to die on its first batch with `NOGROUP`. |
| `teleframe:routing {account}` | **The write path that turns app dispatch ON.** `account` is a **required argument** (positive integer), not a flag. Upserts one `tg_update_routing` row through the `UpdateRoutingRule` model so the `RoutingSettingsObserver` hot-reloads it into the Redis routing table and publishes on the reload channel — no daemon restart. `--mode=act_on` (default; store **and** dispatch app events) or `store_only`; `--peer=` (1=user 2=chat 3=channel), `--peer-id=`, `--account-wide` (required to confirm the account-wide catch-all peer `0,0`), `--priority=0`, `--dry-run`. Without this command nothing in the package could ever write `act_on`, so `onMessage`/`observe()` handlers never fired on the normal path. |
| `teleframe:ingest:gate {account}` | Session-register gate: ingest the big update chunk as a schema-correctness proof. `--only` verifies integrity without persisting the new watermark. |
| `teleframe:backfill` | Quota-aware history backfill into the Postgres truth. `--account=`, `--peer=*`, `--full`, `--discover`, `--state`, `--budget=25`, `--no-persist`, `--restart`. |
| `teleframe:daemon` | Multi-account supervisor: poll → Redis stream → Postgres ingest. `--once`, `--ingest-only`, `--poll-only`. |
| `teleframe:supervise` | The package's **own** process supervisor — keeps the long-running commands alive, so the host writes no `config/horizon.php` and no polling job. Supervises `daemon`, `ingest`, `tstore-work`, `relay` per `teleframe.supervisor.processes`. `--process=*`, `--once`, `--status`, `--ticks=`. |
| `teleframe:relay` | Obfuscated2 relay daemon. `--once`, `--host=`, `--socket=tcp\|websocket`, `--account=`. Non-loopback TCP binds are gated by `TF_RELAY_TCP_SECRET`. |
| `teleframe:backup {action}` | Encrypted Telegram-channel backup vault. `run\|restore\|verify\|status\|prune`. `--set=default`, `--passphrase-file=` (or env `TELEFRAME_BACKUP_PASSPHRASE` or a hidden prompt — NEVER `--passphrase=`, argv is visible in `ps` and shell history; never logged or echoed), `--target=`, `--sample=5`. `status` **fails** on a stale or missing backup (below); `verify` prints a `corrupt` count on both the keyed and the keyless path; `prune` prints a loud console warning if it runs without its Redis lock. |
| `teleframe:tstore {action}` | Telegram-backed encrypted archive **and** the DB-dump surface. Actions: `buckets\|sync\|put\|get\|ls\|index\|verify\|prune\|work\|schedules\|backup\|schedule`. |
| `teleframe:notify {action}` | Notification/logger core. `send\|topics\|work\|watch\|schedules`. |
| `teleframe:app {action}` | Bot/user app framework. `register\|list\|calls\|run`. `--install`, `--app=`, `--call=`. |
| `teleframe:passport {action}` | Telegram Passport. `status\|nonce\|flush\|import`. `--user=`, `--file=`. |
| `teleframe:schema-audit` | Diff committed method schemas against a fresh regeneration. `--write`. |
| `teleframe:schema-update` | Fetch upstream schema sources, regenerate the artifact chain, stamp the layer manifest. `--no-fetch`, `--dry-run`. |
| `teleframe:regenerate` | Regenerate migrations/models/DTOs/factories from tracked TL schemes. `--force`, `--schemas=`, `--out=`, `--ship`, `--prune`. |

Notes:

- `teleframe:schema-update` and `teleframe:regenerate` are **maintainer**
  tools. They rewrite generated artifacts inside the package. A consuming
  app that only calls Telegram does not need them.
- `teleframe:app run` executes one scheduled call now;
  `teleframe:app calls --install` probe-installs the app-call schedules
  through the schedule seam.
- `teleframe:tstore backup` dumps the host DB to a channel;
  `teleframe:tstore backup --prune=N` keeps only the newest N runs.
  `teleframe:tstore schedule` inspects the schedule/watchdog.
- `teleframe:notify watch --register=TOPIC --every=N` registers a
  heartbeat watchdog; `--check` runs the no-show check.
- `teleframe:supervise` is the one command here that is **meant** to sit
  under a process manager forever. Every other long-running command in this
  table is something `teleframe:supervise` is designed to keep alive, so
  running both is redundant — pick one posture, not both. See §6.1a.
- `teleframe:routing` is a **one-shot** command, not a process: run it once
  per account/peer you want dispatching for, and the decision hot-reloads
  into a running daemon. There is **no config key that sets the routing
  mode** — the DB row is the only source of truth, and this command is the
  only thing in the package that writes it.

---

## 6. Scheduled and background work

The package ships **long-running commands, not scheduled jobs.** It does
not register anything in your `routes/console.php` or `Schedule` for you;
you decide how each loop is supervised.

### 6.1 The daemon — the normal production shape

`php artisan teleframe:daemon` is the whole mirror pipeline in one
process:

```
MTProto poll → Redis stream → Postgres ingest
```

It reads accounts from `config('teleframe.daemon.accounts')`, gives each
one an `AccountWorker` (poll + `RedisStreamSink`), supervises them with a
backoff ladder and time-slice rotation, and runs a single
`IngestConsumer` **in the same process** — after each account tick it
drains the Redis stream into Postgres. You do **not** need a separate queue
worker for single-server deployments.

Two behaviours worth knowing before you point a supervisor at it:

- **The `tf_accounts` table wins when it has rows.** If the table exists,
  is migrated and holds rows, the enabled rows become the live account
  set; the `daemon.accounts` config seed only supplies the dialable
  `session_string` for those `account_id`s. Registry rows with no matching
  seed entry are dialed through the host's `session_ref` → `SessionData`
  resolver seam **if one is bound**, and are warned-and-skipped otherwise.
  When the table is empty or not migrated, the config seed governs as-is.
- **It re-seats without a restart.** Every rotation the daemon re-reads the
  `tf_accounts` registry, diffs it against the seated worker set, and seats
  or stops workers for added/removed accounts, leaving unchanged accounts
  running with no reconnect.

Flags: `--once` (one supervision cycle per account, then exit),
`--poll-only`, `--ingest-only`.

> **`--ingest-only` is not what makes the bus drain.** The drain runs on
> **every rotation in the default mode**, not only under `--ingest-only`. It
> used to be reachable only from the ingest-only path, so a default-mode
> daemon produced updates and consumed none: the stream grew forever and
> every table, handler and Centrifugo event stayed empty **while the process
> looked healthy**. If you want to see it work, the line to look for is
> `[bus] drained N entries (M forwarded)`. A drain that throws is contained —
> you get `[bus] drain failed: …` and the daemon keeps polling; a failing
> drain never takes the daemon down.

Run it under a process supervisor (systemd, Supervisor, a container
`restart: unless-stopped`). It is not a cron job. Note the restart-policy
caveat in §7 (*A revoked session spins forever, or the daemon restart-loops*):
a terminal auth-key failure now stops the daemon with a named error instead of
spinning inside it, so check the account it names before letting your process
manager restart it forever.

### 6.1a `teleframe:supervise` — or let the package do it

If you would rather not wire a worker pool at all, the package ships its own
supervisor and you run **one** command:

```bash
php artisan teleframe:supervise                              # every configured process
php artisan teleframe:supervise --process=daemon --process=ingest
php artisan teleframe:supervise --once                       # one tick, print the report, exit
php artisan teleframe:supervise --status                     # ops JSON, no process started
php artisan teleframe:supervise --ticks=3                    # bound the loop (diagnostics)
```

Which processes it supervises comes from `teleframe.supervisor.processes`
(default `daemon,ingest`), out of the closed catalog `daemon`, `ingest`,
`tstore-work`, `relay`. `--process=tstore-work` is how you opt the async
upload drain in. An unknown name is a **loud** failure — a supervisor that
silently supervises nothing looks exactly like a healthy one. Children are
reaped on teardown (SIGINT/SIGTERM), so stopping the supervisor does not
orphan them.

Three things to know before you point your monitoring at it:

- **`--status` only sees its own process.** A supervisor's rows live in the
  memory of the process doing the supervising, and a second `artisan`
  invocation cannot read another process's memory. A standalone `--status`
  run therefore reports `supervised: []` **even while your supervisor is
  running four processes**. The same is true of the dashboard ops snapshot
  (`GET <dashboard.prefix>/ops`) — it renders whatever
  `teleclient.supervisor.state` returns, and that binding is **unbound by
  default**. If you need a cross-process view, bind it yourself to a shared
  reader; otherwise read the supervisor's own stdout table, which is
  refreshed every 5 s tick and is the truthful source.
- **The bus half of the ops snapshot degrades honestly, not loudly.** With
  no usable Redis the snapshot reports `bus: false` rather than 500-ing the
  dashboard. That is a degraded ops view, not a healthy one — check the flag
  before you trust the rest of the snapshot.
- **Backoff and restart caps are config, not policy you inherit silently:**
  base delay 5 s, ceiling 300 s, `max_restarts=0` = unlimited. A process that
  crash-loops will be restarted forever by default. Set
  `TELEFRAME_SUPERVISOR_MAX_RESTARTS` if you would rather it give up and show
  up as `EXHAUSTED`.

This replaces, and does not supplement, the old "paste a `config/horizon.php`
and write a polling job" posture described in [docs/scaling.md](../scaling.md).
The package does **not** write a `horizon.php` into your app and does not
depend on `laravel/horizon`; if you use Horizon for your own queues, the two
coexist as long as you do not point Horizon at `teleframe:*` commands as well.

### 6.2 Ingest on its own

```bash
php artisan teleframe:ingest            # loop until SIGTERM/SIGINT
php artisan teleframe:ingest --once     # one consumeOnce() batch, exit
php artisan teleframe:ingest --max=100  # stop after 100 iterations
```

`--once` is the right shape for a cron entry or a one-shot drain. Without
`pcntl` (Windows) there is no signal handling and `--max` is the only stop,
so the command warns when the loop is unbounded. Prefer the daemon's
built-in consumer unless you are deliberately splitting poll and ingest
across two hosts — in which case run the poll side with
`teleframe:daemon --poll-only`.

### 6.3 Backfill

Backfill is **not** a daemon. It is a bounded, resumable crawl you invoke
on purpose.

```bash
# List every dialog, print it, write nothing.
php artisan teleframe:backfill --account=ACCOUNT_ID --discover

# Crawl one peer.
php artisan teleframe:backfill --account=ACCOUNT_ID --peer=@your_channel

# Crawl every dialog down to message id 1.
php artisan teleframe:backfill --account=ACCOUNT_ID --full
```

Peers come from `--peer` (repeatable: `"@username"`, numeric id, `-100…`)
or, with `--full`, from **every** dialog the account can see. Full history
needs the dialogs path, not `getDifference`: Telegram bounds the update
window, so an account's whole history can only be recovered by enumerating
dialogs (`messages.getDialogs`) and crawling each peer down to message id 1.

`--full` keeps working the queue until it drains (the per-pass budget still
halves for live-traffic headroom). Durable per-peer cursors let a throttled
crawl **resume** instead of restarting every peer at offset 0 and never
reaching message id 1. `--no-persist` is the explicit opt-out and it loses
progress on exit. `--restart` forgets every durable cursor for the account.

Every fetched page is stored through the normal ingest surface as a
`messages.messages` payload under the account's tenancy — deliberately not
`ingestResponse`, because the route-dedup key would collapse distinct pages
with identical shapes. The write path is keyed `(account_id, peer_type,
peer_id, id)`, so same-id messages from different chats never overwrite
each other.

### 6.4 The relay

`php artisan teleframe:relay` is a separate long-running process serving
obfuscated2 clients, terminating them at the account's real Telegram DC.
`--socket=websocket` runs the vault terminal instead (a `wss://…/apiws`
browser leg that takes a relay ticket). Treat it like the daemon: a
supervised process, not a cron entry.

### 6.5 Cron-shaped things that really are cron-shaped

- `teleframe:tstore backup` — the documented posture is literally
  `0 0 * * * cd $APP && php artisan teleframe:tstore backup`.
- `teleframe:backfill --full` — run it from cron/scheduler on your own
  cadence, respecting `--budget`.

---

## 6.6 The Temporal worker — `bin/teleframe-worker`

Arming a schedule is only half of it. A schedule that is armed and has no
worker polling its task queue executes **nothing, forever, with no error** —
`armed … recurring every …` still reads healthy while the job silently never
runs. This section exists because that failure is invisible from both sides.

The worker ships in the dist tarball at
`vendor/merezarezaei/teleframe/bin/teleframe-worker` (`bin/` is no longer
excluded wholesale; the dev scripts are excluded individually). You do not
copy it in from a repo checkout.

```bash
# From your app root — the env var must be visible to BOTH processes.
export TELEFRAME_TEMPORAL_TASK_QUEUE=teleframe
php artisan teleframe:tstore schedules --install    # arm
php vendor/merezarezaei/teleframe/bin/teleframe-worker
```

**The task queue is one knob, and it is an env var, not a config key.**
`TELEFRAME_TEMPORAL_TASK_QUEUE` is read by *both* sides: the four schedule
publishers resolve it through `TemporalSchedulePublisher::defaultTaskQueue()`
(`tstore`, `notify`, `app`, `store.backup` — all four share one default, so
they cannot end up on different queues behind one worker), and the worker
resolves the same variable on the serve side. The precedence on the worker is
**`--task-queue` flag → `TELEFRAME_TEMPORAL_TASK_QUEUE` → `'default'`**.

The worker prints which of the three it used, on startup:

```
Teleframe worker: polling task queue 'teleframe' (from TELEFRAME_TEMPORAL_TASK_QUEUE) for 3 workflow type(s) and 1 activity type(s). Ctrl+C to stop.
Teleframe worker: if the schedules were armed on a DIFFERENT queue, nothing will ever execute here and nothing will say so. Compare this queue with the one your arm command reported.
```

> ### 🔴 If you see nothing happening: compare the two queue names
>
> **A worker on the wrong task queue blocks forever with no error, no log and
> no exit.** It polls an empty queue and reports healthy, while the arm
> command's `armed … recurring every …` line also reads healthy. The two
> lines are the only evidence that exists, so **compare the queue name the
> worker printed against the queue your arm command reported**:
>
> - worker says `(from TELEFRAME_TEMPORAL_TASK_QUEUE)` → the env var is set
>   **for the worker process**. If your arm ran from a shell, a cron entry or
>   a container that did not have it, the arm side silently used `'default'`
>   instead. Export it in the environment the *arm* command runs in, not just
>   the worker's unit file.
> - worker says `(from default)` → the env var was unset or blank **in the
>   worker's own environment**. If your arm command reported a non-default
>   queue, the two sides disagree and nothing will run.
> - worker says `(from --task-queue)` → a flag overrode the env var on the
>   serve side only. Re-arm with the same queue, or drop the flag.
>
> The fix is always to make one `TELEFRAME_TEMPORAL_TASK_QUEUE` value visible
> to both processes, then re-arm. There is deliberately **no config key** for
> the task queue on the tstore or notify seams — the queue used to be
> configured in two places and hardcoded in two others, and nothing
> reconciled them, so one worker served at most half of what the host believed
> it had armed. A host needing a different queue binds its own
> `TemporalScheduleClient`, which wins verbatim.

The worker serves the workflow types the schedule producers emit
(`teleframe:tstore backup`, `teleclient.notify.check-no-show`, and one
`telegram.app.run-call {app} {call}` type per declared `(app, call)` pair —
the type embeds both names, so declare the pairs you intend to run) plus the
`teleframe.store.backup-dump` activity. It prints the exact set it serves on
startup, and refuses to start **loudly and by name** (exit 1) if the SDK is
missing rather than pretending.

Run it under a process manager. There is no live Temporal server in this
repo's test suite, so the schedule path is pinned by tests with an injected
client — arming against a real server is something you verify yourself, which
is precisely why the queue-comparison warning above exists.

---

## 7. Troubleshooting the first run

Every message below is quoted from the shipped source.

### `Telegram API ID and API Hash are required...`

`RuntimeException` from `TeleframeClient::user()`. Either
`TELEGRAM_API_ID` / `TELEGRAM_API_HASH` are unset in `.env`, or you did not
register a vault app and pass `--app=LABEL`. Register with
`teleframe:vault-add-app`, or set the two env keys.

### `tstore channel factory needs teleframe.tstore.account_id...`

`RuntimeException` from the channel factory bound at
`src/Laravel/Providers/TeleframeServiceProvider.php:475`. Set
`teleframe.tstore.account_id` to a real `account_id` from
`teleframe.daemon.accounts` (or point `TELEFRAME_TSTORE_ACCOUNT` at it), or
bind `TstoreCommand::CHANNEL_FACTORY_KEY` yourself. Until then
`TStore::buckets()` and `teleframe:tstore buckets` still work — only
`put`/`get` need the account.

### Notify silently doing nothing — or throwing on send

`teleframe.notify.default_chat` defaults to `''`. The facade resolves and
is callable, so nothing looks wrong until you send; the send then throws on
first transport use. This is deliberate ("loud, never silent-drop"), but
it looks like a hang if you don't know. Set `TELEFRAME_NOTIFY_CHAT` to a
`@channel` you actually administer, or per-call with
`PendingNotify::chat('@your_channel')`.

### `passport login not enabled (configure teleframe.passport)`

`PassportLoginException` from `PassportLoginService::login()`. Set
`teleframe.passport.enabled = true`, `private_key_path` to a `0600` PEM,
and — in production — `nonce_store` to a PSR-16 cache key so the nonce
survives from the issuing web session to the webhook. With the default
`nonce_store = ''` the registry is in-process, so a second PHP worker (or a
queue) will not see the nonce.

### No Redis / `teleframe:ingest` or `teleframe:daemon` cannot connect

The bus reads and writes through the `illuminate/redis` connection named by
`teleframe.bus.connection` (default `default`) using
`teleframe.bus.redis_client` (default `predis`). **The package does not ship
a Redis client** — `predis/predis` is in `require-dev` and `suggest`, not
`require` (see §1). Run `composer require predis/predis`, or set
`TELEFRAME_REDIS=phpredis` and install `ext-redis`. You also still have to
configure the host's own Redis service; the package does not run one. The
stream, group and reload-channel names come from `StreamSchema` and are
deliberately **not** configurable strings: do not rename them, they are
wire-compatible with the archived `teleclient` consumer.

### "No session" — `Telegram API ID … required` or an empty `authKey`

`Teleframe::user()` resolves its session from, in order: the `$session`
argument, then `teleframe.user_session` (`TELEGRAM_USER_SESSION`). If you
never ran `teleframe:login`, that is `null` and you are unauthenticated.
Run `teleframe:login` (§3) and either answer the `.env` prompt or
`--account=LABEL` to the vault.

If you called `teleframe:login` in a different directory than your app, it
wrote to *that* project's `.env`. `EnvFile::upsert(base_path('.env'), …)`
is app-relative.

### `teleframe:backup` uploads nothing

`teleframe.backup.sets.*.paths` is empty by default and **the runner
refuses to upload with no paths**. Add `base_path('…')` entries (the config
comments suggest `env('TELEFRAME_BACKUP_PATHS')`). Also check
`teleframe.backup.driver`: the default is `telegram`. The accepted set is
exactly `memory`, `telegram` and `s3`; anything else throws at bind time
naming all three. Two of those are **durable**: `telegram` (a private
channel) and `s3` (the `teleframe.tstore.s3` gateway — the one tier that
keeps the backup off Telegram entirely). `memory` is offline and proves
nothing — reach for it only with `TELEFRAME_BACKUP_ALLOW_VOLATILE=1`. If
the driver is `telegram`, point `teleframe.backup.account` at a
`daemon.accounts` `account_id`; if it is `s3`, configure
`teleframe.tstore.s3` instead (its default endpoint is a **local MinIO**,
`http://127.0.0.1:9010`, and `us-east-1` is MinIO's region, not AWS's).

### `StoreBackup::backup()` throws on first use

`teleframe.store.backup.database.driver` is `''` by default. The service
constructs; the dumper throws loud on first use. Set the `database.*` keys
(or leave them `''` to read the host's DB config at run time). Remember
there is deliberately **no** password config key — the dump subprocess reads
`MYSQL_PWD` / `PGPASSWORD` from its own environment.

### The `tg.miniapp` guard rejects everything

Two causes: `teleframe.bot_token` unset (it is the HMAC key), or
`auth_date` older than `teleframe.identity.miniapp_auth_max_age` (default
1800 s). A stale initData blob replayed from a test fixture is a replay, and
is meant to be rejected.

### `teleframe:supervise --status` says nothing is running

It is not lying about *your* supervisor — it cannot see it. A supervisor's
state lives in the memory of the supervising process, so a second `artisan`
invocation always reports `supervised: []`. Same for the dashboard ops page,
whose `teleclient.supervisor.state` binding is unbound by default. Use the
supervisor's own stdout table, or bind a shared reader. See §6.1a.

### `teleframe:backup status` says STALE — or `prune` warns it ran without the lock

Two failure modes that used to be invisible, because the engine binds
`NullLogger` by default so nothing reached your logs either.

**`status` now fails instead of reporting a green inventory.** It used to
print the manifest's `created` timestamp and return `SUCCESS`
unconditionally — nothing in the codebase compared that timestamp to now, so
a backup cron that had been failing for months produced no line, no exit
code, and nothing at all. Now:

- **no manifest at all** ⇒ `FAILURE`, with the message that no backup has ever
  been written for the set, so there is nothing to restore;
- **newest manifest older than `teleframe.backup.max_age_hours`** (default
  **24** h) ⇒ `FAILURE`, printing both the age and the bound. Every hour above
  the bound means the run is failing or not scheduled;
- the success line now carries `age_hours` and `max_age_hours`, so a healthy
  run tells you how much headroom you have.

Tune it for your own cadence — a weekly cron needs a bound above 24 h:

```php
// config/teleframe.php
'backup' => ['max_age_hours' => 24 * 9],
```

**`prune` warns loudly when it runs without the lock.** The prune's Redis lock
is what prevents deleting chunks an in-flight backup is still uploading. When
Redis is unavailable the prune **proceeds anyway** — and that degradation used
to reach you only as a PSR-3 warning, which the `NullLogger` default swallowed,
so the command printed a clean success line and said nothing. It is now
printed **on the console**, where no logger can silence it:

```
WARNING: prune set=default ran WITHOUT the lock (Redis unavailable) — an in-flight backup may now reference deleted chunks.
```

If you see that, treat the next restore as suspect until a fresh full `run`
completes and `verify` comes back clean.

**`verify` reports `corrupt` on both paths.** The keyless path used to print
only `checked`/`ok`/`missing`, so a chunk that existed but had rotted was
invisible. It now prints `corrupt` either way, and the keyless exit code
already fails on `missing > 0 || ok < checked`.

### `teleframe:notify.queue=amqp` is refused, or notify defaults to your Laravel queue

Two different symptoms, two different causes:

- **`refusing to dial … with the stock guest/guest broker identity`** — the
  AMQP driver refuses to arm against a **remote** broker with stock
  credentials, by name. `teleframe.notify.amqp.user` / `.password` (and the
  `tstore` equivalents) default to **empty**, which is *treated* as
  `guest`/`guest` at the dial, and `guest`/`guest` is only acceptable on a
  loopback broker. Set `TELEFRAME_NOTIFY_AMQP_USER` /
  `TELEFRAME_NOTIFY_AMQP_PASSWORD` (or the `TELEFRAME_TSTORE_AMQP_*` pair)
  to a dedicated broker identity.
- **Notify keeps landing in your `failed_jobs` table while you configured a
  broker** — `queue=auto` used to prefer the host's Laravel queue, so a
  configured broker was silently ignored. It now tries **RabbitMQ first**
  when `ext-amqp` is loaded and a broker host is set, and only then the
  Laravel queue. Confirm what actually resolved: `teleframe:notify work`
  prints the resolved driver, and the same description is bound as
  `teleclient.notify.queue.report`. Pin it explicitly (`amqp` / `laravel` /
  `sync`) rather than relying on `auto` once you have decided.

See §2.2.

### A revoked session spins forever, or the daemon restart-loops

**A revoked or unregistered auth key is TERMINAL, not transient.** It used to
be treated as a retryable condition, and the consequences were the worst kind
of silence: `pollUser()` spun at its 2-second backoff floor **forever**, with
no exception, no log line and no PSR-3 logger involved, and the daemon then
printed an unconditional `Daemon stopped.` and exited `1` — which under the
`Restart=always` / `Restart=on-failure` policy every daemon deployment uses is
an **unbounded restart loop**.

That is fixed. `UpdatePollerService::errorLoopAction()` now returns
`rethrow` for an `AuthKeyException` by name, and the daemon:

- prints the **per-account** errors that stopped it,
- tells you plainly: *"A revoked or expired auth key cannot recover by
  retrying — re-run `php artisan teleframe:login` for that account."*,
- and prints `Daemon stopped.` **only on a clean exit**, so that line is now a
  signal rather than noise.

> **Restart-policy warning.** Because a revoked session now stops the daemon
> instead of spinning in it, any process manager configured to restart on
> **any** non-zero exit will still restart it — the loop is bounded by the
> account's real state, not eliminated. Fix the session first: re-run
> `teleframe:login` for the account the daemon names. Do not "fix" this by
> adding restart backoff; the exit is telling you the truth.

`teleframe:doctor` always probes with a **throwaway** session by default — it
is a network and `api_id`/`api_hash` check, and a green run says **nothing**
about whether your stored session is still valid. Use
`teleframe:doctor --account=<id>` to probe the actual stored session; see
§3.4a.

### `teleframe:ingest` dies with `NOGROUP No such key 'tg:stream:updates'`

It should not any more: `IngestConsumer::ensureGroup()` creates the consumer
group itself at startup with `XGROUP CREATE … MKSTREAM`, and treats
`BUSYGROUP` as success. If you are reading this, you are running a build from
before that change — the single invariant is now one method, so the ingest
command and the daemon cannot drift apart again.

### The Redis stream grows without bound / OOMs

It no longer should. `RedisStreamSink` appends with `maxlen` — the update
stream is trimmed to roughly **50,000** entries (`RedisStreamSink::DEFAULT_MAXLEN`,
approximate `MAXLEN ~`, O(1)) and the dead-letter stream to **10,000**
(`IngestConsumer::DL_MAXLEN`). The strikes hash no longer gains a permanent
field per successful entry. If your stream is still growing without bound,
you are on a pre-change build. Note the update stream is a **buffer, not an
archive**: anything older than ~50k entries is gone, so do not treat it as
your update history — Postgres is the truth.

### Dead-letter replay and consumer-crash reclaim say "unsupported"

Those are real in production now. `LaravelRedisAdapter` implements `xpending`,
`xautoclaim`, `xrange`, `xdel` and `hdel`, and `xadd` accepts
`['maxlen' => N]`. That is what makes reclaiming a crashed consumer's
in-flight entries, replaying the dead-letter stream, and the ops dead-letter
view work against a real Redis — previously the tests passed on the in-memory
double while production had no such verbs. If you are on a build where these
report unavailable, you are pre-change.

### The dashboard 403s

`teleframe.dashboard.middleware` defaults to `['web', 'auth']` — the host's
own login. A token-based host should pass its own guard:
`Teleframe::routes(prefix: 'api', middleware: ['auth:sanctum'])`. Remember
the group always also carries `tg.readonly`.

### `Route::telegramWeb()` 404s, or 401s

Two different failures, and the status code tells you which:

- **404** — `teleframe.telegram_web.enabled` is `false` by default, and the
  built static bundle under the package's `tgweb/dist` is **not shipped**
  (see the boundaries section of the root README). Enable it and run
  `bin/build-tgweb.sh`.
- **401 `{"message":"Unauthenticated."}`** — the route is up, but
  `tg.auth` refused the request because there is no authenticated Laravel
  user behind it. This is the intended deny-by-default posture, not a bug.
  A standalone session-less deployment must opt out explicitly with
  `->withoutMiddleware('tg.auth')`.

### The engine prints nothing, ever

That is correct. `teleframe.logging.logger` defaults to `''` ⇒
`NullLogger`. Bind `Psr\Log\LoggerInterface` in your own service provider
(it always wins over the config value) or set `TELEFRAME_LOGGER` to a
PSR-3 class FQCN.

---

## Where to go next

- [usage/README.md](README.md) — index of the five feature areas
- [quickstart.md](../quickstart.md) · [index.md](../index.md)
- The root README's **What version one does not do yet** section — read it
  before you plan around a feature
