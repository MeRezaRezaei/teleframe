# AGENTS.md — working on the Teleproto repo

Guidance for coding agents editing this repository. Facts below describe `packages/*/src/` as shipped; when docs and code disagree, code wins.

## Architecture map

| Layer | Location | What it is |
| --- | --- | --- |
| MTProto wire layer | `packages/core/src/MTProto/` | Raw binary engine: `Client`, `Connection/EncryptedConnection` (handshake, blocking `call()` + `callBatch()` msg_container batching with receive demux by inner msg_id, `invokeWithLayer` wrap), `Crypto/` (DH key exchange, 2FA SRP), `TL/` (registry + serializer/encoder/decoder, zero-regex), `Transport/`, `SessionData`. |
| Schema + Methods (generated layer) | `packages/schema/src/`, `packages/*/src/Methods/` | `MethodRegistry` loads the packaged artifacts `schema/methods-mtproto.json` + `schema/methods-botapi.json` from `packages/schema/schema/` into `TelegramMethod` entries; `SchemaDiffer` audits them. `Methods.php` exposes fluent builder groups backed by `src/Methods/Generated/*`. |
| Services | `packages/laravel/src/Services/`, `packages/bot/src/Services/`, `packages/core/src/Services/` | `TeleprotoClient` (entry point: `user`/`fromSession`/`bot`/`botMtproto`/`dispatch`), `UserAccountScope` + `BotAccountScope` + `BotClient` (per-transport call scopes), `TeleprotoAuthService` (phone/QR/bot login), `UpdatePollerService` + `EventDispatcherSink` (update ingestion, `updates.getDifference` state machine). |
| Exceptions | `packages/core/src/Exceptions/` | `TelegramException` base, `DcMigrationException`, and `Rpc/`: typed per-error classes (`FloodWaitException`, `AuthKeyException`, ...), `RpcErrorCatalog` (generated official error DB), `RpcExceptionResolver` (error string -> typed exception + doc hint). |
| Console | `packages/laravel/src/Console/` | Artisan commands: `teleproto:login`, `teleproto:doctor`, `teleproto:poll`, `teleproto:schema-audit`, `teleproto:schema-update`. |
| Support surfaces | `packages/laravel/src/(Http|Media|Facades|Events)`, `packages/core/src/(Passport|Types|Contracts|Support)` | Webhook controller + `Route::telegramWebhook` macro, Mini App HMAC middleware, Passport decryption, input-object helpers, `Teleproto`/`TP` facades, `UpdateSinkInterface`, update events. |

## Generated artifacts — never hand-edit

These carry `@generated` markers and are products of the schema pipeline. Hand edits are overwritten on the next regeneration:

- `packages/schema/schema/*.json` and `packages/schema/schema/sources/` — regenerate with `php packages/schema/bin/generate-method-schema.php` and `php packages/schema/bin/generate-botapi-schema.php`; audit/update against the live schema with `php artisan teleproto:schema-audit --write` / `php artisan teleproto:schema-update`.
- `packages/*/src/Methods/Generated/*.php` — regenerate with `php packages/schema/bin/generate-method-builders.php` after editing the curated dial `packages/schema/config/curated-methods.json`.
- `packages/schema/skills/telegram-methods/*.md` — regenerate with `php packages/schema/bin/generate-skill-files.php`.
- `packages/core/src/Exceptions/Rpc/RpcErrorCatalog.php` — regenerate with `php packages/schema/bin/generate-rpc-catalog.php` (re-fetches core.telegram.org/api/errors.json) after a layer bump.

To grow the fluent-builder surface: add method names to `packages/schema/config/curated-methods.json`, then run the builders + skill-file generators. `Methods::__callStatic` resolves groups added by regeneration; unknown groups fail loudly.

## Hard rules

- **Zero regex in `src/`**: `preg_*()` is banned inside packages (phpstan `disallowedFunctionCalls`, spec 2026-08-28 §A). Use `sscanf`, string functions, or the TL tokenizer. `bin/` and `examples/` are exempt.
- **Session strings are credentials**: `.env` (with `TELEGRAM_*_SESSION`) must never be committed. Tests never require real credentials.
- **Layer note**: the wire speaks Layer 227 (`EncryptedConnection::LAYER`, and `RpcErrorCatalog::LAYER` matches), while the packaged schema artifact `methods-mtproto.json` is Layer 229. They intentionally differ; do not "fix" one to match the other without running the schema-update pipeline and the full live gate.
- **teleclient dev-link**: If working on the consumer repo `teleclient`, it mounts these packages via path repositories pointing at `packages/*` during development.

## Gates

Run before declaring work done (from repo root):

```bash
composer verify        # install + pin check + 4 suites + phpstan x4 + regeneration idempotence
```

Live gates (opt-in, real credentials) are unchanged:
`TELEPROTO_LIVE=true ./bin/teleproto test-e2e` — never part of CI.

## Specs and plans

Design specs live in `docs/superpowers/specs/` and implementation plans in `docs/superpowers/plans/`, dated by filename. Read the relevant spec before touching the wire layer or the schema pipeline; the zero-regex and generated-artifact rules come from there.
