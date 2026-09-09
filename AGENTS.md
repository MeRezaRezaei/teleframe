# AGENTS.md — working in the Teleframe repo

Guidance for coding agents editing this repository. Facts below describe
`src/` and `generated/` as shipped; when docs and code disagree, code wins.

## Single-package layout

`merezarezaei/teleframe` is ONE package: the engine **and** the merged
consumer layer (the former `teleclient` is archived; its modules now live
here under `MeRezaRezaei\Teleframe\*`).

| Layer | Location | What it is |
| --- | --- | --- |
| MTProto wire | `src/Core/MTProto/` | Raw binary: `Client`, `Connection/EncryptedConnection` (handshake, blocking `call()` + `callBatch()`, `invokeWithLayer` wrap), `Crypto/` (DH, 2FA SRP), `TL/` (zero-regex registry + codec), `Transport/`, `SessionData`. Wire speaks **Layer 227** (`EncryptedConnection::LAYER`). |
| Schema + Methods | `src/Core/Schema/`, `src/Core/Methods/` (+ `Generated/`), `src/Bot/Methods/Generated/` | `MethodRegistry` loads `schema/methods-mtproto.json` + `schema/methods-botapi.json`; `SchemaDiffer` audits; `Methods::__callStatic` resolves fluent groups. Schema catalog = **Layer 229** (`composer extra.telegram-layer`). The 227-wire vs 229-schema gap is intentional — never "fix" it. |
| Services | `src/Laravel/Services/`, `src/Bot/Services/`, `src/Core/Services/` | `TeleframeClient` (entry: `user`/`fromSession`/`bot`/`botMtproto`), scopes, `TeleframeAuthService` (phone/QR/bot login), update polling. |
| Exceptions | `src/Core/Exceptions/` | `TelegramException` base, `DcMigrationException`, `Rpc/` typed catalog + `RpcExceptionResolver`. |
| Consumer modules | `src/Teleframe/{Ingest,Bus,Daemon,Backfill,Backup}/` | Tenant-scoped Ingest (Postgres truth, `UpdateStored` event), Redis stream Bus (`tg:stream:updates`, consumer group `teleclient`, route table `tg:bus:routes`, DL `tg:stream:dead-letter`), multi-account `Daemon`, quota-aware `BackfillWorker`, encrypted `Backup` vault. |
| Handlers | `src/Teleframe/Handler/` (+ `Middleware/`, `Subscriptions/`, `Testing/`) | Handler-as-data `HandlerRegistry` (`on`/`onMessage`), zero-regex `HandlerMatcher` (exact / `prefix*` / `*`, first-match + priority), `Update` uprate (`fromBus`/`fromMirror`), 26-line `Pipeline` onion, `UpdateDispatcher` (DI-flavored invoke, lazy model hydration), `EchoEliminator` (Q2d send-time PSR-16 registry, `onOwn` escape hatch), `HandlerSink`/`UpdateStoredHandler` (both intake rows → one pipeline), `Testing\FakeDispatcher` (real pipeline, faked transport). |
| Console | `src/Laravel/Console/` | `teleframe:login`, `teleframe:poll`, `teleframe:doctor`, `teleframe:schema-audit`, `teleframe:schema-update`, `teleframe:regenerate`, `teleframe:ingest`, `teleframe:backfill`, `teleframe:backup`. |
| Faces | `src/Teleframe/Teleframe.php`, `src/Teleframe/Teleclient.php` | `Teleframe` = one-class compose-not-own facade (also `docs/handlers.md`); `Teleclient` = ingest-only face, byte-stable. |
| Laravel glue | `src/Laravel/` | `TeleframeServiceProvider` (delegate-container wiring only), `Route::telegramWebhook` macro, `tg.miniapp` middleware, `TF`/`Teleframe` facades, config key `teleframe` (env `TELEFRAME_*`). |

## Generated artifacts — never hand-edit

Carry `@generated` markers; overwritten by the schema pipeline:

- `schema/methods-*.json`, `schema/sources/*.tl` — update via
  `php artisan teleframe:schema-update` (diff + regenerate + stamp, NO migrate).
- `generated/**` (models, data, factories) + `migrations/` — `php artisan
  teleframe:regenerate [--ship]` / `php bin/regenerate`.
- `src/{Core,Bot}/Methods/Generated/*.php` — regenerate after editing
  `schema/config/curated-methods.json`.
- `src/Schema/skills/telegram-methods/*.md` — generated from the catalog
  (`bin/generate-skill-files.php`, 30 files, `<!-- @generated -->` stamped).
- `src/Core/Exceptions/Rpc/RpcErrorCatalog.php` — regenerated from the
  committed `errors.json`.

## Hard rules

- **Zero regex in `src/` outside the allow-list**: `preg_*()` is banned
  (phpstan `disallowedFunctionCalls`); allowed only in `src/Laravel/*`,
  `src/Schema/*`, `src/Bot/*`. `src/Teleframe/Handler/*` and `src/Core/*` are
  strictly zero-regex (`sscanf`, string funcs, TL tokenizer).
- **Session strings are credentials**: `.env` (with session values) must never
  be committed; tests never require real credentials; live gates are opt-in.
- **Redis wire keys are opaque state**: do not rename
  `tg:stream:updates` / group `teleclient` / `tg:bus:routes` / DL / reload
  channel (forward compat with the archived teleclient).
- **Public bind keys stay fixed**: `teleclient.backfill.scope-resolver`,
  `teleclient.backfill.ingester`, `teleclient.backup.vault-factory`.

## Gates

Run before declaring work done (from repo root):

```bash
composer verify        # phpunit + phpstan (level 5, src only) + regeneration idempotence
php bin/standalone-smoke.php   # exit 0 = every module plain-PHP-constructible
TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg   # Postgres truth track (unix-socket peer auth)
```

Live gates (real credentials) are opt-in and never part of CI.

Notes: LSP "Undefined type" diagnostics are stale-autoload noise — final
authority is `composer verify`. `composer.lock` is gitignored. `tests/`
mirrors `src/`, one test class per file.

## Known gaps / limitations (2026-09-09 audit)

- **No logging seam.** `src/` contains zero `LoggerInterface`/`Log::` usage and
  `psr/log` is not required. The engine intentionally delegates observability
  to the host app (Laravel writes its own logs); an injectable PSR-3 logger is
  an open application-layer item, not an engine bug.
- **StreamSocket proxy tunneling not implemented.** `StreamSocket` accepts a
  `proxy` config (SOCKS5/HTTP shape) but connects directly — `@todo` in the
  class. Wire-path spec's proxy option remains inert by design.
- **PSR-7/17 not used** (by design): `illuminate/http` is the HTTP seam inside
  Laravel; MTProto is raw TCP. PSR-17 becomes relevant only if raw HTTP
  handling is added outside Laravel.
- **Schema artifacts catalogue Layer 229 while the wire speaks Layer 227** —
  intentional, never "fix" it (see the Schema + Methods row above).

## Specs and plans

Design specs live in `docs/superpowers/specs/`, implementation plans in
`docs/superpowers/plans/` (dated filenames), roadmap in
`docs/superpowers/plans/2026-09-07-master-roadmap.md`. Read the relevant spec
before touching the wire layer, the schema pipeline, or the handler
substrate; the zero-regex and generated-artifact rules come from there.