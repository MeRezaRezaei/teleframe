# Identity — telegram bindings, notifications, guards

> **Phase 5b.** One binding table links three worlds: the STABLE telegram
> bigint identity (`tl_user_id`), the tenant account it lives under
> (`account_id` — null on single-tenant/plain-PHP hosts), and an optional
> Laravel `User` (nullable morph — works with NO Laravel User at all).
> Everything funnels through `MeRezaRezaei\Teleframe\Identity`; tenancy
> (Q7) is enforced in exactly one place (`TlUserBinding::bindingFor()`).

---

## 1. Migration

```bash
php artisan vendor:publish --tag=teleframe-migrations
php artisan migrate
```

Creates `tl_user_bindings` (unique `(tl_user_id, account_id)`, index
`(user_type, user_id)`). The morph carries no foreign key on purpose — deleting
a Laravel User never cascades into bindings, and dropping a telegram entity
never deletes the binding (`contact_lost` survives instead).

## 2. Bind a Laravel User to a telegram identity

```php
use MeRezaRezaei\Teleframe\Identity\Bindings;

// attach: telegram id 42, primary account (default tenant)
Bindings::bindLaravelUser($user, 42);

// explicit tenant:
Bindings::bindLaravelUser($user, 42, accountId: 7);
```

Idempotent — re-binding the same `(tg id, account)` updates the morph, never
duplicates. For the user-app login flow, record the telegram identity first
(no Laravel User yet), then attach the User when it is known:

```php
Bindings::onLogin($tgId, accountId: 7);   // login-completion write-hook
Bindings::bindLaravelUser($user, $tgId);  // when the Web-User is known
```

`Bindings::onStoreUpdate($event)` fires on every stored update and
registers/keeps the principal alive (clears `contact_lost`) — the package wires
this itself; you do not call it.

## 3. Contactable side — reach a User ON Telegram

`use MeRezaRezaei\Teleframe\Identity\HasTelegram;` on your User model:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use MeRezaRezaei\Teleframe\Identity\HasTelegram;

class User extends Authenticatable
{
    use HasTelegram;
}
```

### `User::findTF($tgId, $accountId = null)` — lookup

Resolves the Laravel User a telegram id belongs to. Tenancy default (Q7):

1. explicit `$accountId` → that tenant only;
2. else the configured primary account
   (`TELEFRAME_PRIMARY_ACCOUNT_ID` / `teleframe.primary_account_id`);
3. else the first account that ever hosted that telegram id;
4. else the no-account row;
5. else `null`.

There is **never** a silent cross-tenant global scan by tg id.

```php
$user = User::findTF(42);          // primary-account default
$user = User::findTF(42, 7);       // explicit tenant
```

### Notifications over the Telegram channel

`use MeRezaRezaei\Teleframe\Identity\Channels\TelegramChannel;` in a
notification's `via()`:

```php
use MeRezaRezaei\Teleframe\Identity\Channels\TelegramChannel;
use MeRezaRezaei\Teleframe\Identity\Channels\TelegramMessage;

public function via(object $notifiable): array
{
    return [TelegramChannel::class];
}

public function toTelegram(object $notifiable): TelegramMessage
{
    return TelegramMessage::text('Hello!', ['parse_mode' => 'HTML']);
}
```

Recipient resolution (Q9): Laravel-native `routeNotificationForTelegram()`
override when defined — return the `{tg_id, account}` route, or a bare tg id,
or `null`/`false`/`''` to skip delivery. When the method is NOT defined the
channel falls back to the notifiable's binding + the configured default sender
(`TELEFRAME_IDENTITY_DEFAULT_SENDER` / `teleframe.identity.default_sender`,
then the bot token). Delivery engine default is
`BotClient::sendMessage()`; inject your own sender callable for MTProto-aware
hosts:

```php
app()->instance(TelegramChannel::class, new TelegramChannel(
    sender: static fn (array $route, string $text, array $options) => /* ... */,
));
```

No token configured, no binding, or a `null` payload → the notification is
silently skipped (never throws).

## 4. Login-side — the user-app session IS a telegram identity

`use MeRezaRezaei\Teleframe\Identity\HasUserTelegram;` (composes all of
`HasTelegram`) on the mini-app User, or extend the ready-made base:

```php
use MeRezaRezaei\Teleframe\Identity\TgUser;

class MiniAppUser extends TgUser { /* host adds Authenticatable/Notifiable */ }
```

```php
$user->bindTelegramUser(42);          // record the logged-in telegram id
$user->telegramUserId();              // -> 42 (primary account default)
$user->telegramSessionAccount();      // -> tenant account of the session
```

## 5. Guards

Two `Illuminate\Contracts\Auth\Guard` implementations (Q10). Wire them in your
**host** `auth.php` — no provider registration happens here:

```php
'guards' => [
    'tg-webapp' => [
        'driver' => 'custom',
        'provider' => 'users',
        'callback' => static fn ($app) => new TgWebAppGuard($app['request']),
    ],
    'tg-session' => [
        'driver' => 'custom',
        'provider' => 'users',
        'callback' => static fn ($app) => new TgSessionGuard($app['request']),
    ],
],
```

- **`tg-webapp`** — authenticates a Telegram Mini App from its init-data
  (`X-Telegram-Init-Data` header or `initData` input). HMAC via the existing
  `VerifyMiniAppInitData` core (identical token fallback chain — what
  `tg.miniapp` accepts, this guard accepts), plus a freshness window
  (`TELEFRAME_MINIAPP_AUTH_MAX_AGE`, default 1800s) that **rejects replayed
  init-data**, plus binding resolution → bound `User` (guest when unbound). An
  `account_id` request input overrides the primary-account default.
- **`tg-session`** — resolves the logged-in user-app session to a User. Reads
  (low→high): an explicit callable resolver → request attribute
  `telegram_session_user_id`/`telegram_session_account_id` → Laravel session
  keys `telegram_session_user_id` / `telegram_user_id`. Written by your host at
  its login-completion hook (the `Bindings::onLogin()` point).

Both return `null` user (guest) in plain-PHP / unbound states — the nullable
morph keeps them safe with no Laravel User at all.

## Config reference

Read env-first via `Identity\IdentityConfig` (works with and without a Laravel
host; `config()` wins when present so `config:cache` is safe):

| Config key | Env | Default |
|---|---|---|
| `teleframe.primary_account_id` | `TELEFRAME_PRIMARY_ACCOUNT_ID` | — |
| `teleframe.identity.default_sender` | `TELEFRAME_IDENTITY_DEFAULT_SENDER` | — |
| `teleframe.identity.miniapp_auth_max_age` | `TELEFRAME_MINIAPP_AUTH_MAX_AGE` | `1800` |
| (bot token chain) | `TELEGRAM_BOT_TOKEN` | — |

A numeric default sender (an MTProto account id) leaves delivery to an injected
sender callable — no deliverer exists in-tree today for account ids.

## Note on the packaging gate

`tl_user_bindings` is app-owned (hand-authored, no `generated/` source), so the
ship-dial regenerate golden in `tests/Schema/ShipDialGoldenTest` exempts it via
`APP_OWNED_MIGRATIONS` — TL namespace migrations stay byte-strict.