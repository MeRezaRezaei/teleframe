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

$registry = new HandlerRegistry();
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