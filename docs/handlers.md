# Handlers — one class, one pipeline

> **Phase 3.** The handler substrate + facade. Every update that reaches a
> TENANT (from the Redis bus or the ingest event) collapses into ONE
> immutable uprate and flows through ONE 26-line middleware onion into your
> handler. `MeRezaRezaei\Teleframe\Teleframe` is the primary face; the
> ingest-only `Teleclient` is retained unchanged.

---

## 1. Subscribe

```php
use MeRezaRezaei\Teleframe\Teleframe;
use MeRezaRezaei\Teleframe\Handler\Update;

$teleframe = app(Teleframe::class);

// catch-all: every stored update reaches this handler
$teleframe->onMessage(function (Update $u) {
    // $u->constructor()   '' on the event path (mirror), else the raw name
    // $u->accountId       the tenant the update landed under
    // $u->array           the raw update payload
});
```

Receive-ordered routing (first match wins, then priority desc, then
registration order):

```php
use MeRezaRezaei\Teleframe\Handler\Update;

$teleframe
    ->on('updateNewMessage', MyMessageHandler::class, priority: 5)
    ->onMessage(FallthroughHandler::class, priority: 1); // catch-all * 
```

Match grammar is zero-regex: exact constructor (`updateNewMessage`), trailing
`*` prefix (`updateNew*`), or bare `*` catch-all.

### Route args (`/start %s`)

A single sscanf `%s` token captures the rest of the matched constructor
(zero-regex, sscanf only). The captured segment lands in a `string $arg`
handler parameter:

```php
$teleframe->on('/start %s', function (Update $u, string $arg) {
    // $arg === '42' for constructor '/start 42'
});
```

- **Zero or one `%s` token per pattern** — registering `/start %s %s` throws
  `MultipleSscanfTokensException` at registration.
- First-match-wins (priority, then registration order) is unchanged; exact
  and `*`-prefix patterns still carry no args.
- The matcher result itself exposes `HandlerMatch::$args` / `HandlerMatch::arg()`
  if you route manually.

## 2. Hammer-weight matching, feather-weight hydration

Routing uses the raw constructor name (cheap); the mirrored Eloquent model is
hydrated lazily, only when your handler asks:

- a parameter typed `Update` receives the current uprate;
- a parameter typed `TelegramContext` receives the context wrapper;
- any other class parameter is resolved from the container, so a **DI
  constructor can reach a `TlInstanceModel` of any type** without touching
  the wire;

```php
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;

$teleframe->on('user', UserSaved::class);

// UserSaved::__invoke(Update $u, TlUserUser $user)
```

### Validation wrapper (`ValidatedUpdate`)

Opt in to shape guardrails by type-hinting `ValidatedUpdate` — the FormRequest
analog. The dispatcher constructs it from the same active `Update`, so no
binding setup is needed on your side:

```php
use MeRezaRezaei\Teleframe\Handler\ValidatedUpdate;

$teleframe->onMessage(function (Update $u, ValidatedUpdate $v) {
    if (! $v->valid()) {
        $errors = $v->errors(); // ['from' => [...]], ['chat' => [...]], ...
        return;
    }
    $messageText = $v->update()->array['message']['text'] ?? null;
});
```

- `valid(): bool` — every guardrail on `{from_, chat, message}` passed.
- `errors(): array` — field-path → message list (empty when valid).
- `rules(): array` — the guardrail rules, documented like a FormRequest.
- **No failure magic (Q6a):** never throws on invalid input, never
  auto-replies — reads only.

## 3. Reply explicitly — the Q2d echo loop

Handlers return `void`; replies go through the facade's `send()`, which
records `random_id`/`msg_id` in the shared PSR-16 registry. When that reply's
echo comes back, the **first middleware** (`EchoEliminator`) recognizes it and
terminates the chain — your handlers never re-run on your own messages.

```php
$teleframe->on('updateNewMessage', function (Update $u) use ($teleframe) {
    if ($u->selfOriginated) {
        return; // your own previous reply's echo
    }
    $teleframe->send($u->accountId, ['random_id' => random_int(1, PHP_INT_MAX), 'text' => 'got it']);
});
```

A handler that opts into its own echoes passes `onOwn: true`. To disable the
eliminator, bind a fresh `UpdateDispatcher` without it.

### Replay dedup + `seen_replays`

A second middleware (`ReplayDedup`) sits after the echo eliminator on the same
onion. Every uprate carries a deterministic content-hash identity
`Update::updateId()` = `sha1(accountId . "\0" . ts . "\0" . raw_json(array))`
— stable across deploys and processes, derived from the entry, so a
re-ingested payload dedups regardless of handler mutability.

The first dispatch marks the id in the PSR-16 `teleframe.handler.seen`
namespace (TTL 60s default); a duplicate within the window is dropped before
any user handler runs:

```php
$dispatcher->dispatch($first);   // runs; mark teleframe.handler.seen
$dispatcher->dispatch($second);  // same updateId → skipped
$dispatcher->seenReplays();      // 1
```

The TTL is constructor-injected (`UpdateDispatcher(..., dedupTtl: 60)`); a
Laravel coordinator supplies `teleframe.handler.dedup_ttl`. On the fake
running-mode surface the drop counter is `FakeDispatcher::$seenReplays` after
`run()`.

## 4. Both intake rows are one pipeline

- **Event path:** an `UpdateStored` event (model + account) is collapsed into
  an uprate via `Update::fromMirror` and dispatched.
- **Bus path:** `IngestConsumer`'s matched entries also fan into handlers via
  `HandlerSink` (payload shape identical — only the transport differs).

Plain-PHP hosts can run the same pipeline with `FakeDispatcher`:

```php
use MeRezaRezaei\Teleframe\Handler\{\HandlerRegistry, Pipeline, InMemoryCache};
use MeRezaRezaei\Teleframe\Testing\FakeDispatcher;

$registry = HandlerRegistry::instance();   // one shared table per process
$registry->onMessage(static function (Update $u): void { /* ... */ });

$fake = new FakeDispatcher(
    [['update' => ['_' => 'updateNewMessage'], 'account_id' => 7]],
    $registry,
    $container,   // PSR-11 (Laravel's app works)
    new InMemoryCache(),
);
['dispatched' => $runs, 'sent' => $replies] = $fake->run();
```

The fake drives the REAL pipeline (real registry, real onion, real
eliminator); only transport is faked.

## 5. `Teleframe` composes the modules

`ingest()`, `ingestResponse()`, `onMessage()`, `on()`, `run()`, `user()`,
`chat()`, `channel()`, `route()`, `backup()`, `schemaLayer()`, `send()`.
Anything not surfaced forwards one hop to the owning module (`__call`).
Everything delegates to the package's real singletons via PSR-11.

## 6. Observing & reacting programmatically

A first-class observe/react surface for company apps: subscribe to an
**account-scoped** stream, and toggle *listen / ignore* per peer in real time
— all through the facade, all through the same handler pipeline.

```php
use MeRezaRezaei\Teleframe\Teleframe;
use MeRezaRezaei\Teleframe\Handler\Update;

$teleframe = app(Teleframe::class);

// react only to updates that land under account 7 (company account)
$teleframe->observe(7, function (Update $u) {
    // $u->accountId === 7 here; frames from other accounts never reach you
});

// '*' = every account
$teleframe->observe('*', function (Update $u) {
    // every stored update, any tenant
});

// real-time listen/ignore toggle for one (account, peer) pair
$teleframe->ignore(7, peerType: 2, peerId: 445003); // channel 445003 → store_only
```

### Account scoping

`observe($accountId, $handler, $priority)` registers an
`onMessage`-style `*` handler (same priority ordering, echo elimination,
replay dedup, first-match-wins) whose wrapped dispatch first checks
`Update->accountId` against the scope and skips on mismatch. Scope `'*'`
matches every account. Handler forms match the rest of the substrate: a
closure `function (Update $update): void`, a container-addressable invokable
class name, or a `[Class::class, 'method']` pair. The observed handler flows
the REAL pipeline; the DI-shaped parameter receiving is the dispatcher's.

If you register several overlapping observers, remember the substrate's
first-match-wins contract — only the highest-priority registration sees a
given update, so register one observer per scope (or tune `$priority`).

### Listen/ignore in real time

`ignore($accountId, $peerType, $peerId)` writes a `tg_update_routing` row in
mode `store_only` (default peer `0` = the whole account): the ingest event
path stops emitting app events for that peer while the final truth is still
stored — the notify/log-channel case. It is best-effort: it acts only when
the host exposes the DB + event-dispatcher seam (a full Laravel app), and is
a documented no-op on a plain-PHP container. The `RoutingSettingsObserver`
picks the write up and fans `RoutingSettingsChanged`, so a running daemon
re-reads the decision without restart.

### The company-account use-case

One account, per-peer control:

```php
$teleframe->observe(7, OrderHandler::class);   // react to store messages
$teleframe->ignore(7, 3, 445003);              // but never to the notify channel
// flip it later — live, no restart
$teleframe->ignore(7, 1, 501558149);           // mute a user too
```

## 7. Hot-reload live-account orchestration

The company-account live driver: `tf_accounts` is the DB registry of what the
daemon drives, `tg:bus:reload` is the bell, and the lifecycle events + the
per-peer routing rows turn an operator edit into live behavior with **zero
restart**.

### Lifecycle events: `AccountAdded` / `AccountRemoved` / `BotAdded`

A `TfAccount` row (table `tf_accounts`) is the live "which account/bot is
managed" answer. The model wires `AccountLifecycleObserver` through its own
`booted()` hook — no provider registration, a host that never loads the model
pays nothing. After every create/update/delete the observer (1) publishes a
best-effort reload signal on `StreamSchema::RELOAD_CHANNEL`
(`tg:bus:reload`) and (2) dispatches a lifecycle event through the app's
event dispatcher:

```php
// the reload payload a listening daemon reads (kind 'accounts'):
['kind' => 'accounts', 'account_id' => 501558149, 'change' => 'added']
// change: 'added' (created) | 'changed' (updated, re-announced) | 'removed'

// the dispatched events — kind rides ON the payload, no type-switching:
//   kind 'account' → AccountAdded($accountId, 'account', $label, $change)
//   kind 'bot'     → BotAdded($accountId, $label, $change)   // instanceof AccountAdded
//   removal        → AccountRemoved($accountId, $kind, $label, 'removed')
```

All three are plain DTOs (public readonly fields, plain-PHP constructible,
dispatchable through any `Illuminate\Contracts\Events\Dispatcher`). Subscribe
with a plain listener:

```php
Event::listen(AccountAdded::class, function (AccountAdded $event) {
    if ($event->isBot()) { /* a BotAdded also lands here */ }
    // $event->accountId(), $event->kind(), $event->label, $event->change
});
Event::listen(AccountRemoved::class, function (AccountRemoved $event) {
    // a managed account/bot just left the set
});
```

Best-effort by contract (mirrors `RoutingSettingsObserver`): a host with no
working bus redis still gets every event — only the publish is skipped, and a
`RuntimeException` mid-publish is swallowed so an account write is never a
hard failure. Seam-proven by `tests/Control/AccountLifecycleObserverTest.php`
and `tests/Laravel/Models/TfAccountTest.php`.

### Registry vs bootstrap seed

`tf_accounts` (migration
`2026_09_22_000100_create_tf_accounts_table.php`) carries one row per
Telegram account_id: `id` (the Telegram account_id, NOT auto-increment),
`label`, `dc_id`, `port`, `kind` (`'account'` | `'bot'`), `enabled`, and
`session_ref` — deliberately OPAQUE (a reference into the vault/host session
resolver, NEVER a session string; the only consumer is the daemon's
`SESSION_RESOLVER_KEY` seam below).

`config('teleframe.daemon.accounts')` remains the **bootstrap seed**. When
`tf_accounts` has enabled rows, `DaemonCommand::resolveAccounts` makes the
registry the LIVE set: the seed only supplies dialable credentials
(`session_string`, `api_id`, `api_hash`) for the ids the registry lists. A
registry row with no matching seed entry is dialed through the host's
`SESSION_RESOLVER_KEY` seam — a callable that turns the opaque `session_ref`
into `SessionData` (results are memoized per `session_ref` per process, so
staying accounts are not re-resolved every rotation). Unbound (the container
default), a credential-less registry row is warned (by account_id only, never
the ref) and skipped. An empty or unmigrated table falls back to the seed
governing as-is — no behavior change on legacy hosts.

### The end-to-end hot toggle (observe/ignore + per-peer routing)

The listen/ignore decision is a `tg_update_routing` row; the ingest seam
re-reads it live. `Teleframe::observe()` / `ignore()` (§6) are the
programmatic faces; an operator editing the DB flows the same wire:

1. **The operator flips a rule row** in `tg_update_routing` (or calls
   `$teleframe->ignore($accountId, $peerType, $peerId)`, which writes
   `store_only`). `RoutingSettingsObserver` picks the write up, refreshes the
   Redis-2 cache `tg:routing:rules:{account}` from the DB, dispatches
   `RoutingSettingsChanged`, and publishes kind `'routing'` on
   `tg:bus:reload`.2. **The running daemon's ingest seam answers from the cache.** The
   `MirrorIngesterFactory` seam builds an `UpdateRouter` over the cache —
   cache-first, DB source-of-truth fallback — so the next update for that
   (account, peer) is classified `act_on` (store + emit) or `store_only`
   (store silently) with zero restart.
3. **The event stream switches.** `RoutingEventGateway` emits `UpdateStored`
   only for `act_on` peers, so your `observe()`'d handlers are the ones that
   react; an unmarked peer stays `store_only` by default.

> ### 🔴 `onMessage` handlers never fire until you run `teleframe:routing`
>
> The engine's dispatch default is `store_only` — deliberately, for loop
> prevention: a notify/log channel must never become an app input. That is
> safe, but it meant the `UpdateRouter` answered `store_only` for every peer,
> so `RoutingEventGateway::emit()` never dispatched `UpdateStored`, so your
> handlers, the Centrifugo feed and TStore were **all dead on the normal
> path while everything looked healthy**. Nothing in `src/` could ever write
> the opt-in row.
>
> `php artisan teleframe:routing <account>` is that missing opt-in. The
> account is a **required argument**, not a flag; `--mode=act_on` is the
> default:
>
> ```bash
> # one account, one peer:
> php artisan teleframe:routing 501558149 --peer=2 --peer-id=445003
>
> # the account-wide catch-all peer (0,0) — flips every unmarked peer:
> php artisan teleframe:routing 501558149 --account-wide
>
> # see what it would do, write nothing:
> php artisan teleframe:routing 501558149 --account-wide --dry-run
> ```
>
> Flags: `--mode=act_on|store_only`, `--peer=` (1=user 2=chat 3=channel),
> `--peer-id=`, `--account-wide`, `--priority=0`, `--dry-run`. It upserts
> through the `UpdateRoutingRule` **model** (never the query builder — the
> observer that refreshes the hot cache hangs off the model lifecycle), so
> the already-wired observer hot-reloads the decision into Redis and
> publishes on the reload channel. **No daemon restart.**
>
> Two things this command will not let you do by accident: the account must
> be a positive integer (routing is per account, never global), and the
> account-wide catch-all requires the explicit `--account-wide` confirmation.
> There is **no config key that sets the routing mode** — the DB row is the
> only source of truth, and this command is the only thing in the package
> that writes it.
>
> **If you see handlers registered but never called**, and the row in
> `tg_update_routing` really does say `act_on`: check that a **consumer** is
> running (`teleframe:daemon` or `teleframe:ingest`). A default-mode daemon
> drains the bus on every rotation, so a registered handler plus an armed
> route is still inert with nothing consuming `tg:stream:updates`.

Wiring pinned by `tests/Ingest/MirrorIngesterFactoryRoutingCacheTest.php`
(cache wins over a flipped DB underneath the already-built seam; `refresh()`
reaches the seam live; the null-cache path still routes from the DB) and
`tests/Handler/ObserveApiTest.php`.

> **Account list branch IS live (Cycle 5, item 1):** a `tg:bus:reload`
> kind `'accounts'` payload IS consumed — `DaemonCommand` arms a reload watch
> (`armReloadWatch`/`noteReloadPayload`) and `reseatAccounts()` diffs each
> rotation via the pure `planReseat`, seating/stopping workers without a
> restart (`src/Teleframe/Daemon/Daemon.php` holds a mutable seat set;
> `src/Laravel/Console/DaemonCommand.php`; proof
> `tests/Control/DaemonLiveReseatTest.php`).

> **AUTO-REGISTRATION on discovery IS live (Cycle 5, item 2):** a NEW
> registry row spotted by the reseat (an account/bot the daemon did not drive
> before) is seated through the `SESSION_RESOLVER_KEY` seam, dispatches
> `AccountAdded`/`BotAdded` (or `AccountRemoved` on unseat) on the
> DB-discovery path, and pre-seeds the account's default route through the
> `ROUTE_SEEDER_KEY` seam (`AccountRouteSeeder` default: a `tg_update_routing`
> catch-all row at peer 0/0 with mode `store_only` + a Redis-2 refresh, so the
> router answers the account immediately). Unseating forgets the account's hot
> routing cache. Proof: `tests/Control/DaemonAutoRegistrationTest.php`.

## 8. Laravel-user session → READ-ONLY client (embedded tgweb)

The embedded Telegram Web client is a **separate, default-behaviour thing**.
Nothing in its internals carries auth; the Laravel host binds it to the user
session at the boundary (routes + the relay vault terminal seam), and while a
Laravel-user scope is live every access to the managed telegram/bot accounts
is **READ-ONLY**.

### Standalone (unchanged)

Outside a Laravel-user scope the client keeps its full behaviour, byte for
byte. A scope is only marked read-only by (a) the `tg.readonly` request
middleware when a Laravel user session is present on a scoped route, or (b) an
operator-pinned resolver (below). A session-less host — the guard-free
harness, a console/daemon process — never opens a frame, so the vault
dispatch is the raw callable and sends/writes pass through.

### The enforcement seam

`Laravel\Services\RelayVaultTerminalFactory::dispatch()` builds the callable
`VaultMtprotoAnswerer` consumes. When the shared `ReadOnlyScope` is injected
(the provider does), `dispatch()` returns `ReadOnlyVaultDispatch` around the
real dispatch. On **every RPC** the wrapper asks the scope: read-only →
non-allow-listed methods get `rpc_error` (error_code `400`,
`READONLY_SCOPE_FORBIDDEN`) and never reach the real dispatch; allowed methods
pass through untouched; an unmarked scope is a zero-cost pass-through.

The **allow-list IS the contract** (explicit READ-ONLY allow-list, not a write
block-list — anything not listed is refused under a read-only scope):

```php
// prefix contract (every method under these is a read shape):
help.*            updates.*            channels.get*     photos.get*
auth.export*      contacts.get*        // + exact read contacts resolvers below
// exact read methods outside the prefixes:
users.getUsers / users.getFullUser
messages.getDialogs / getHistory / getMessages / getSearch / getMessageEditData
messages.getMessageReactionsList / getPeerDialogs / getChats / getFullChat / checkChatInvite
account.getAccountTtl / getPassword / getPrivacy / getAuthorizations
account.getNotifySettings / getContactSignUpNotification / checkUsername
contacts.search / contacts.resolveUsername / contacts.resolvePhone
```

So `messages.sendMessage`, `messages.sendMedia`, `channels.createChannel`,
`channels.leaveChannel`, `account.updateProfile`, `contacts.addContact`,
`contacts.importContacts`, `contacts.block` etc. are all refused while a
read-only scope is on. Namespaces that also carry writes are never
prefix-whitelisted whole — `contacts` read shapes are `contacts.get*` plus the
exact resolver/list names, so a state-changing contact method can never ride
the namespace. Classification is exact match / `str_starts_with` — zero regex.

### Binding at the route boundary

* `TeleframeServiceProvider` binds `ReadOnlyScope` as a process singleton,
  injects it into `RelayVaultTerminalFactory`, and aliases the middleware
  **`tg.readonly`** → `ScopeTelegramReadOnly`.
* The `telegramWeb` macro attaches `tg.readonly` to `GET /tg/{path?}` itself
  (plus whatever the host adds: `web`, `auth`, `tg.miniapp`, ...).
* `DashboardRoutes::register()` appends `tg.readonly` to the group, so `POST
  accounts/{id}/switch`, `GET/POST accounts/{id}/presence`, `touch`, `ticket`
  and the account control routes are scoped automatically.

`ScopeTelegramReadOnly` consults a **guard via the provider/host, never a
concrete user model**: by default `Auth::check()`; an injected callable lets a
host/tests drive it without a real login guard. Authenticated → `begin()` a
read-only frame for that request; no session → no frame (full behaviour). The
frame closes in `terminate()` when the response is sent.

### How an operator flips a scope to read-only

Two ways, both cheap and injectable (construct-not-own):

1. **Request frame (default):** open `/tg` or a dashboard control route while
   signed in as a Laravel user — that request is read-only.
2. **Pin the process (long-lived relays / WS terminals):** bind `ReadOnlyScope`
   with an always-true resolver (e.g. `new ReadOnlyScope(fn () => true)`), or
   call `begin()` once in the relay boot. Because the WS terminal's RPCs can
   outlive any one web request, the pinned resolver is the deterministic way
   to hold a relay read-only for every browser session against it.

Seam-proven by `tests/Laravel/Services/ReadOnlyVaultDispatchTest.php`
(refuses `messages.sendMessage`; passes `messages.getDialogs`; default scope
unchanged; middleware frames with a fake auth-resolver; factory wraps only
when a scope is injected).