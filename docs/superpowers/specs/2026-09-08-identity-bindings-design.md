# Phase 5b — Identity & Laravel Bindings (design)

**Parent:** `docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5b ·
`2026-09-07-framework-layers-gap-analysis.md` §II (Q7–Q12).
**Created:** 2026-09-08. **Depends on:** Phase 3 substrate, Phase 5a (uprate
identity `Update::updateId`), the Q2 echo-elimination registry.

## Problem

A Laravel app using teleframe must answer, per incoming telegram update and
per outbound notification: *who is this telegram principal, on which tenant,
and which Laravel `User` owns it?* That is the identity layer. It has two
directions (Q8): **contactable** — reach a Laravel User ON Telegram
(notifications, Q9); and **login-side** — the user-app session IS a telegram
identity, resolved back to a Laravel User (Q10/Q11).

## RULINGS applied (gap doc §II, decided 2026-09-07, Autonomy Protocol)

| Q | Ruling | How Phase 5b enacts it |
|---|---|---|
| Q7 | **(a) `findTF`: primary-account default + explicit `accountId` override** ⚠ dev-facing | `TlUserBinding::bindingFor()` is the single tenancy point: configured primary (`teleframe.primary_account_id`/`TELEFRAME_PRIMARY_ACCOUNT_ID`) → deterministic FIRST known account for that tg id (min `account_id`) → null-account row → `null`. An explicit `accountId` is exact-match only. **Never a bare cross-tenant global scan.** |
| Q8 | **(a) package-owned `tl_user_bindings`, nullable morph** | New migration (app-owned, hand-authored): `id`, nullable `user_type`/`user_id` (morph), stable `tl_user_id` bigint, nullable `account_id`, `contact_lost` bool, timestamps; `unique (tl_user_id, account_id)`; index `(user_type, user_id)`. Morph has NO FK — bindings outlive User/entity deletion. `TlUserBinding` model owns identity lookups; `Bindings` owns the write hooks + public DX. |
| Q9 | **(d) `routeNotificationForTelegram()` override + (a) configured default sender fallback** | `TelegramChannel` reads the Laravel-native override first (null/empty route → skip); absent override → recipient from the binding, sender from `teleframe.identity.default_sender`/`TELEFRAME_IDENTITY_DEFAULT_SENDER`, else `botToken()`; delivery engine = `BotClient::sendMessage()` (the single in-tree real send surface; injectable `callable` sender for hosts). Null results never throw (Laravel channel convention). |
| Q10 | **(c) two guards: `tg-webapp` (initData) + `tg-session` (our user-app sessions)** | `TgWebAppGuard` reuses `VerifyMiniAppInitData::validateInitData` by INSTANTIATION (never reimplemented), adds the F4 freshness window (`teleframe.identity.miniapp_auth_max_age`, default 1800s) + binding resolution. `TgSessionGuard` walks session/attribute/callable sources → binding → morph `user`. |
| Q11 | **(a) uprate DTO delivered through Q4(c) mechanics — truth-first (D8)** | `Bindings::onStoreUpdate(UpdateStored)` — the ONE write-hook (Event carries the root model + account; no second raw-update DTO). Registers the principal `(tl_user_id, account_id)` and clears `contact_lost` when the mirror exposes a stable tg id exactly; no-op when it does not (message-rooted updates carry anonymized UUID refs — the binding never guesses). `Bindings::onLogin()` is the login-completion hook. |
| Q12 | **(a) ship publishable stubs — Phase 5g** | Out of scope here; the guards + bindings are exactly what Phase 5g's hosting wires to. |

## Architecture

```
tl_user_bindings (Q8 table)
  ├── TlUserBinding        model: bindingFor()/ensureBinding()/resolveAccountId()
  │                          (@property annotations for phpstan; resolved morph `user`)
  ├── Bindings             static façade: findTF(), bindLaravelUser(), onStoreUpdate(),
  │                          onLogin()                          [write hooks; DX surface]
  ├── IdentityConfig       config/env accessor (Laravel config() + getenv fallback)
  ├── HasTelegram          trait: telegramBindings(), routeNotificationForTelegram(),
  │                          findTF(), telegramBinding()         [contactable side, Q9]
  ├── HasUserTelegram      trait: hasTelegram + bindTelegramUser(), telegramUserId(),
  │                          telegramSessionAccount()            [login side, Q10]
  ├── TgUser               abstract Model composing HasUserTelegram (optional base;
  │                          ensures traits are ALWAYS analysed by phpstan)
  ├── Channels/            TelegramChannel (+ TelegramMessage value object)
  └── Guards/              TgWebAppGuard (initData) + TgSessionGuard (user sessions)
```

Every read/write on bindings funnels through `TlUserBinding::bindingFor()` /
`ensureBinding()` so the Q7 tenancy contract is enforced in exactly one place.

### findTF — the Q7 chain (user-facing contract)

`Bindings::findTF($tgId, $accountId = null)` / `HasTelegram::findTF`:

1. explicit `$accountId` → exact `(tl_user_id, account_id)` row only;
2. else configured primary account (`IdentityConfig::primaryAccountId()`);
3. else the deterministic first account that ever hosted `$tgId`
   (`min(account_id)` — stable, no cross-tenant poll);
4. else the null-account (plain-PHP / no-tenant) row;
5. else `null`.

A screenshot of this module must NOT change behavior between Laravel and
plain-PHP hosts: the model/façade carry no container dependencies.

### Guards

- **`tg-webapp`** (`TgWebAppGuard implements Guard`): reads init-data from
  `X-Telegram-Init-Data` header or `initData` input → HMAC via the middleware's
  core → freshness check (`auth_date` present and within the window) →
  `bindingFor((int)$tgId, account_id request param)` → `resolver()` → guard
  `user()`. Unbound/plain-PHP → `null` guest. `account_id` request input
  overrides the primary default.
- **`tg-session`** (`TgSessionGuard implements Guard`): source of truth
  low→high — explicit `callable` resolver (non-session hosts) → request
  attribute `telegram_session_user_id` / `telegram_session_account_id` →
  Laravel session keys `telegram_session_user_id`/`telegram_user_id` (written
  by the host at login completion; `Request::hasSession()` guarded) → binding →
  morph `user`.

`auth.php` guard wiring (host code; no provider registration — see plan):
`'tg-webapp' => guard(custom, TgWebAppGuard::class, [request()])`,
`'tg-session' => guard(custom, TgSessionGuard::class, [request()])`.

### Config surface (env-first; no config file change)

Read via `IdentityConfig`, which tolerates BOTH `config()` (Laravel,
`config:cache`-aware) and `getenv()` (plain-PHP):

| Key | Env | Default | Meaning |
|---|---|---|---|
| `teleframe.primary_account_id` | `TELEFRAME_PRIMARY_ACCOUNT_ID` | — | Q7 primary tenant |
| `teleframe.identity.default_sender` | `TELEFRAME_IDENTITY_DEFAULT_SENDER` | — | Q9 default sender (Bot token, or numeric account id → no-op default, host injects sender) |
| `teleframe.identity.miniapp_auth_max_age` | `TELEFRAME_MINIAPP_AUTH_MAX_AGE` | 1800 | initData freshness window (s) |
| `teleframe.bot_token` (chain) | `TELEGRAM_BOT_TOKEN` | — | bot token; identical fallback chain to `VerifyMiniAppInitData` |

## Open questions resolved during design

- **Trait analysis.** phpstan never analyses traits with zero consumers and
  flags `trait.unused`. Repo precedent: `HasTlChildren` → `TlInstanceModel`.
  Resolution: ship `TgUser` (abstract `Model` composing `HasUserTelegram`) as
  the optional base so every trait body is phpstan-analysed every gate run.
- **Ordering on Eloquent builders.** `whereNull()`/`whereNotNull()` and
  `orderBy` on a `Relation` degrade the generic to `Builder`/`stdClass`
  (`phpstan` `property.notFound`). Resolution: `where('col', null)` /
  `where('col', '!=', null)` forms and relation-generic sources replaced by
  explicit `TlUserBinding::query()` chains.
- **`tg-webapp`'s HMAC core.** The guard reuses `VerifyMiniAppInitData`
  word-for-word (instantiation) instead of copying its fallback chain — a
  session accepted by `tg-webapp` is guaranteed accepted by the `tg.miniapp`
  middleware and vice versa.

## Deliverables

- `migrations/2026_09_08_000100_create_tl_user_bindings_table.php` (app-owned;
  exempted from the ship-dial regenerate golden, documented in
  `ShipDialGoldenTest`).
- `src/Teleframe/Identity/**`: model, façade, config accessor, 2 traits,
  `TgUser`, channel + message, 2 guards — all phpstan-clean.
- `tests/Identity/**`: testbench-based (sqlite `:memory:`), 8 files mirroring
  `src/` (see plan Tasks).

## Gate

`User::findTF()` resolves in a Laravel test app; a `Notification` delivers via
the Telegram channel; a replayed init-data is rejected by `tg-webapp`. Plus the
repo gates: `composer verify` (phpstan) green, `bin/standalone-smoke.php` exit
0, Identity suite green by path (`vendor/bin/phpunit tests/Identity`).