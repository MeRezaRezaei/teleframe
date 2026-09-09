# Credential Vault — DB-encrypted apps + accounts (design)

**Created:** 2026-09-09. **Type:** architectural.
**Depends on:** `2026-09-08-identity-bindings-design.md`, `TeleframeClient`, `SessionData`, provider singletons.

## Problem

Per-Laravel-User N `api_id/api_hash` pairs (my.telegram.org apps) and N logged-in accounts (user sessions, bot tokens). Env-only storage blocks multi-tenancy. Session strings must live DB-encrypted. Only default account id stays in `.env`.

## Account kinds

Two kinds, one `telegram_accounts.type`:

- `user`: MTProto. Needs `app_id` FK (api_id/hash) + `session` (SessionData export string, encrypted) + `dc_id`. Resolves via `TeleframeClient::user()`. Login via `teleframe:login` (phone/QR).
- `bot`: two transports. `bot-http` needs only `bot_token` (encrypted, `app_id` nullable). `bot-mtproto` needs `bot_token` + `app_id` FK + `session` (encrypted, via `auth.importBotAuthorization`). Resolves via `TeleframeClient::bot()` / `botMtproto()`.

## Tables (package-owned, app-owned migrations)

`telegram_apps`: id, label unique (`teleframe` seeded default), owner morph nullable (`owner_type/owner_id`, no FK — bindings pattern), `api_id` int, `api_hash` text (encrypted cast), timestamps.

`telegram_accounts`: id, `app_id` nullable FK → `telegram_apps.id` null-on-delete (null allowed only for `bot` + `bot_token` set; `user` and `bot-mtproto` require non-null), label, type `user|bot`, owner morph nullable, `session` text nullable (encrypted), `bot_token` text nullable (encrypted), `dc_id` int default 2, timestamps. Unique `(app_id, label)`. Type CHECK enforced in model validation, not DB enum (sqlite/pg portable).

Integrity separate: deleting app nulls `app_id` (accounts survive, MTProto unusable until re-linked); deleting account never touches app.

## Models + resolver

`src/Teleframe/Vault/TelegramApp`, `TelegramAccount` (`$casts: api_hash/session/bot_token => encrypted`). `Vault` service (provider singleton): `app(name)->account(label)`, `defaultAccount()`. `TeleframeClient::userFromVault(label)`, `botFromVault(label, $transport='http')` — thin wrappers over existing `user()/bot()/botMtproto()` overrides. No cross-tenant scan: explicit label only; default chain `teleframe.vault.default_account` / `TELEFRAME_DEFAULT_ACCOUNT_ID` → `primary_account_id` → env `TELEGRAM_*`.

Copy tenancy rule from `TlUserBinding::bindingFor`: exact-match only, null when unresolvable.

## CLI + my.telegram.org helper

`teleframe:vault:add-app {label}`, `add-account {app} {label} {--type=user|bot}`, `list`, `use-default {label}`. `teleframe:login --app --account` writes encrypted session.

Manual-first: commands print my.telegram.org steps, prompt paste of id/hash. DOM helper (`DOMDocument` only, zero-regex) best-effort fetch; on any failure print manual fallback. Never stores my.telegram.org password.

## Config / env

Only `TELEFRAME_DEFAULT_ACCOUNT_ID` (or `teleframe.vault.default_account`) in `.env`. All secrets in DB. Existing `TELEGRAM_*` remain as fallback for single-account hosts.

## Testing / gates

TDD: migration round-trip sqlite, encrypted-cast assertions (ciphertext != plaintext), FK null-on-delete, resolver exact-match + default chain, `userFromVault/botFromVault` with faked transport, console happy-path. Gates: `composer verify`, `php bin/standalone-smoke.php`, `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg`. Live ladder unchanged opt-in.

## Out of scope

Permission scoping per app-user (later), proxy per account (reuse existing override), auto-rotation.
