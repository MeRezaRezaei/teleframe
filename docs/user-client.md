# User MTProto Client Guide

The User MTProto client allows your Laravel application to connect to Telegram directly over native binary MTProto 2.0 (Layer 227+).

---

## 🔑 Prerequisites: Why `api_id` and `api_hash` are required

Telegram gates direct binary TCP socket connections to its core Data Centers behind application credentials.

1. Log in to [https://my.telegram.org](https://my.telegram.org) with your Telegram account.
2. Navigate to **API development tools**.
3. Create an application to obtain your **API ID** (integer) and **API Hash** (string).
4. Add them to your `.env`:
   ```env
   TELEGRAM_API_ID=12345678
   TELEGRAM_API_HASH="your_api_hash_here"
   ```

---

## 1. Authentication & Session Management

### Step 1: Initialize User Login
```php
use MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe;

// Initialize client with user's API credentials (or fallback to .env)
$user = Teleframe::user(
    accountId: 123456789,
    apiId: 123456,
    apiHash: 'your_api_hash'
);

// Request SMS / Telegram code
$result = $user->call('auth.sendCode', [
    'phone_number' => '+1234567890',
    'api_id'       => 123456,
    'api_hash'     => 'your_api_hash',
    'settings'     => ['_' => 'codeSettings'],
]);
```

### Step 2: Submit Verification Code
```php
$signInResult = $user->call('auth.signIn', [
    'phone_number'    => '+1234567890',
    'phone_code_hash' => $result['phone_code_hash'],
    'phone_code'      => '12345',
]);
```

### Step 3: Handling 2FA Cloud Password (if required)
If Telegram responds with `SESSION_PASSWORD_NEEDED`:
```php
$passwordInfo = $user->call('account.getPassword');

// Compute 2FA SRP proof
$srpProof = $user->mtproto->compute2faProof($passwordInfo, 'user_2fa_password');

$authResult = $user->call('auth.checkPassword', [
    'password' => array_merge(['_' => 'inputCheckPasswordSRP'], $srpProof),
]);
```

### Step 4: Exporting the Session (Stateless Architecture)
Teleframe does not lock your filesystem or require a local SQLite database. The entire cryptographic state (DC ID, 256-byte AuthKey, user ID, and clock delta) is packed into a lightweight string:
```php
$sessionString = $user->session->exportString();

// Save encrypted in your Laravel database:
$userModel->update([
    'telegram_session' => Crypt::encryptString($sessionString)
]);
```

---

## 2. Making Calls from Stored Session

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe;

// Load and initialize client from stored session string
$user = Teleframe::fromSession(
    sessionString: Crypt::decryptString($userModel->telegram_session),
    apiId: $userModel->api_id,
    apiHash: $userModel->api_hash
);

// 1. Send Text Message
$user->sendMessage(peer: '@username', text: 'Hello from Laravel!');

// 2. Fetch User Profile
$profile = $user->call('users.getFullUser', [
    'id' => ['_' => 'inputUser', 'user_id' => 987654321, 'access_hash' => 0]
]);

// 3. Search Chat History
$messages = $user->call('messages.search', [
    'peer' => ['_' => 'inputPeerChannel', 'channel_id' => 123456, 'access_hash' => 0],
    'q'    => 'invoice',
    'filter' => ['_' => 'inputMessagesFilterEmpty'],
    'limit' => 50,
]);
```

---

## 3. Calling ANY Telegram Method by Name

Section 2 called `messages.search` directly. That is not a special case:
**any method Telegram exposes can be called by its raw name** — you are not
limited to a curated list of hand-written helpers.

This is deliberate. The package owner asked for "whatever telegram activity
needed using mtproto call both for user and bot", and an earlier cycle of this
repo shipped a *curated subset* wearing a full-coverage label. It is gone. The
set of callable methods is **derived at runtime** from two packaged artefacts,
never from a list someone has to remember to update:

| Artefact | What it decides | Used by |
| --- | --- | --- |
| `src/Schema/schema/methods-mtproto.json` + `methods-botapi.json` | which names are API **functions**, and which transport owns each | `TeleframeClient::dispatch()` routing |
| `src/Core/MTProto/TL/TLRegistry.php` (loads the vendored `schema/sources/*.tl` mirror) | each mtproto method's **encodable signature** and real `crc32` | `EncryptedConnection` / `TLEncoder` |

Measured on the shipped package: **1008 catalogued methods, 972 of them
dialable by a host** (787 mtproto + 185 bot-http), against **20 curated fluent
builders**. A third figure circulates and should not: **789 mtproto** is
`823 − 34`, the *encodable* question, not the *dialable* one. See
`docs/user-client.md:208` for why those two differ.

### The four routes, all taking a raw name

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe;

// 1. The user scope — the direct MTProto route.
$user = Teleframe::fromSession($sessionString, apiId: $id, apiHash: $hash);
$user->call('account.getWallPapers', ['hash' => 0]);

// 2. Several in ONE round-trip (single msg_container). callMany() lives on
//    the CLIENT (the default user scope's connection), not on the scope.
Teleframe::callMany([
    ['method' => 'messages.getPeerSettings', 'params' => ['peer' => ['_' => 'inputPeerSelf']]],
    ['method' => 'channels.getChannels',       'params' => []],
]);

// 3. dispatch() — routes by name: mtproto names go to the user scope,
//    bot-api names go to the Bot API over HTTP. The '_' key is the name.
Teleframe::dispatch(['_' => 'account.getWallPapers', 'hash' => 0]);

// 4. The fluent builders, for the ~20 curated methods. Pure sugar over the
//    same three routes above — the curated list is a convenience, never a ceiling.
$request = Methods::messages()->sendMessage()
    ->peer(['_' => 'inputPeerSelf'])->message('hi')->randomId(1)->toRequest();
Teleframe::dispatch($request);
```

Bot accounts work identically over MTProto (`Teleframe::botMtproto(...)`, whose
scope exposes the same `call()`), and the Bot API client
(`Teleframe::bot(...)`) takes the same raw names. Bot-API names are catalogued
too, and need no TL signature — the Bot API is JSON over HTTP, not a
TL-encoded wire, so `Methods::isCallable('sendMessage')` is `true` with
`api === 'bot-http'`.

### Ask before you dial

`Methods::isCallable()` / `Methods::describe()` answer "can I call this, and
what do I pass?" **without opening a socket**, using the same two artefacts the
engine encodes against — so the answer cannot drift from the call:

```php
use MeRezaRezaei\Teleframe\Core\Methods\Methods;

Methods::isCallable('account.getWallPapers');   // true — catalogued AND encodable
Methods::isCallable('communities.getPeerLinkRequests'); // false — not-in-wire-mirror (layer 229, absent at 227)
Methods::isCallable('userProfilePhoto');       // false — that is a TYPE, not a method

$info = Methods::describe('account.getWallPapers');
$info['api'];          // 'mtproto' | 'bot-http'  → which transport owns the name
$info['crc32'];        // 0x...  — the id the registry parsed, never a guess
$info['params'];       // [['name' => 'hash', 'type' => 'long', ...]]  → what to pass
$info['return_type'];  // 'account.WallPapers'
$info['docs'];         // https://core.telegram.org/method/account.getWallPapers
$info['errors'];       // the RPC error codes this method can raise
```

`describe()` returns `null` for anything not callable, so a null check *is* the
reachability check.

**Two questions, and which one you are asking.** There is a subtlety worth
knowing before the list below, because it is easy to trip over:

| You ask | Entry point | `invokeWithLayer` |
| --- | --- | --- |
| *Is there a signature the encoder can use?* | `Methods::isCallable()`, `Methods::describe()` | **`true`** |
| *Can a host dial this by name?* | `MethodRegistry::isCallable()`, `MethodRegistry::unsupportedMethods()` | **`false`** |

Both are honest; they answer different questions. `Methods::describe()` is the
*encodable* question, and `invokeWithLayer` really is encodable — the client
hand-builds it and hands it to the encoder on every call. `MethodRegistry` is
the *dial-by-name* question, and you still cannot dial it yourself, because
binding its `query:!X` needs the request it is supposed to be wrapping. So when
you are deciding **"can my code call this?"** use `MethodRegistry`; when you are
deciding **"will this serialize if something calls it?"** use `Methods`.
`WireSchemaCoverage` carries both facts separately (`isEncodable()` versus
`classify()`), and the unsupported list below is the dial-by-name one.

### What the catalogue advertises but this client cannot call

**36 of the 823 catalogued mtproto methods are not callable by a host, and they
are not 36 of the same thing.** They fall into three families with three
different causes, because "is in the catalogue" and "is reachable" are two
different questions. `Methods::isCallable()` says `false` for all of them and
`Client::call()` throws — the two agree, live and offline — so you never have to
discover this by hitting it.

**One correction, because "they agree" was itself wrong for two of the 36.**
`invokeWithLayer` and `initConnection` are in the not-dialable set, and
`Methods::isCallable()` returns **`true`** for both: they are real constructors
the client hand-builds on every call, so they serialise fine. A host cannot dial
them by name, and the wire encoding is not the problem. So there are two
questions with two answers — **36 cannot be dialled by name**, **34 cannot be
serialised at all** — and the sentence above only held for the 34.

**1. `not-in-wire-mirror` — 23 methods. A layer gap.**
All nine `communities.*`, all eight `ephemeral.*`, plus
`auth.initFirebasePnvLogin`, `auth.firebasePnvSignUp`,
`auth.finishFirebasePnvLogin`, `messages.composeRichMessageWithAI`,
`messages.translateRichMessage` and `messages.requestChatJoinWebView`. The
catalogue is generated from `sources/api.tl` (**layer 229**) while the encoder
is seeded from `sources/api_full.tl` (**layer 227**). Those namespaces **do not
exist at layer 227 at all** — not a renamed signature, an absent one — so they
can never be serialized onto the wire. This is the same deliberate 227/229 gap
described under [Layers](#layers); it is not a bug and the wire is not moving.

**2. `envelope` — 11 methods. Wrappers, not endpoints.**
`invokeWithLayer`, `initConnection`, `invokeAfterMsg`, `invokeAfterMsgs`,
`invokeWithoutUpdates`, `invokeWithTakeout`, `invokeWithMessagesRange`,
`invokeWithReCaptcha`, `invokeWithApnsSecret`, `invokeWithGooglePlayIntegrity`
and `invokeWithBusinessConnection`. Each declares a generic
`{X:Type} ... query:!X = X`, so it exists to **wrap** some other request. You
never dial one by name: binding `query:!X` needs the wrapped request, which is
exactly what the client supplies for you. The two the client hand-builds
(`invokeWithLayer`, `initConnection`) really are on the wire, which is why
"is it on the wire" and "can I dial it" are genuinely separate questions — an
envelope can be both real and uncallable.

**3. `service` — 2 methods. Not RPCs.**
`req_pq` and `destroy_auth_key` come from `sources/mtproto.tl`, the service
layer the connection speaks during the auth handshake. They are not
addressable through the method-call API at all.

**You do not have to take any of this on trust.** The same classification, with
these counts computed live from the classifier, is printed by the tool:

```bash
php artisan teleframe:schema-audit
```

That is the difference between a documented limit and a discoverable one — the
command names every method in all three families and the file each verdict came
from, so for any method you care about you can ask the tool whether it is
callable and, if not, exactly which of the three families refuses it and why.

**A constructor is not a method.** Separately from those 36: `TLRegistry`
indexes constructors, so it also holds types (`userProfilePhoto`,
`auth.sentCode`). Sending a type name would be a wire error, so the resolver
requires the name to be catalogued *as a function* and reports `false` for a
type. A type is not counted among the 36, because it was never advertised as a
callable method in the first place.

### Layers

The wire advertises **layer 227** (`EncryptedConnection::LAYER`). The vendored
mirror and the schema catalogue are **layer 229**. That gap is intentional.
`describe()` never blends the two into one number: it returns `wire_layer` and
`schema_layer` under their own names (`schema_layer` is `0` when the manifest
cannot be read, meaning *unknown* — never *same as the wire*).
