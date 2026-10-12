# Usage — RAW MTProto: the wire under everything

> **Release area 3 of 5.** The binary MTProto 2.0 layer — what `user()` /
> `botMtproto()` / the TStore channels dial under the hood. Pure PHP, no
> Laravel required (`MeRezaRezaei\Teleframe\Core\MTProto\*`). Zero-regex by
> hard rule — the TL codec is a tokenizer, and session strings are
> **credentials** (never commit, never log).

---

## 1. Layer note (read first)

The wire speaks **Layer 227** (`EncryptedConnection::LAYER`); the method
catalog is **Layer 229** (`Teleframe::schemaLayer()`). This gap is
**intentional** — the wire is pinned, the catalog advances. Never "fix" it.

## 2. Sessions — `SessionData`

The session is a stateless DTO carrying the crypto keys + connection context:

```php
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;

$session = new SessionData(
    dcId: 2,
    authKey: $binaryAuthKey,          // raw bytes, 256 bytes for the auth key
    serverTimeDelta: 0, seqNo: 0,
    userId: 900, serverSalt: 0,
);

$exported = $session->exportString();               // base64, portable
$restored = SessionData::importString($exported);   // throws InvalidArgumentException
```

Export format: `base64("dcId:base64(authKey):userId:serverTimeDelta:serverSalt")`
— legacy 4-field strings (no salt) import with salt 0. Prefer
`Teleframe::fromSession(...)` for reconstruction in Laravel.

## 3. `Client` — the driver

```php
use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;

$client = new Client(
    apiId: 123,
    apiHash: 'abc…',
    session: $session,       // or null → dialed on first use
    live: true,              // null = defer to config('teleframe.live_mode')
    logger: $psr3Logger,     // optional; NullLogger by default (silent)
);
```

- `setSession(SessionData)`, `getSession(): ?SessionData`, `setProxy(array)`,
  `setLogger(LoggerInterface)`, `openSocket()`, `live(): static`.
- `call(string $method, array $params = []): array` — one RPC.
- `callMany(array $requests): array` — a batch of RPC bodies.
- `callToHost(string $host, int $port = 443): array` — dial a specific DC.
- `compute2faProof(array $accountPassword, string $password): array` — 2FA SRP
  proof (see `PasswordCalculator`).
- Keepalive: the connection idle longer than
  `Client::KEEPALIVE_IDLE_SECONDS` (45 s) gets a `ping_delay_disconnect`
  before its next RPC — the 60 s server idle kill is never hit.
- DC table: `Client::DC_IPS` (1–5), `DEFAULT_PORT = 443`.

## 4. `EncryptedConnection` — the transport

```php
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;

$conn = new EncryptedConnection($session, $socket); // socket optional (client dials)

$conn->ping(50);                    // ping_delay_disconnect; returns pong
$conn->idleSeconds();               // float since last frame
$result = $conn->call('help.getNearestDc');                 // one constructor
$batch  = $conn->callBatch([$body1, $body2, …]);            // container batch
$conn->isInited();                  // handshake done?
$conn->close();
```

- **Batching is bounded**: `MAX_BATCH_MESSAGES = 1020` and
  `MAX_BATCH_CONTAINER_BYTES = 32768`. `callBatch()` validates with the static
  `EncryptedConnection::assertBatchWithinBounds(array $bodies)` and throws
  before dialing when a batch exceeds either bound.
- `LAYER = 227` (see §1). `getServerSalt(): int`, `lastSessionData()`.
- Every RPC is wrapped in `invokeWithLayer` automatically.

## 5. TL constructors and the codec

TL is written as **arrays with a `'_'` constructor key**, and encoded/decoded
with the zero-regex codec:

```php
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;

$binary = TLEncoder::encodeObject('resPQ', $args);          // string binary
$parsed = TLDecoder::decodeObject($binary, $offset);        // array
```

Constructor names are looked up in `TLRegistry` (the zero-regex catalog under
`src/Schema/schema/`); `ParsedSignature`/`TLSignatureParser` handle
deserialization. Typed helpers exist for the common peers:

```php
use MeRezaRezaei\Teleframe\Core\Types\InputPeer;

InputPeer::user(900, 'access_hash_abc');    // ['_' => 'inputPeerUser', …]
InputPeer::channel(789012, 'hash_def');
InputPeer::chat(45678);
InputPeer::self();
```

## 6. `Methods` — the fluent builders

The curated method catalog ships as builder groups; each builder ends in
`toRequest(): array` (the same TL array shape from §5), then hand the array to
your client (via `UserAccountScope::call` / `BotAccountScope` / raw
`$client->call`):

```php
use MeRezaRezaei\Teleframe\Core\Methods\Methods;

$req = Methods::messages()->sendMessage()
    ->peer(InputPeer::channel(789012, 'hash_def'))
    ->message('hi')
    ->randomId(random_int(1, PHP_INT_MAX))
    ->toRequest();

$req = Methods::messages()->getHistory()
    ->peer(InputPeer::channel(999))
    ->limit(20)
    ->toRequest();

// Bot-API-flavored surface through the same registry:
$req = Methods::bots()->sendMessage()->chatId('@channel')->text('hi')->toRequest();
```

Groups: `messages()`, `users()`, `contacts()`, `account()`, `auth()`, `help()`
plus magic `__callStatic` resolution for anything else in the catalog
(`Methods::bots()` included — `src/Bot/Methods/Generated/Bots.php`).

## 7. 2FA / SRP — `PasswordCalculator`

`compute2faProof()` is the callable SRP proof for `account.checkPassword`.
The crypto helpers enforce the known-parameter policy:

```php
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PasswordCalculator;

PasswordCalculator::assertKnownSrpParams($g, $p);            // RFC 7919 known groups only
PasswordCalculator::assertSrpScalarsValid($srpB, $p, $u);    // reject invalid scalars
$proof = PasswordCalculator::computeSrpProof($accountPassword, $password);
```

Standard flow: `account.getPassword` → validate params/scalars → `computeSrpProof`
→ `account.checkPassword`. (The login wizard itself is
`teleframe:login` + `Laravel\Services\TeleframeAuthService`.)

## 8. Verification note

Anything that disagrees with `src/Core/MTProto/` is wrong — these docs included.
The single-class test witnesses (`tests/Core/Wire/ClientCallManyTest.php`,
`TLCodecTest.php`) show the minimal construction pattern:
`new Client(apiId: 1, apiHash: 'h', session: $session, live: true)` then
`call`/`callMany` against a stubbed socket.

---

Related: [notify.md](notify.md) (client acquisition + sending ergonomics) ·
[store-backup.md](store-backup.md) (TStore dials these channels) ·
[app-framework.md](app-framework.md) (what consumes the updates).