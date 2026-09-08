# Phase 5f — Bot Map (execution plan)

**Parent:** `specs/2026-09-08-bot-map-design.md` (this layer's spec) +
`docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5f.
**Created:** 2026-09-08. **Depends on:** nothing heavy (5b–5e independent);
uses the shipped `BotClient` (`Bot\Services`), the `MethodRegistry` named
pattern (`Core\Schema`), the webhook-controller + mini-app HMAC precedents
(`Laravel\Http`), and the `Backup` vault seam (`Teleframe\Backup`,
`teleclient.backup.vault-factory`).

## RULINGS enacted (gap doc §RULINGS)

- **Q19 (a):** capability discovery = getMyCommands probe at registration
  (automate the discoverable) + manual capability manifest (document the
  rest). Probe is best-effort and silent on failure; results cached and
  written back to the registry; `manifest()` = manual ∪ probe.

## Rulings made here (autonomy protocol item 2)

- **Vault wiring:** `BotMap` constructor accepts `VaultInterface` + passphrase
  + `clientFactory` callable. No hard-call of the `teleclient.backup.vault-
  factory` Laravel bind from the module; docs show the container one-liner.
- **HTTP auth:** shared-secret HMAC header `X-BotMap-Signature` over
  `bot\nmethod\nbody` (bot + method bound into the MAC), constant-time
  compare, required secret (missing → 500). `Route::telegramBotApi()` macro
  mirrors `Route::telegramWebhook`; registration is documented, not wired.
- **BotClient call surface:** `BotClient::call()` is the generic seam;
  `BotEntry::command()`/`call()` are thin passthroughs.

## Findings recap

- `BotClient::call()` is the single generic invocation point; typed helpers
  exist but a named-bot wrapper must not duplicate them — passthrough wins.
- `Illuminate\Http\Client\Factory::fake()` intercepts `BotClient`'s
  `asJson()->post()`; a recording `BotClient` subclass is the lightweight
  test double (no container, no HTTP).
- `Router` is plain-PHP constructible (`new Router(new Dispatcher(), new
  Container())`) and `Macroable::hasMacro` lets the macro test run offline.
- Laravel 12 (merged illuminate/*) provides Router/Request/JsonResponse; the
  disallowed-calls rule still bans `preg_*` in `src/Teleframe` — HMAC uses
  `hash_hmac`/`hash_equals` + string funcs only.

## Tasks

- [x] **Task 1: `BotMap` registry.** `register(name, {token, vault_key?,
      capabilities?, route_prefix?})`, `for(name): BotEntry` (throws
      unknown-bot, MethodRegistry pattern), `all()` metadata (never tokens);
      constructor wires `VaultInterface`/passphrase/`clientFactory`;
      vaulted tokens encrypted at register + decrypted on resolve; Q19 probe
      at register (silent failure). Tests: register/for/all, unknown bot,
      duplicate + missing token, vault decrypt-on-resolve + token-out-of-
      memory, vault-without-seam, wrong passphrase, probe-seeded manifest,
      silent probe fallback.
- [x] **Task 2: `BotEntry`.** `command(method, params): array` fluent →
      `BotClient::call`; `call()` low-level alias; `commands()` probe via
      getMyCommands, cached + registry write-back; `manifest()` = manual ∪
      probe, cached (seeded). Tests: transport hit with right params, manifest
      union + cache, commands probe + cache, malformed-entry filtering,
      seeded-manifest no-reprobe, accessors.
- [x] **Task 3: `TokenVault`.** `VaultInterface` + `VaultCrypto` AEAD per-key
      chunks (`tfbintoken:{key}`, `salt\x00cipher`); wrong passphrase/tamper
      fails loud. Tests: round-trip, passphrase binding, has(), unknown key,
      overwrite, empty-token rejection.
- [x] **Task 4: `Http/BotMapController` + route macro.** HMAC-authenticated
      `__invoke(Request, bot, method): JsonResponse`; 500-missing-secret,
      401-bad-signature, 404-unknown-bot, 502-transport; `macro()` registers
      `Route::telegramBotApi()` (mirror `telegramWebhook`). Tests: valid
      signature + JSON, empty body, bot/method MAC binding (no replay), all
      error codes, macro registration.
- [x] **Task 5: `BotTransport`.** Cross-bot / handler-to-bot thin wrapper
      (`invoke`, `for`). Tests: cross-bot invoke, entry chain, unknown bot.
- [x] **Task 6: docs.** `docs/botmap.md` (register → for()->command(),
      manifest, vault tokens, HTTP exposure + route macro + auth,
      BotTransport, testing), this spec + plan. Roadmap Phase 5f ticks are
      the coordinator's step (`docs/superpowers/plans/2026-09-07-master-
      roadmap.md` is on the do-not-touch list for this worker).

## Gate evidence (2026-09-08)

- `vendor/bin/phpunit tests/BotMap` → OK (list of green suites below in the
  worker report: BotMapTest, BotEntryTest, TokenVaultTest,
  BotMapControllerTest, BotTransportTest — all offline, transport faked).
- `vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress` →
  `[OK] No errors` (zero-regex enforced; no `preg_*` in `src/Teleframe/BotMap`).
- `composer verify` → full suite green + phpstan `[OK] No errors` (tail
  reported by the worker).

**Commits:** none (coordinator commits; Phase 5f files in the tree remain
owned by the 5f worker). Roadmap Phase 5f ticks are the coordinator's step
(`docs/superpowers/plans/2026-09-07-master-roadmap.md` is on the do-not-touch
list for this worker).