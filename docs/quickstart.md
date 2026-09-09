# Quickstart — 5 Recipes

Copy-paste recipes against real Teleframe APIs. Every snippet assumes `use MeRezaRezaei\Teleframe\Laravel\Facades\TF;` unless shown.

> **Phase 2 + 3 consumers.** For the merged consumer layer the public face is
> `MeRezaRezaei\Teleframe\Teleframe` (composes `ingest` / `onMessage` /
> `route` / `run` / `backup`) with ingest-only `Teleclient` retained. See
> [handlers](handlers.md), [ingest](ingest.md), [bus](bus.md).

---

## (a) Send a message as a Bot (HTTP Bot API)

**When:** fire-and-forget notifications from any Laravel code — zero setup beyond the token.

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\TF;

TP::bot()->sendMessage(chatId: '@mychannel', text: 'Deploy finished ✅');

// custom token at runtime (multi-bot):
TP::bot('123456:ABC-DEF...')->sendMessage(chatId: 987654321, text: 'Direct ping');
```

**.env:** `TELEGRAM_BOT_TOKEN=...`

---

## (b) Log in as a User, then send via MTProto

**When:** act *as the user's own account* (channels, DMs, history) over native MTProto 2.0.

```bash
# 1. One-time wizard: phone → code → (2FA if set) → session string
php artisan teleframe:login --phone=+1234567890
#    ...confirm saving the session to .env as TELEGRAM_USER_SESSION
```

```php
// 2. Anywhere in Laravel — reads TELEGRAM_USER_SESSION + API creds from .env
$user = TP::user();

$user->sendMessage(peer: '@mychannel', text: 'Hello from my own account!');

// any raw schema method (Layer 227+):
$history = $user->call('messages.getHistory', [
    'peer' => ['_' => 'inputPeerChannel', 'channel_id' => 123456, 'access_hash' => 0],
    'limit' => 10,
]);
```

**.env:** `TELEGRAM_API_ID=`, `TELEGRAM_API_HASH=`, `TELEGRAM_USER_SESSION=` (wizard writes the last one for you). QR login: `php artisan teleframe:login --qr`.

---

## (c) Protect a Mini App route

**When:** serve your Telegram Mini App backend; reject forged requests before they reach you.

```php
use Illuminate\Support\Facades\Route;

// HMAC-SHA256-validated; user exposed as plain array:
Route::get('/miniapp/me', fn (\Illuminate\Http\Request $r) => [
    'hello' => $r->attributes->get('telegram_user')['first_name'],
])->middleware('tg.miniapp');
```

The middleware reads the `X-Telegram-Init-Data` header, verifies Telegram's HMAC signature, and stores the decoded user in the `telegram_user` request attribute. Invalid signature → 403.

**.env:** `TELEGRAM_BOT_TOKEN=` (the bot that owns the Mini App).

---

## (d) Decrypt Telegram Passport credentials (KYC)

**When:** a user submits identity documents via Passport to your bot.

```php
use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;

$cred = $update['message']['passport_data']['credentials'];

$decrypted = PassportDecryptor::decryptCredentials(
    encryptedData:   $cred['data'],     // base64
    encryptedSecret: $cred['secret'],   // RSA-encrypted to your public key
    privateKeyPem:   file_get_contents(storage_path('app/keys/passport_private.pem')),
    hash:            $cred['hash'],
);

$name = $decrypted['personal_details']['first_name']; // verified identity data
```

**.env:** none — needs the RSA keypair registered with @BotFather (see [telegram-passport.md](telegram-passport.md)).

---

## (e) Stream a Storage file to MTProto (upload parts)

**When:** upload files to Telegram straight from S3/MinIO/local disk — never fully in memory (512 KB parts, big-file path automatic > 10 MB).

```php
$user = TP::user();
$fileId = random_int(1, PHP_INT_MAX);
$md5 = hash_init('md5');
foreach (\MeRezaRezaei\Teleframe\Laravel\Media\StorageMedia::readFromDisk('exports/report.pdf', disk: 's3') as $part) {
    hash_update($md5, $part['bytes']);
    $user->call($part['is_big'] ? 'upload.saveBigFilePart' : 'upload.saveFilePart', [
        'file_id' => $fileId, 'file_part' => $part['part_index'],
        'file_total_parts' => $part['total_parts'], 'bytes' => $part['bytes'],
    ]);
}
$file = ['_' => 'inputFileBig', 'id' => $fileId, 'parts' => $part['total_parts'], 'name' => 'report.pdf'];
if (!$part['is_big']) {
    $file = ['_' => 'inputFile', 'md5_checksum' => hash_final($md5)] + $file;
}
$user->sendMedia('@mychannel', [
    '_' => 'inputMediaUploadedDocument', 'file' => $file, 'mime_type' => 'application/pdf',
    'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'report.pdf']],
], 'Monthly report');
```

**.env:** `TELEGRAM_API_ID=`, `TELEGRAM_API_HASH=`, `TELEGRAM_USER_SESSION=`

---

Next: [index](index.md) · [Bot API](bot-client.md) · [User MTProto](user-client.md) · [Passport](telegram-passport.md) · [Scaling](scaling.md)

---

## Consumer layer recipes (merged from teleclient, Phase 2)

The teleclient capabilities (tenant-scoped ingest, Redis bus, daemon,
backfill, encrypted backup) now live in this package under
`MeRezaRezaei\Teleframe\*`. Common ground: a Laravel host app, Postgres,
Redis, and this package installed (migrations auto-load — `php artisan
migrate` creates the `tl_*` truth tables). Sessions are credentials: keep
them in env/secrets, never in committed files.

### (f) First update → Postgres row

```bash
# 1. Login once — teleframe's wizard prints a session string:
php artisan teleframe:login

# 2. Register the account (config/teleframe.php):
# 'daemon' => ['accounts' => [
#     ['account_id' => 501558149, 'session_string' => env('TELEGRAM_SESSION_501558149')],
# ]],

# 3. Produce: run the daemon bootstrap (pattern in docs/bus.md — host
#    command wrapping Daemon + RedisStreamSink) with TELEFRAME_LIVE=true.

# 4. Consume one batch:
php artisan teleframe:ingest --once
```

Query what landed — the aggregator resolves the CURRENT instance:

```php
$user = app(\MeRezaRezaei\Teleframe\Teleclient::class)
    ->user(501558149, 501558149);          // ?TlUser, tenant-scoped
$user?->currentInstance->first_name;       // fields live on the instance
```

### (g) Route updates are hot by construction

```php
use MeRezaRezaei\Teleframe\Bus\RouteTable;

$table = app(RouteTable::class);
$table->set('updateNewMessage*', 'tg:target:messages');
$table->set('*', 'tg:target:everything');
```

```bash
php artisan teleframe:ingest   # re-reads routes per entry; forward + ack
```

### (h) Backfill a channel's history (quota-aware)

```bash
php artisan teleframe:backfill --account=501558149 --peer=@channel --budget=25
php artisan teleframe:backfill --account=501558149 --peer=@a --peer=@b
```

Flag details and v1 report-only semantics: [bus.md](bus.md).

### (i) Encrypted backup of a directory to a Telegram channel

```php
// config/teleframe.php — real backups need the telegram driver;
// driver 'memory' (default) is the offline smoke-test driver.
'backup' => [
    'driver' => 'telegram',
    'account' => env('TELEFRAME_BACKUP_ACCOUNT'), // daemon.accounts id
    'sets' => ['default' => [
        'paths' => [base_path('docs')],
        'excludes' => ['.git', 'node_modules'],
    ]],
],
```

```bash
php artisan teleframe:backup run     --set=default --passphrase='...'
php artisan teleframe:backup verify  --set=default --sample=5   # keyless sampling
php artisan teleframe:backup verify  --set=default --passphrase='...'  # full decrypt
php artisan teleframe:backup restore --set=default --passphrase='...' --target=/tmp/restore
```

Deeper treatment: [ingest.md](ingest.md), [bus.md](bus.md), [backup.md](backup.md).

## Live test (opt-in, needs Telegram credentials)

1. `cp .env.example .env` and fill `TELEGRAM_API_ID` + `TELEGRAM_API_HASH` (my.telegram.org).
2. `./bin/teleframe doctor` — no account needed (handshake + `help.getNearestDc`).
3. `./bin/teleframe login` — writes the session string into `.env` (never commit it).
4. `./bin/teleframe me` — live `users.getUsers inputUserSelf`.
5. Optional: `php bin/live-walkthrough.php`, `./bin/teleframe test-e2e`, `php examples/batch-bench.php`.
