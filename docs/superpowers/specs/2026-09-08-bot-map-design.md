# Phase 5f — Bot Map design

> **Phase 5f.** A named third-party bot registry: register a bot once, then
> invoke *any* Bot API method on it by name through one resolution point —
> as a function call, from a handler, or over an authenticated HTTP endpoint
> (webhook-controller precedent). Tokens live in-process or encrypted in the
> Backup vault seam (VaultInterface + VaultCrypto) and capability discovery
> follows Q19: the getMyCommands probe automates the discoverable, the manual
> manifest documents the rest.
>
> All code lives under `MeRezaRezaei\Teleframe\BotMap\`, is zero-regex, and is
> plain-PHP constructible — the vault, passphrase, and BotClient factory are
> constructor wires, never a hard-coded container call.

---

## 1. RULINGS enacted (gap doc §RULINGS)

- **Q19 (a):** capability discovery = `getMyCommands` probe **at registration**
  (best-effort, silent on failure) **+ manual manifest array**. The probe is
  cached on the entry and written back to the registry; the manifest is the
  documented residual. `manifest()` = manual ∪ probe.

## 2. Rulings made here (autonomy protocol item 2)

- **Token vault wiring:** `BotMap` accepts a `VaultInterface`, a passphrase,
  and an optional `clientFactory` callable via its constructor. `vault_key` in
  register config moves the token into the vault at register; `for()` decrypts
  it on resolve. The Laravel bind (`teleclient.backup.vault-factory`) is never
  called from `BotMap` — hosts wire the seam; docs show the container one-liner.
- **HTTP auth:** a shared-secret HMAC header (`X-BotMap-Signature`) over
  `bot\nmethod\nbody`, constant-time `hash_equals`, bot **and** method bound
  into the MAC (replay cannot cross bots/methods), and a **required** secret —
  unset secret answers 500 so an unauthenticated endpoint cannot silently ship.
- **BotClient call surface:** `BotClient::call(string $method, array $params)`
  is the single generic invocation seam — `BotEntry::command()`/`call()` are
  thin passthroughs, so the entire Bot API surface is reachable without a
  wrapper per method.

## 3. The idea

The anonymous `bot('token')` client is stateless-per-call: every consumer
handles its own token, transport, and discovery. Multiboot apps (user-facing
bot + support bot + ops bot) quickly drift into token soup. The Bot Map bends
that back to Laravel's named-resolver shape — one registry, one name, one
resolution, used anywhere:

```php
$map = app(BotMap::class);
$map->register('support', ['token' => '123:ABC', 'capabilities' => ['answer_callback_query']]);

$map->for('support')->command('sendMessage', ['chat_id' => 1, 'text' => 'Hi']);
```

## 4. Registry (`BotMap`)

- `register(string $name, array $config)` — config
  `{token, vault_key?, capabilities?, route_prefix?}`. Validates token
  presence and name uniqueness (`InvalidArgumentException`); vaulted tokens are
  encrypted into the vault at register and nulled in memory; a best-effort
  `getMyCommands` probe seeds the manifest union. Probe failure is swallowed
  (Q19 — registration must not throw offline).
- `for(string $name): BotEntry` — resolves the entry, **decrypts the token
  from the vault** when `vault_key` is set, and returns a `BotEntry` wrapping a
  fresh `BotClient` from the `clientFactory` seam. Unknown name →
  `InvalidArgumentException` (the `MethodRegistry` named pattern).
- `all(): array` — introspection metadata per bot (name, capabilities,
  probed, manifest, vault_key, route_prefix); **tokens never returned**.

## 5. Entry (`BotEntry`)

- `command(string $method, array $params = []): array` — fluent invocation;
  thin passthrough to `BotClient::call`.
- `call(string $method, array $params = []): array` — explicit low-level alias.
- `commands(): array` — `getMyCommands` probe through the transport, cached
  per entry, written back to the registry.
- `manifest(): array` — manual `capabilities` ∪ probe, cached (seeded from the
  registration-time probe so the happy path never re-probes).
- `client()`, `name()`, `capabilities()`, `routePrefix()` — accessors.

## 6. Token vault (`TokenVault`)

- One chunk per bot, name `tfbintoken:{vault_key}`, stored through the
  `VaultInterface` seam.
- Blob = `salt \x00 ciphertext`; keyed by `VaultCrypto::deriveKey` (Argon2id,
  per-token salt) and wrapped in `VaultCrypto::encryptChunk`
  (XChaCha20-Poly1305, FINAL tag). Wrong passphrase or tampering fails loud.
- `store`/`retrieve`/`has`; `VaultInterface` is the exact seam that backs the
  `teleclient.backup.vault-factory` driver behind `teleframe:backup`.

## 7. Bot-on-bot / user-to-bot transport (`BotTransport`)

- Thin wrapper over the registry: `invoke(bot, method, params)` and
  `for(bot)` — one named indirection between bot worlds, resolvable, mappable,
  and swappable in tests.

## 8. HTTP exposure (`Http/BotMapController`)

- `__invoke(Request, string $bot, string $method): JsonResponse` — webhook-
  controller style: HMAC-authenticated outbound call, BotClient envelope
  returned as JSON.
- Auth: `X-BotMap-Signature = HMAC-SHA256("bot\nmethod\nbody", secret)`; `hash_equals`; route params bound into MAC.
  Secret from `teleframe.botmap.secret` / `TELEFRAME_BOTMAP_SECRET` (webhook
  controller resolution precedent); missing secret → 500.
- Errors: 401 bad signature, 404 unknown bot, 502 transport failure.
- `BotMapController::macro(Router $router, string $uri)` registers
  `Route::telegramBotApi()` mirroring `Route::telegramWebhook` — the caller
  registers it (the provider is not touched; docs show the boot wiring).

## 9. Proof surface (tests/BotMap)

- `BotMapTest` — register/for/all; unknown bot throws; token required;
  duplicate name rejected; **vault decrypt on resolve** + token kept out of
  memory; vault-without-seam throws; wrong passphrase throws; register probe
  seeds manifest union; probe failure is silent with manual fallback;
  capabilities/route_prefix exposure.
- `BotEntryTest` — command() hits the fake transport with the right params;
  call() passthrough; manifest = manual ∪ probe cached; commands() probed +
  cached; malformed probe entries filtered; seeded manifest never re-probes.
- `TokenVaultTest` — round-trip, passphrase binding, has(), unknown key,
  overwrite, empty-token rejection.
- `BotMapControllerTest` — valid signature → JSON; empty body; missing secret
  → 500; invalid signature → 401; **signature bound to bot and method**;
  unknown bot → 404; transport failure → 502; macro registration.
- `BotTransportTest` — cross-bot invoke, for() entry, unknown bot throws.

Transport is always faked (a recording `BotClient` double or an
`Illuminate\Http\Client\Factory` fake) — no test touches the real Telegram API.