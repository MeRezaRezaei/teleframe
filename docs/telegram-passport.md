# Telegram Passport Integration Guide

Telegram Passport is an end-to-end encrypted identity authorization system allowing services to securely receive user credentials (Passports, Driver's Licenses, ID Cards, Utility Bills, and Personal Details).

---

## 1. Setup RSA Keys & @BotFather

Generate an RSA 2048-bit keypair:
```bash
openssl genrsa -out storage/app/keys/passport_private.pem 2048
openssl rsa -in storage/app/keys/passport_private.pem -pubout -out storage/app/keys/passport_public.pem
```

Register `passport_public.pem` in [@BotFather](https://t.me/botfather) (`/mybots` ➔ Select Bot ➔ Bot Settings ➔ Telegram Passport).

---

## 2. Decrypting Passport Credentials in Laravel Webhook

When a user submits passport data, decrypt it in your webhook controller:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $update = $request->all();
        $message = $update['message'] ?? [];

        if (isset($message['passport_data'])) {
            $credentials = $message['passport_data']['credentials'];
            $privateKey = file_get_contents(storage_path('app/keys/passport_private.pem'));

            $decrypted = PassportDecryptor::decryptCredentials(
                encryptedData:   $credentials['data'],
                encryptedSecret: $credentials['secret'],
                privateKeyPem:   $privateKey,
                hash:            $credentials['hash']
            );

            // Access verified identity data
            $personal = $decrypted['personal_details'] ?? [];
            $firstName = $personal['first_name'];
            $lastName  = $personal['last_name'];
            $birthDate = $personal['birth_date'];

            return response()->json(['status' => 'verified', 'user' => $personal]);
        }
    }
}
```

> The decrypt above is the raw wire. To VERIFY A TELEGRAM IDENTITY you do not
> write this controller — the package ships the whole loop (see §3).

---

## 3. Verifying a Telegram identity with Telegram Passport (no glue code)

The package registers a complete HTTP surface, so a host never hand-wires the
nonce, the widget and the callback.

> **Read §3.3 first if you came here to log users in.** There are TWO flows and
> they are not the same thing. The three routes above ATTACH a verified Telegram
> identity to a user your Laravel session has **already** authenticated — they
> sit inside the host's `auth` guard and never authenticate anyone. The pair in
> §3.3 is the one that can: it starts with no Laravel session and ends with
> one. It is a **separate capability, off by default**
> (`teleframe.passport.anonymous_login`), and turning it on is a security
> decision you make deliberately.

### 3.1 Configure

```dotenv
TELEFRAME_PASSPORT_ENABLED=true
TELEFRAME_PASSPORT_PRIVATE_KEY=/absolute/path/to/passport_private.pem   # 0600
TELEFRAME_PASSPORT_NONCE_STORE=cache                                    # durable, see below
TELEFRAME_PASSPORT_BOT_ID=4242
TELEFRAME_PASSPORT_BOT_USERNAME=my_login_bot
TELEFRAME_PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----…"            # what @BotFather holds
```

`nonce_store` matters: a non-empty value binds the PSR-16 `CacheNonceRegistry`
so the nonce survives between the web request that issued it and the callback
that consumes it. An empty value uses the in-process memory registry, which
only works for a single-process test harness. Point it at Redis in
production — the one-time consume is a get-then-delete there too.

Then register the routes (same call the dashboard uses):

```php
// routes/console.php or AppServiceProvider::boot()
MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes::register();
```

### 3.2 The two endpoints

| Route | Name | Method | What it does |
| --- | --- | --- | --- |
| `GET {prefix}/passport` | `teleframe.passport.show` | GET | A page: status, "issue nonce", and a form that posts credentials to the callback. |
| `{prefix}/passport/start` | `teleframe.passport.start` | GET/POST | Issues a one-time nonce bound to the authenticated user and returns the Telegram handshake. |
| `{prefix}/passport/callback` | `teleframe.passport.callback` | GET/POST | Receives `data`/`hash`/`secret` + `from_id` and runs the whole chain. |

All three live in the dashboard group, so they carry the host's own
`web` + `auth` middleware: the flow cannot be started without an already
authenticated Laravel session, and the nonce is bound to that session's user.
Read §3.3 before assuming this logs anyone in — it does not.

`POST {prefix}/passport/start` answers:

```json
{
  "ok": true,
  "nonce": "…32 hex chars, one-time, bound to this session…",
  "expires_in": 900,
  "bot_id": 4242,
  "bot_username": "my_login_bot",
  "scope": ["personal_details", "email", "phone"],
  "request": { "bot_id": 4242, "scope": ["…"], "nonce": "…", "public_key": "…" },
  "telegram_url": "https://t.me/my_login_bot?start=…",
  "bot_command": "/passport …",
  "callback_url": "https://host.test/teleframe/passport/callback"
}
```

Your bot's `/start` handler reads the token and calls `requestPassport` with
`request` (the nonce travels to Telegram inside it), or you drop
`telegram_url` straight onto a login button. Telegram answers with
`message.passport_data.credentials`; forward that update to
`Teleframe::passport()->handleUpdate($update)` (the webhook path) or POST the
three credential fields plus the sender's `message.from.id` to
`teleframe.passport.callback` (the HTTP path). Either way the engine decrypts,
verifies the one-time nonce, resolves the identity and writes the binding for
the user that session is already authenticated as.

### 3.3 The two flows, and the one that can log a visitor IN

The three routes above are **ATTACH**. All of them live inside the dashboard
group and therefore inside your own `auth` middleware, and
`PassportController::callback()` refuses **401** when no authenticated session
is presenting the credentials. The engine then refuses the login outright if
the consumed nonce's user id is not the presenting session's user id. So by
construction there is no anonymous visitor anywhere in *that* flow: the only
user who can complete it is one your Laravel session authenticated first.

`TELEFRAME_PASSPORT_ENABLED=true` therefore gives you **verified Telegram
identity + a binding row on your existing user**. It does not give you a way
for an anonymous visitor to become a logged-in Laravel user, and no value of
any other key turns it into one.

**The login flow is a second, separate pair of routes**, and it is off until
you turn it on:

```dotenv
TELEFRAME_PASSPORT_ENABLED=true            # the decrypt + attach machinery
TELEFRAME_PASSPORT_ANONYMOUS_LOGIN=true    # ← THIS is the security decision
TELEFRAME_PASSPORT_UNKNOWN_USER=refuse     # the only shipped policy
TELEFRAME_PASSPORT_LOGIN_RATE_LIMIT=10     # per minute, per IP, both routes
```

| Route | Name | Method | What it does |
| --- | --- | --- | --- |
| `{prefix}/passport/login/start` | `teleframe.passport.login.start` | GET/POST | Mints the pending marker + the one-time passport nonce bound to it, and returns the same Telegram handshake shape as `passport/start`. |
| `{prefix}/passport/login/callback` | `teleframe.passport.login.callback` | GET/POST | Takes `flow` + `data`/`hash`/`secret` + `from_id`, and — on success — leaves a **Laravel session for the resolved user**. |

These two are registered in their OWN group: `web` + a throttle, and **without**
your `auth` guard, because authenticating someone who has no session is the
entire point. The attach routes stay behind `auth` where they belong. With
`anonymous_login` false the routes still EXIST and both answer **403**, so the
refusal is a decision you can see rather than a 404 you have to infer.

#### The exact flow

1. The browser GETs/POSTs `passport/login/start`. The server refuses **401** if
   the request has no session at all, and **403** if the browser is already
   authenticated. It then mints a **pending marker**: 128 bits of
   `random_bytes`, recorded — in one write, with a TTL — as *a SHA-256
   fingerprint of the session that just started the trip* plus *a digest of the
   one-time passport nonce it is about to hand out*. The response carries that
   nonce, the Telegram handshake, a `telegram_url` deep link carrying the
   nonce, and a `callback_url` carrying `?flow=…`.
2. The browser goes to Telegram with the nonce (inside `requestPassport`), and
   Telegram answers with the encrypted envelope. Your bot's `/start` handler, or
   the website widget, posts `data`/`hash`/`secret` + `from_id` + the `flow`
   handle to `passport/login/callback`.
3. The callback recomputes the fingerprint from the session **presenting it**,
   consumes the marker **one-time**, and refuses unless the two fingerprints
   match and the decrypted payload's nonce digests to the digest the marker
   recorded.

**Where identity comes from: the verified Telegram field, and nowhere else.**
There is no session to read a user id out of, so the field has to be the
identity — and it is bounded by four rules, all enforced in
`PassportAnonymousLoginService`:

1. **Only Telegram-vetted fields are read.** Only `secure_data` entries count.
   An email present only as a top-level plain scalar is a bare claim Telegram
   never vouched for, and it is refused. This is **unconditional** here, not
   gated on `require_verified`: the field is the *entire* identity on this path,
   so a vetted field is the price of admission rather than an opt-in. Setting
   `require_verified` too is strictly safer, never weaker.
2. **Exactly one field, compared for equality against exactly one host column.**
   A vetted email OR a vetted phone. Both present is refused rather than
   preferring one — a payload that disagrees with itself is evidence of a
   problem, not of a user — and so is a vetted value that disagrees with the
   payload's own plain scalar of the same type.
3. **One equality, one row.** `email_column` / `phone_column` (default `email`
   / `phone`) are the only columns consulted. There is no fallback and no scan.
4. **No live session is ever replaced.** A browser that is already
   authenticated is refused at both endpoints; the attach flow owns that case.

**What it will not do, by design:**

- **It never creates an account.** A verified field that matches no user is
  refused loudly and no row is written. `unknown_user=refuse` is the only
  shipped policy; any other value is refused *and named back to you* rather
  than quietly ignored, because whether any Telegram account holder may appear
  in your users table is your product decision, not this package's. Provision
  the user yourself and let them log in again.
- **It never acts on an unvetted claim**, as above.
- **It never leaks a credential.** No session id, flow token, nonce, credential
  envelope or decrypted field value reaches a log line, a response, a redirect
  or a URL. The store holds two digests; the raw session id and the passport
  nonce are never persisted anywhere.

#### Why a leaked flow token logs nobody in

The `flow` handle travels in a URL, so it lands in referrer logs, proxy logs
and browser history. That is why the session binding is **mandatory** rather
than a nicety: the marker is bound to a fingerprint of the browser that
fetched it, so holding the token — even together with valid credentials —
completes nothing from any other browser. The binding is not a branch a caller
can skip: `login()` takes a **non-nullable** `string $sessionFingerprint` with
no default, a blank one is refused rather than digested, and both endpoints
refuse 401 before minting or consuming anything.

Both flows are proved by tests that go through the real routes, not the service
by name: `tests/Laravel/Http/Dashboard/PassportAnonymousLoginFlowTest.php`
carries the whole journey plus the cross-browser refusal, the replay, the
expiry, the unvetted claim, the unmatched field, the leak scan and the default-
off case; `tests/Passport/PendingLoginRegistryTest.php` and
`tests/Passport/SessionFingerprintTest.php` pin the store and the formula.

### 3.4 Reacting to a successful verification

`Auth::login` is not the package's to call in the sense that matters: the
package never calls Auth itself. When Laravel's `auth` factory is bound, the
package's default ride does call `Auth::guard()->login($user)` and regenerates
the session — and it is the **same ride** for both flows. On the attach path it
re-affirms the login your guard already made; on the anonymous path (§3.3) it is
what turns a resolved user into a session, and the regeneration is the
session-fixation defence. In a plain-PHP host with no `auth` bound, no ride is
installed and `null` is passed instead.

To add your own behaviour, bind the optional success hook:

```php
$this->app->instance('teleclient.passport.on-login', function (\Illuminate\Database\Eloquent\Model $user) {
    event(new UserVerifiedWithTelegram($user));
});
```

It runs after the binding is written and after the session is regenerated, so
the authenticated user is already in place.

### 3.5 What is refused

- `teleframe.passport.enabled=false` → both endpoints refuse (403) and no nonce
  is ever issued; a direct `login()` call throws loud.
- A malformed or oversized credential envelope → 422, and the pending nonce is
  NOT consumed (a bad request cannot burn a good nonce).
- A replayed callback → 422 from the engine's one-time nonce guard. The
  callback also runs the consume under a cache lock keyed on a SHA-256 digest
  of the payload, so two concurrent callbacks carrying the same credentials are
  serialized and only the first can write the binding.
- A missing/zero sender telegram id → 422 (Passport is user-only; a
  `sender_chat` sender is refused).
- A payload whose own fields contradict each other (e.g. a vetted email AND a
  phone) → refused by `FieldIdentityResolver`; the package never picks a winner
  and never re-reads a field itself.
