# Usage — STORE + BACKUP: TStore archive, backup vault, credential vault, DB store-backup

> **Release area 2 of 5.** Four surfaces, one posture: everything stays
> **constructible and loud-on-use** — unconfigured seams throw on first real
> use, never silent-drop. Detailed concepts for the two older surfaces live in
> [store.md](../store.md) and [backup.md](../backup.md); this page is the
> usage reference for the shipped calls.

---

## 1. TStore — the Telegram-backed encrypted object store

TStore is an object store whose "buckets" are Telegram channels (each bucket =
one channel/dialog of an MTProto account). Objects are chunked, sealed
(passphrase-protected) by default, and verified on read.

**Service** — `MeRezaRezaei\Teleframe\Store\StoreService`, resolved by the
`TStore` facade (`teleclient.tstore.service`) and by the compose facade
`Teleframe::store()` — one shared instance:

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\TStore;

TStore::put('media', 'avatar.jpg', '/tmp/avatar.jpg');              // raw
TStore::put('media', 'secret.db', '/tmp/db.sql', 'sealed', 'hunter2'); // sealed

$bytes = TStore::get('media', 'secret.db', 'hunter2');              // string
TStore::exists('media', 'avatar.jpg');  // bool
TStore::size('media', 'avatar.jpg');    // ?int
TStore::delete('media', 'avatar.jpg');  // void

TStore::buckets();                       // registered bucket titles
TStore::objects('media');                // object rows
TStore::syncRegistry(['media', 'docs']); // (re)build the channel registry
TStore::indexDataChannel('media');       // rebuild the in-channel index
TStore::verify('media', 'secret.db', 'hunter2'); // chunk verification report
TStore::prune();                         // tombstone sweep
```

Sealed mode derives per-object keys from the passphrase — the same bytes in
means the same bytes out. There is **no key recovery**: a lost passphrase is a
lost object (see also the rotation trap in §2).

### Re-publishing by hash, and freeing the local file

Once a document is indexed, its Telegram `file_reference` **is** the reference.
Re-publishing it to another channel needs no bytes at all:

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\TStore;

TStore::publishByReference('media', $msgId, $targetPeer, 'caption');
// -> posts inputMediaDocument + inputDocumentFileLocation; uploads NOTHING
```

A message with no `tf_store_media` row throws a `RuntimeException` — it never
silently re-uploads, because a quiet re-upload is exactly the cost this avoids.

Because the hash row now stands in for the bytes, the **local** copy can be
freed — the "keeping storage of laravel free" half:

```php
use MeRezaRezaei\Teleframe\Store\ReclaimOutcome;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;

$outcome = TStore::reclaimLocal(
    'local',
    '/storage/app/media/42.jpg',
    ['bucket' => 'media', 'msg_id' => $msgId],
    ReferencePolicy::dropLocalAfterIndex(),   // default is retain-local: inert
    unlink: fn (string $disk, string $path): bool => Storage::disk($disk)->delete($path),
    dryRun: true,                             // decide first, delete later
);

$outcome->state();      // 'retained' | 'reclaimed' | 'refused'
$outcome->freedBytes(); // true ONLY when bytes were really deleted
```

Three things to know before turning this on:

- **The default is inert.** With no policy passed, nothing is ever deleted, so
  adding the call changes no behaviour until you opt in.
- **The index row is checked first, and the check is the safety property.** A
  missing row throws; a row with an empty `file_reference` or a non-positive
  `doc_id` is `refused` and **nothing is touched**. Until that row is durable
  the local file is the only copy of those bytes.
- **A `refused` outcome means the file is still there.** `freedBytes()` is true
  only for a real deletion — a dry run decides *without* deleting and will
  never claim otherwise.

### `teleframe:tstore`

```bash
php artisan teleframe:tstore buckets
php artisan teleframe:tstore sync --buckets=media,docs
php artisan teleframe:tstore put media avatar.jpg /tmp/avatar.jpg            # raw
php artisan teleframe:tstore put media secret.db /tmp/db.sql --mode=sealed --ask-passphrase
php artisan teleframe:tstore get media secret.db --ask-passphrase --out=./restore.sql
php artisan teleframe:tstore ls media
php artisan teleframe:tstore index media
php artisan teleframe:tstore verify media secret.db --ask-passphrase
php artisan teleframe:tstore prune
php artisan teleframe:tstore work --max=10        # drain the async queue
php artisan teleframe:tstore schedules            # probe/inspect schedule wiring
php artisan teleframe:tstore backup [--prune=N]   # DB store-backup — see §4
php artisan teleframe:tstore schedule             # inspect the store-backup schedule/watchdog
```

Options: `--mode=raw|sealed`, `--passphrase-file=PATH`, `--ask-passphrase`,
`--mime=`, `--queued`, `--buckets=…`, `--max=1`, `--out=`, `--prune=backup`.
**Passphrase handling**: env `TELEGRAM_TSTORE_PASSPHRASE`,
`--passphrase-file=PATH`, or a hidden prompt — `--ask-passphrase` arms that
prompt (it only fires on a real TTY, so cron never blocks on it); there is
**no** `--passphrase=` option and passing one is a leaked secret, because argv
is world-readable in `ps` and lands in shell history. `--prune=N` keeps
the newest N backup runs in the bucket and deletes the rest (`0` = delete all).

**Config** (`teleframe.tstore.*`, env `TELEFRAME_TSTORE_*`): `account_id`
(required for real use; the MTProto account whose dialogs host the buckets),
`passphrase`, `bucket_prefix` (default `teleframe-store:`), `cache`/`cache_ttl_seconds`/
`cache_max_bytes`, `premium`, `queue` (`sync` inline default | `amqp`; `amqp`
defers uploads to `teleframe:tstore work` on the durable `tstore.upload`
queue, with bounded requeue then the real `tstore.upload.dl` dead-letter
queue, and the source temp file unlinked only after a **successful** upload),
and `amqp.{host,port,vhost,user,password,heartbeat,connect_timeout,read_timeout,write_timeout,publish_retry_budget_seconds}`.
`amqp.user`/`amqp.password` default to **empty**, which is *treated* as
`guest`/`guest` at the dial — and that stock pair is now **refused against
any non-loopback broker** by name. In non-dev environments AMQP must carry
explicit `TELEFRAME_TSTORE_AMQP_USER/PASSWORD`.

Unconfigured (no `account_id`): service resolves with a memory index + sync
queue; scaffolding commands (`buckets`…) work, and `put`/`get` throw on first
transport use.

## 2. Backup vault — encrypted Telegram-channel file vault

The backup vault uploads a configured set of paths as an encrypted, chunked
archive into a Telegram channel, with manifest-first restore and sampling
verify. See [backup.md](../backup.md) for the format (Argon2id +
XChaCha20-Poly1305, chunked 4 MiB, manifest version 1) and **the passphrase
rotation trap**: the dedup keys derive from the plaintext hash + a per-set
salt, so rotating the passphrase on an existing set produces a mixed-key set
no single passphrase can fully restore — to rotate, use a **new set id**.

### `teleframe:backup`

```bash
php artisan teleframe:backup run      --set=default --passphrase-file=./vault.key
php artisan teleframe:backup restore  --set=default --passphrase-file=./vault.key --target=./restored
php artisan teleframe:backup verify   --set=default --passphrase-file=./vault.key --sample=5
php artisan teleframe:backup status   --set=default
php artisan teleframe:backup prune    --set=default
```

- **Passphrase handling**: env `TELEFRAME_BACKUP_PASSPHRASE`,
  `--passphrase-file=PATH`, or a hidden prompt (a real TTY only, so cron never
  blocks on it) — NEVER `--passphrase=`; argv is visible in `ps` and in shell
  history, so it is never logged or echoed.
- `restore` writes into `--target` (a directory).
- The set holds `paths` (runner requires ≥ 1 path) under
  `teleframe.backup.sets.<id>`; `driver` is one of `memory`, `telegram` or
  `s3`, defaults to `telegram` (needs `account`; `memory` is the volatile
  offline driver, `s3` a durable bucket); `chunk_size` defaults to 4194304.

**Programmatic** — the compose facade resolves a vault factory per set:

```php
$vault = app(MeRezaRezaei\Teleframe\Teleframe::class)->backup('default');
// BackupRunner::run($setConfig, $vault, $passphrase) / Restorer / Verifier / Pruner
```

## 3. Credential vault (`teleframe:vault-*`)

Store `my.telegram.org` app credentials and user/bot accounts **encrypted** in
the DB (`MeRezaRezaei\Teleframe\Vault\{Vault, TelegramApp, TelegramAccount}`);
the CLI writes them and lists labels only — secrets never print.

```bash
php artisan teleframe:vault-add-app teleframe --api-id=123 --api-hash=abc
php artisan teleframe:vault-add-account main --app=teleframe --type=user --telegram-id=900 --dc=2
php artisan teleframe:vault-add-account supportbot --type=bot --bot-token=123:ABC
php artisan teleframe:vault-list
php artisan teleframe:vault-use-default 900        # numeric Telegram user id, or a label
```

- `vault-add-app`: `{label} {--api-id=} {--api-hash=}` (prompts otherwise).
- `vault-add-account`: `{label} {--app=} {--type=user|bot} {--telegram-id=}
  {--bot-token=} {--dc=2}`; type `user` requires `--app`; `bot` requires
  `--bot-token` or a session.
- `vault-use-default` writes `TELEFRAME_DEFAULT_ACCOUNT_ID` into `.env`
  (`EnvFile::upsert`) — the numeric user id is the canonical identity.

**Reading back** (service, not facade — there is no Laravel `Vault` facade):

```php
use MeRezaRezaei\Teleframe\Vault\Vault;

$vault = app(Vault::class);
$app  = $vault->app('teleframe');          // ?TelegramApp
$acct = $vault->account('main');           // ?TelegramAccount
$byId = $vault->accountByUserId(900);      // ?TelegramAccount
$def  = $vault->defaultAccount();          // ?TelegramAccount (TELEFRAME_DEFAULT_ACCOUNT_ID)
$creds = $acct->credentials();             // ['api_id','api_hash','session','bot_token','dc_id','type']
```

`defaultAccount()` resolves the numeric `TELEFRAME_DEFAULT_ACCOUNT_ID` first,
then the label fallback, then null.

## 4. DB store-backup — dump the host database into TStore

The store-backup orchestrator dumps the host DB, `sealed`-puts the dump into
the backup bucket, writes a run ledger, and heartbeats the notify watchdog
(the `backup` heartbeat; a missed run alerts on `backup.missing`).

```bash
php artisan teleframe:tstore backup          # one run, then show the report
php artisan teleframe:tstore backup --prune=30   # keep newest 30 runs
php artisan teleframe:tstore schedule        # schedule/watchdog wiring status
```

**Facade** — `MeRezaRezaei\Teleframe\Laravel\Facades\StoreBackup`
(accessor `teleclient.store.backup.service`); `backup()` and `schedule()` are
real static methods (the core never fabricates a target config-free):

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\StoreBackup;

$report = StoreBackup::backup();                    // default target; BackupRunReport
$report = StoreBackup::backup($target, queued: true); // async-queue upload
$rows   = StoreBackup::runs('teleframe-backup:default', 10); // ledger rows
$spec   = StoreBackup::schedule()->describe();      // install payload (interval 86400 default)
$svc    = StoreBackup::service();                   // DatabaseBackupService
```

**Programmatic targets** — `MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget`
carries **no password field** (deliberate): DB credentials are env-at-run only
(`MYSQL_PWD` / `PGPASSWORD` in the forked dump process), never argv, config,
manifests, or logs.

```php
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;

$target = new DatabaseTarget(
    driver: 'pgsql', database: 'app', host: '127.0.0.1',
    port: 5432, user: 'app', socket: null, tables: null, // null tables = whole DB
);
app(DatabaseBackupService::class)->backup($target);
```

**Restore is programmatic only in this slice.** `DatabaseRestorer`
(`MeRezaRezaei\Teleframe\Store\Backup\DatabaseRestorer`) feeds the dump back
into the DB client binary over stdin, but **there is no CLI or facade for it
yet** — the restore surface is TBD. What exists today:

```php
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseRestorer;

$source = fopen('/path/to/dump.sql', 'rb');   // stream resource OR readable file path
$result = (new DatabaseRestorer)->restore($target, $source); // RestoreResult
// non-zero client exit → BackupException with the last 4 KiB of stderr
```

`teleframe:backup restore` in §2 is the **file-vault** restore
(`Backup\Restorer` → target dir), which is a different surface.

**Config** (`teleframe.store.backup.*`, env `TELEFRAME_STORE_BACKUP_*`):
`database.{driver,host,port,database,user,socket,tables}` (driver `''` =
unconfigured → constructible, throws loud on first dump),
`target.{account_id,bucket,peer_type,passphrase,mode,members}` (default target
row; `targets` = wave-2 multi-destination rows, non-owned peers require
`mode=sealed`), `schedule.{enabled,interval_seconds,publisher,dsn,watchdog_expect_multiplier}`
(daily default, one-line cron posture:
`0 0 * * * cd $APP && php artisan teleframe:tstore backup`), `spool_dir`,
`queue` (`sync` default | `amqp`).

---

Related: [notify.md](notify.md) (the `backup` heartbeat / `backup.missing`
watchdog the store-backup talks to) · [mtproto.md](mtproto.md) (the wire under
the TStore channels) · [store.md](../store.md) · [backup.md](../backup.md).