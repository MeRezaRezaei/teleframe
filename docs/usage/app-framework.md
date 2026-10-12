# Usage — APP FRAMEWORK: the handler substrate and the compose facade

> **Release area 4 of 5.** The programmatic surface of the handler substrate —
> subscribe, route, filter, run pipeline fixtures. The in-depth operational
> guide (webhooks, bus intake, hydration, stages) stays in
> [handlers.md](../handlers.md); this page is the verified API reference.

---

## 1. The compose facade — `MeRezaRezaei\Teleframe\Teleframe`

One class composes every module behind PSR-11 seams (**composes, never owns**
— module singletons keep their full shape). `app(Teleframe::class)` gives you
the whole consumer layer:

```php
use MeRezaRezaei\Teleframe\Teleframe;

$t = app(Teleframe::class);

$t->onMessage(fn ($u) => …);                    // catch-all `*`
$t->on('updateNewMessage', MyHandler::class, priority: 5, onOwn: false);

$t->observe(7, fn ($u) => …);                   // account-scoped observer
$t->observe('*', Observer::class, kind: Teleframe::KIND_BOT); // kind filter

$t->run([['update' => $raw, 'account_id' => 7, 'ts' => 123]]); // REAL pipeline w/ fixtures
$t->ingest($rawUpdate, 7);                      // D1: store an update
$t->ingestResponse('messages.sendMessage', $params, $response, 7);
$t->send(7, ['random_id' => 123, 'text' => 'ok']);  // Q2d: echo-elimination registry
$t->user(7, 900); $t->chat(7, 900); $t->channel(7, 789012);  // entity lookups
$t->route('updateNewMessage', 'bus.main');      // bus route-table entry
$t->resolve(SomeService::class);                // PSR-11 passthrough
```

- `on()` / `onMessage()` return `$this` (chainable); `observe()` wraps a `*`
  handler with an account-id gate (scope `'*'` matches every account; a
  non-matching accountId skips the frame) plus an optional kind filter
  (`KIND_ANY = 0`, `KIND_ACCOUNT = 1`, `KIND_BOT = 2`) resolved from the
  `tf_accounts` registry when a DB seam exists; a DB-less container collapses
  the filter to `KIND_ANY`.
- `run(array $fixtures)` drives the **real pipeline** with fixture updates and
  returns every non-null handler result in fixture order (echoed /
  self-originated frames drop) — the plain-PHP test entry.
- `send()` writes the shared elimination registry `EchoEliminator` reads, so
  your replies never re-enter handlers; returns the count of
  `random_id`/`msg_id` keys written, and **throws** when no PSR-16
  `CacheInterface` is bound.
- `ingest()`/`ingestResponse()` return the hydrated curated model root
  (`TfMessage`/`TfUpdate`) or null when the constructor has no curated
  surface.
- Module hands: `route()`, `backup($setId): VaultInterface`,
  `store(): StoreService`, `storeBackup(): DatabaseBackupService`,
  `app(): AppRuntime`, `passport(): PassportLoginService`,
  `daemon(): Daemon`, `schemaLayer(?dirs): array` (schema regeneration —
  never hand-edit the generated artifacts).
- `__call` forwards any unknown method to the first owner that has it
  (owners: `Teleclient`, `UpdateIngestor`, `EntityAggregator`, `RouteTable`,
  `HandlerRegistry`, `UpdateDispatcher`, `Daemon`).
- **Two facades, don't mix them up** — this class vs the Laravel facade
  `MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe` (the client-acquisition
  facade over `TeleframeClient`; see [notify.md](notify.md) §1).

## 2. `HandlerRegistry` — the shared handler table

```php
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;

$registry = HandlerRegistry::instance();        // singleton per process
HandlerRegistry::reset();                          // tests / teardown

$registry->on('updateNewMessage', MyHandler::class, priority: 5, onOwn: false, tag: 'a');
$registry->onMessage(Fallthrough::class, priority: 1);   // catch-all `*`
$registry->all();    // raw rows
$registry->compile();// ordered rows (priority desc, then registration order)
$registry->count();
```

**Dispatch order: first-match-wins; within matches, priority desc, then
registration order.** The `tag` is free-form (routing/debug). `onOwn` opts the
handler into self-originated frames (the echo path is filtered out by
default).

## 3. `HandlerMatcher` — zero-regex matching

```php
use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;

$matcher = new HandlerMatcher($registry);
$hit = $matcher->match('updateNewMessage');      // ?HandlerMatch
$hit?->arg();                                    // ?string (the %s token)
```

Grammar (str_starts_with / str_ends_with / single sscanf — zero regex):

- exact constructor: `updateNewMessage`
- trailing `*` prefix: `updateNew*`
- bare `*` catch-all (what `onMessage` registers)
- one `%s` token captures the rest: `/start %s` matches `/start 42` with
  `arg() === '42'`. **Two `%s` tokens throw `MultipleSscanfTokensException`**
  at registration.

## 4. `Update` — the immutable uprate

```php
use MeRezaRezaei\Teleframe\Handler\Update;

$u = new Update(
    array: $rawUpdate, accountId: 7,
    source: 'event', ts: null,
    selfOriginated: false, model: null,
);
$u->constructor();          // string — $array['_'] ?? '' ('' on the mirror/event path)
$u->updateId();             // string — sha1 of the frame
$u->withSelfOriginated(true);
$u->withModel($model);

$bus = Update::fromBus($raw, 7, $ts);            // bus intake
$mirror = Update::fromMirror($model, 7, $ts);    // mirror/model intake
```

## 5. `UpdateDispatcher` — DI-flavored invoke

```php
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;

$result = $dispatcher->dispatch($update);        // mixed
$dispatcher->seenReplays();                      // int — replay-deduped frames so far
```

Parameter resolution: `Update` / `ValidatedUpdate` / `TelegramContext` type-hints
receive the frame (fresh per dispatch); a `string $arg` parameter receives the
matcher token; any other class parameter is resolved from the container
(lazy). A handler failure is logged (PSR-3, silent under NullLogger) and
routed to the dead-letter via `DEAD_LETTER_REASON = 'handler-failed'` — the
dispatcher never swallows silently for callers who opt out of the DL.

`ValidatedUpdate` carries `update()`, `rules()`, `valid(): bool`, `errors()`;
`TelegramContext` carries `accountId()` and `inStageFlow()`.

## 6. `Pipeline` — the 26-line onion

```php
use MeRezaRezaei\Teleframe\Handler\Pipeline;

$pipeline = new Pipeline;
$run = $pipeline->then(
    middleware: [$echoEliminator, $replayDedup, $yourMiddleware],
    terminal: fn (Update $u) => $dispatcher->invoke($u),
);
$out = $run($update);
```

Middleware signature: `fn (Update $update, callable $next): mixed` — any layer
may short-circuit by returning without calling `$next`.

## 7. Shipped middleware

```php
use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Handler\Middleware\ReplayDedup;

$echo = new EchoEliminator($cache, $matcher);    // KEY 'teleframe.handler.sends'
$echo->remember(7, ['random_id' => 123, 'text' => 'ok']); // send-time registry

$dedup = new ReplayDedup($cache, $matcher);      // KEY 'teleframe.handler.seen'
```

- **EchoEliminator**: replies written by `Teleframe::send()` (or your own
  `remember()` calls) are filtered out before they re-enter handlers —
  explicit replies are what keep the pipeline loop-free.
- **ReplayDedup**: the same frame delivered twice (bus redelivery) runs once;
  `seenReplays()` counts the deduped frames.

## 8. Coordinate with the docs

- Full subscription walkthrough (webhook route macro, bus intake, hydration,
  stage-state, `FakeDispatcher`): [handlers.md](../handlers.md).
- Where the updates come from: [ingest.md](../ingest.md) · [bus.md](../bus.md).
- The compose facade's store/backup/passport hands: [store-backup.md](store-backup.md) ·
  [passport.md](passport.md).

---

Related: [notify.md](notify.md) · [store-backup.md](store-backup.md) ·
[mtproto.md](mtproto.md) · [passport.md](passport.md).