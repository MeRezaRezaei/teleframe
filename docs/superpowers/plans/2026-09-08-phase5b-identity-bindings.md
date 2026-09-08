# Phase 5b — Identity & Laravel Bindings (execution plan)

**Parent:** `specs/2026-09-08-identity-bindings-design.md` (this layer's spec) +
`docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5b.
**Created:** 2026-09-08. **Depends on:** Phase 3 substrate + Phase 5a uprate
identity (`Update::updateId`), Q2 echo-elimination registry.

## Findings recap

- phpstan never analyses zero-consumer traits (`trait.unused` at level 4+ in
  the dir-analysis `composer stan`); every shipped trait must have an in-repo
  analysed consumer (repo precedent: `HasTlChildren` → `TlInstanceModel`).
- `whereNull()`/`whereNotNull()` and relationship-chained `orderBy` degrade
  Eloquent-builder generics to `Builder`/`stdClass` under phpstan; use
  `where('col', null)` / `where('col', '!=', null)` and explicit
  `TlUserBinding::query()` chains.
- `Illuminate\Auth\Authenticatable` (trait) implements
  `Illuminate\Contracts\Auth\Authenticatable` (interface); `Notification` is
  NOT abstract (anonymous subclasses legal in tests).
- `VerifyMiniAppInitData::validateInitData(string, string)` is the private-free
  HMAC core; reuse it by instantiation — never reimplement.
- The migration is an APP-OWNED hand-authored file: it has no
  `generated/migrations/` source and is NOT reproduced by `bin/regenerate
  --ship`. `ShipDialGoldenTest` therefore exempts it (documented
  `APP_OWNED_MIGRATIONS`), keeping the two regenerate-reproduction goldens
  byte-strict for TL dial migrations.

## RULINGS enacted (gap doc §II)

- **Q7 (dev-facing, enforced everywhere):** `findTF` = primary-account default
  + explicit `accountId` override; never a cross-tenant global scan. Fail-safe
  is the null-account row, then `null`.
- **Q8:** package-owned `tl_user_bindings`, nullable morph, no FK, `contact_lost`
  survives deletion, `unique (tl_user_id, account_id)`.
- **Q9:** Laravel-native `routeNotificationForTelegram()` override + configured
  default sender fallback; channel never throws on unrouteable/unsendable.
- **Q10:** `tg-webapp` (initData, reusing the middleware core + freshness window)
  AND `tg-session` (user-app session → binding → User); `auth.php` wiring is
  HOST responsibility (no provider registration).
- **Q11:** single truth-first write-hook `Bindings::onStoreUpdate(UpdateStored)`
  (+ login-completion `onLogin`); no-guess extraction rule.
- **Q12:** deferred to Phase 5g; this layer ships the guards + bindings the
  hosting stubs wire to.

## Tasks

- [x] **Task 1: binding store.** Migration
      `migrations/2026_09_08_000100_create_tl_user_bindings_table.php`
      (nullable morph, stable `tl_user_id`, nullable `account_id`, `contact_lost`,
      unique + morph index). `TlUserBinding` model: `@property` docblock for
      phpstan, casts, `user()` morphTo, `resolver()`, `markContactLost()`,
      `bindingFor()` (Q7 chain), `resolveAccountId()`, `ensureBinding()` upsert.
      `IdentityConfig`: config/env accessor (primary account, default sender,
      miniapp freshness window, bot-token chain identical to the middleware).
- [x] **Task 2: write-hook façade `Bindings`.** `bindLaravelUser()` (Q8 primary
      write API), `findTF()` (DX surface), `onStoreUpdate(UpdateStored)` (Q11
      truth-first register + `contact_lost` clear), `onLogin()` (login-completion),
      `telegramUserIdFromModel()` (exact-typed, no-guess extraction).
- [x] **Task 3: traits + message/channel (Q9).** `HasTelegram`
      (telegramBindings morphMany, routeNotificationForTelegram override +
      default, `findTF()`, `telegramBinding()`), `HasUserTelegram` (composes
      HasTelegram; `bindTelegramUser()`, `telegramUserId()`,
      `telegramSessionAccount()`), `TgUser` (abstract base Model so trait bodies
      are ALWAYS phpstan-analysed). `TelegramMessage` value object;
      `TelegramChannel` with injectable sender callable + BotClient default
      (no-op when no token; never throws on null route/payload).
- [x] **Task 4: guards (Q10).** `TgWebAppGuard` (HMAC via instantiated
      `VerifyMiniAppInitData`, freshness window `miniapp_auth_max_age` default
      1800s, `account_id` request override, binding→User or guest).
      `TgSessionGuard` (callable → request attribute → session keys, binding→User).
- [x] **Task 5: tests.** testbench `tests/Identity` suite (sqlite `:memory:`):
      `TestCase`, Support models (`TestContactableUser`, `TestAppUser`,
      `TestPlainUser`, `TestIntRouteUser`, `TestNullRouteUser`,
      `TestTelegramNotification`), `TlUserBindingTest`, `BindingsTest`,
      `HasTelegramTest`, `HasUserTelegramTest`, `Channels/TelegramChannelTest`,
      `Guards/TgWebAppGuardTest`, `Guards/TgSessionGuardTest`.
      68 tests, 131 assertions, green.
- [x] **Task 6: docs.** `docs/identity.md` (traits, channel, guards, findTF
      tenancy caveat) + this spec/plan + roadmap Phase 5b ticks.
- [x] **Task 7: gate.** phpstan clean; `vendor/bin/phpunit tests/Identity`
      green; full `composer verify` green; `bin/standalone-smoke.php` exit 0;
      golden gate updated for the app-owned migration.

## Gate evidence (2026-09-08)

- `vendor/bin/phpunit tests/Identity --no-coverage` → `OK (68 tests, 131 assertions)`.
- `composer verify` → `Tests: 669, Assertions: 13392, Skipped: 5` + `[OK] No errors`.
- `php bin/standalone-smoke.php` → exit 0.
- `vendor/bin/phpunit tests/Schema/ShipDialGoldenTest.php` → `OK (7 tests, 580 assertions)`.

Known limitation: `phpunit.xml.dist` is not editable (constraint) and lists no
`tests/Identity` suite, so the full `composer verify` phpunit pass runs Identity
by path only; the 5 skipped tests are the `TELEFRAME_PG=1` Postgres track.

**Commits:** none (coordinator commits; Phase 5a files in the tree remain owned
by the 5a worker).