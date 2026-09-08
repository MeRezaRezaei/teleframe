# Bot Map — named third-party bots

> **Phase 5f.** A named registry for third-party bots: register once, invoke
> any Bot API method by name (`sendMessage`, `getMyCommands`, …), get a
> discoverable capability manifest, keep tokens either in-process or encrypted
> in the Backup vault seam, and expose bots on the HTTP edge — invoking a bot
> as a function call is the whole point. Builds on the anonymous
> `bot('token')` client from `docs/bot-client.md` and supersedes ad-hoc token
> juggling for multi-bot apps.

```php
use MeRezaRezaei\Teleframe\BotMap\BotMap;

$map = app(BotMap::class);

// 1. register once
$map->register('support', [
    'token'        => '123456:ABC-SECRET',
    'capabilities' => ['answer_callback_query'],   // manual manifest (Q19)
    'route_prefix' => 'support',
]);

// 2. invoke by name (any Bot API method)
$map->for('support')->command('sendMessage', ['chat_id' => 1, 'text' => 'Hi']);
```

Everything lives under `MeRezaRezaei\Teleframe\BotMap\`, is zero-regex, and is
plain-PHP constructible — the vault, passphrase, and BotClient factory are
constructor wires, never a hard-coded container call.

---

## 1. Registration

```php
$map = new BotMap(
    vault: $vault,                 // ?MeRezaRezaei\Teleframe\Backup\VaultInterface
    vaultPassphrase: getenv('BOTMAP_VAULT_PASSPHRASE'),
    clientFactory: fn (string $token, array $config) => new BotClient($token),
);

$map->register('support', ['token' => '123456:ABC-SECRET']);
$map->register('ops', [
    'token'        => '654321:DEF-SECRET',
    'vault_key'    => 'ops-prod',       // encrypt into the vault, drop from memory
    'capabilities' => ['send_message', 'delete_message'],
    'route_prefix' => 'ops',
]);
```

- `token` — required. The Bot Client token.
- `capabilities` — the **manual capability manifest** (Q19: the documented
  part of discovery). Free-form string tags.
- `vault_key` — optional. When set, the token is **encrypted into the vault at
  `register()`** (via `VaultInterface` + `VaultCrypto` AEAD, chunk name
  `tfbintoken:{vault_key}`) and **removed from process memory**. On
  `BotMap::for()` it is **decrypted** again. Requires the vault seam +
  passphrase; registering with `vault_key` and no vault wiring throws loudly.
- `route_prefix` — optional metadata for HTTP routing/naming.

Duplicate names and missing tokens throw `InvalidArgumentException` at
register.

## 2. Invocation

```php
$entry = $map->for('support');                       // resolves + decrypts the token
$entry->command('sendMessage', ['chat_id' => 1, 'text' => 'Hi']);

// low-level passthrough (= BotClient::call)
$map->for('support')->call('getMe');
```

- `BotMap::for(string $name): BotEntry` — throws
  `InvalidArgumentException` for unknown names (the `MethodRegistry` named
  pattern).
- `BotEntry::command(string $method, array $params = []): array` — fluent
  invocation over `BotClient::call()`, so the entire Bot API surface stays
  reachable.
- `BotEntry::call(...)` — explicit low-level alias.

## 3. Capability manifest (Q19)

Discovery is **automated where Telegram lets us, documented where it doesn't**:

- **Automated** — a best-effort `getMyCommands` probe runs at `register()` and
  at `BotEntry::commands()`. It is **silent on failure** (offline registration
  must not throw) and its result is **cached**.
- **Documented** — the `capabilities` manifest array above.

```php
$entry->commands();   // ['start', 'help', …]      — getMyCommands probe, cached
$entry->manifest();   // manual ∪ probe, cached   — e.g. ['send_message', 'start', …]
```

`BotMap::all()` returns every registered bot's metadata (name, capabilities,
probed commands, manifest, vault_key, route_prefix) — **never a token**.

## 4. Token vault

Tokens registered with `vault_key` never sit in plaintext memory after
registration. `MeRezaRezaei\Teleframe\BotMap\TokenVault` stores them through
the **Backup vault seam** (`VaultInterface`, the same `teleclient.backup.vault-factory`
driver that backs `teleframe:backup`):

- one chunk per bot named `tfbintoken:{vault_key}`;
- the blob is `salt \x00 ciphertext`, keyed by Argon2id
  (`VaultCrypto::deriveKey`) from the passphrase + a per-token salt, wrapped in
  XChaCha20-Poly1305 (`VaultCrypto::encryptChunk`);
- a wrong passphrase or a tampered chunk fails loud on resolve.

```php
$vault = app('teleclient.backup.vault-factory')('botmap');  // driver-aware
$map = new BotMap($vault, getenv('BOTMAP_VAULT_PASSPHRASE'));
$map->register('ops', ['token' => '654321:DEF-SECRET', 'vault_key' => 'ops-prod']);

$map->for('ops')->command('getMe');   // token decrypted from the vault, used, released
```

## 5. HTTP exposure (authenticated outbound API)

The webhook-controller precedent, inverted for **outbound** calls:
`POST /telegram/bot-map/{bot}/{method}` invokes the bot and returns the
BotClient response as JSON.

### Auth — HMAC header

The controller requires a shared secret (`teleframe.botmap.secret` config or
`TELEFRAME_BOTMAP_SECRET` env) and validates an
`X-BotMap-Signature` header:

```
X-BotMap-Signature = HMAC-SHA256( bot . "\n" . method . "\n" . rawBody , secret )
```

The bot **and** method route params are bound into the MAC, so a captured
signature can never be replayed onto a different bot or method. Constant-time
compare (`hash_equals`); a missing secret answers **500** so an unauthenticated
endpoint can never silently ship. Responses: `200` (BotClient envelope),
`401` (bad signature), `404` (unknown bot), `502` (transport error).

### Route macro

Mirrors `Route::telegramWebhook`. The package does **not** wire the provider
for you (Phase 5f constraint) — register the macro yourself, e.g. in a service
provider's `boot()`:

```php
use Illuminate\Support\Facades\Route;
use MeRezaRezaei\Teleframe\BotMap\BotMap;
use MeRezaRezaei\Teleframe\BotMap\Http\BotMapController;

// in a ServiceProvider::boot()
BotMapController::macro($this->app['router']);

// in routes/api.php
Route::telegramBotApi();                    // POST telegram/bot-map/{bot}/{method}
Route::telegramBotApi('internal/bots/{bot}/{method}');

// bind the BotMap so the controller can resolve it
$this->app->singleton(BotMap::class, fn () => new BotMap(/* vault, passphrase */));
```

## 6. Bot-on-bot / user-to-bot transport

`MeRezaRezaei\Teleframe\BotMap\BotTransport` is the thin seam for a handler or
another bot to invoke a registered bot's methods — one named indirection,
mirrorable and swappable in tests:

```php
use MeRezaRezaei\Teleframe\BotMap\BotTransport;

$transport = new BotTransport(app(BotMap::class));

// a handler answering through the support bot
$transport->invoke('support', 'sendMessage', ['chat_id' => $chatId, 'text' => 'A human will reply soon']);

// chained multi-call work
$transport->for('support')->command('setMyCommands', ['commands' => []]);
```

## 7. Testing

Everything is plain-PHP and transport-injectable: build a `BotMap` with a
`clientFactory` that returns a faked/recorded `BotClient` (or an
`Illuminate\Http\Client\Factory` fake) and no test ever touches the Telegram
API. The vault tests use `MeRezaRezaei\Teleframe\Backup\InMemoryVault` +
`VaultCrypto`, byte-for-byte the same code paths the real vault uses.

## Files

- `src/Teleframe/BotMap/BotMap.php` — registry (`register`/`for`/`all`)
- `src/Teleframe/BotMap/BotEntry.php` — invocation + capability surface
- `src/Teleframe/BotMap/TokenVault.php` — encrypted token vault over the Backup seam
- `src/Teleframe/BotMap/BotTransport.php` — cross-bot / handler-to-bot seam
- `src/Teleframe/BotMap/Http/BotMapController.php` — HMAC-authenticated HTTP exposure
- Design spec: `docs/superpowers/specs/2026-09-08-bot-map-design.md`
- Plan: `docs/superpowers/plans/2026-09-08-phase5f-bot-map.md`