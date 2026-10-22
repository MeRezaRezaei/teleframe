# Usage — APP FRAMEWORK: the declarative apps and the handler substrate

> **Release area 4 of 5.** Two halves of one capability. **§0** is the
> declarative app framework — a bot or user app declared as data, answering
> updates and running scheduled Telegram calls, behind the `TelegramApp`
> facade. **§1–§8** are the handler substrate it registers onto —
> `HandlerRegistry` / `HandlerMatcher` / `Pipeline` / `UpdateDispatcher`.
> The in-depth operational guide (webhooks, bus intake, hydration, stages)
> stays in [handlers.md](../handlers.md); this page is the verified API
> reference.

---

## 0. THE DECLARATIVE APP FRAMEWORK — `TelegramApp`

One facade declares a bot or user app: triggers that answer updates, scheduled
calls that run on a cadence, and the read/answer pair for "connect my AI to my
Telegram messages". It composes the substrate below — it does not replace it.

```php
use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Laravel\Facades\TelegramApp;

// An app that answers every new message, plus a scheduled ping.
TelegramApp::define('assistant', AppMode::User, [
    AppTrigger::on('updateNewMessage', function (Update $u): void {
        $peer = $u->array['message']['peer_id'] ?? null;
        if ($peer === null) {
            return;
        }

        // read the conversation (read() takes the PEER first)…
        $recent = TelegramApp::read($peer, 20);
        // …and answer it through the same account.
        TelegramApp::answer($u->accountId, $peer, 'you said: '.count($recent));
    }),
], [
    AppCall::every('status-ping', 3600, 'messages.sendMessage', ['peer' => '@ops', 'message' => 'alive']),
]);

TelegramApp::list();                              // registry rows (a view)
TelegramApp::calls();                             // installSpec preview
TelegramApp::installCalls();                      // arm them on the schedule seam
TelegramApp::runCall('assistant', 'status-ping'); // one call now, offline probe
```

The runnable version of the owner's sentence —
*"connect my ai to read my telegram messages and answer them"* — is
[`examples/ai-read-and-reply.php`](../../examples/ai-read-and-reply.php);
`php examples/ai-read-and-reply.php --offline` runs it with no credentials, no
network and no session.

### 0.1 Declaring an app from config

A published `config/teleframe/app.php` maps each `teleframe.app.apps` row 1:1
onto `AppDefinition::define()`, so an app can ship with the host instead of in
code. Same data rows, same refusals.

```php
'teleframe.app.apps' => [
    [
        'name' => 'echo-bot',
        'mode' => 'bot',                    // bot|user
        'triggers' => [
            ['match' => '/start', 'handler' => \App\Telegram\EchoBotHandler::class, 'priority' => 0, 'on_own' => false],
        ],
        'calls' => [
            ['name' => 'status-ping', 'interval_seconds' => 3600, 'method' => 'messages.sendMessage', 'params' => ['peer' => '@ops', 'message' => 'alive']],
        ],
    ],
],
```

Seams a host may rebind: `teleframe.app.transport` (a
`callable(AppMode): mixed` — vault/DC rules per app farm, fakes for tests),
`teleframe.app.schedules` (`null` = the shipped no-op driver, `temporal` = the
SDK-backed one), `teleframe.app.schedules_dsn`,
`teleframe.app.schedules_task_queue`.

### 0.2 Triggers, and the one refusal you will meet

A trigger row is an `AppTrigger` (a plain array is **refused** at declare time,
naming the index) and its `$match` is the shipped matcher grammar of §3: exact
`Foo`, prefix `Foo*`, catch-all `*`, or one `%s` token. `$handler` is a
closure, a PSR-11 invokable string, or `[Class::class, 'method']`.

**Registration installs each trigger on the shared hot table at boot, in EVERY
process that consumes updates** — the daemon included. You do not call
`install()`; declaring the app is the whole wiring step.

Dispatch is first-match-wins, so an **unreachable trigger is refused, loudly
and by name**, rather than installed and never run:

| Situation | What happens |
| :--- | :--- |
| Another owner's row precedes this trigger and matches **everything** it would match — a host `onMessage()` catch-all `'*'`, a `prefix*`, or any higher-priority superset row | **Refused.** `InvalidArgumentException` naming the app, the trigger, the blocking match and its owner. Nothing is registered (no half-installed app). |
| A **different priority** | Allowed — priority is the documented disambiguation lever, and the higher-priority row wins. Note the consequence: an app installed at priority 0 stops being reached once a higher-priority superset lands. That is the host's stated choice, so it is allowed, not refused. |
| **Disjoint** patterns | Allowed — two independent rows. |
| **Within one app** (`'*'` plus an exact match in the same app) | Allowed by decision: one app's own two rows are one program. The narrower one stays dead; give them different priorities to run both. |

At boot a refused app is **reported, not fatal**: the refusal is recorded,
`teleframe:app list` prints it in the `status` column and exits non-zero, and
the apps that *can* run still run. One bad row in a config file cannot take
down a process.

### 0.3 Scheduled calls and the worker requirement

`calls()` previews the `installSpec`; `installCalls()` arms it on the schedule
seam. With the shipped default (`teleframe.app.schedules` = `null`) the seam
reports `skipped (null schedule driver)` and arms nothing — `teleframe:app
calls --install` exits non-zero rather than reporting a successful probe.

**With `temporal`, a worker process must actually be running.** Registering a
schedule does not start a worker: a schedule that is armed with no worker
polling its task queue executes **nothing, forever, with no error**, while
`teleframe:tstore schedules` still reads healthy. The worker ships in the dist
tarball as `vendor/merezarezaei/teleframe/bin/teleframe-worker` (see
[integration.md](integration.md) §6.6) and serves the app-call family for the
`--app=…`/`--call=…` pairs it was started with. `teleframe:tstore schedules`
prints, for that family, *"served ONLY IF you started the worker with
--app=… --call=…; this process cannot verify a worker is running"* — because
the status line dials nothing and cannot know.

### 0.4 The CLI

`php artisan teleframe:app register|list|calls|run`
(`--install` probe-installs the schedules, `--app=`/`--call=` pick the call,
`--raw` prints a whole result). `teleframe:app list` is the fastest way to see
whether an app is actually on the pipeline.

### 0.5 Surface reference

`define` · `register` · `apps`/`list` · `resolve` · `calls` ·
`installCalls` · `runCall` · `read` · `answer` · `transport` · `registry` ·
`responder` · `schedules` — all real methods on `AppRuntime`, each listed with
its signature in [integration.md](integration.md) §4.5. `read()` refuses loud
on the bot transport (the Bot API has no history read); `answer()` records the
send in the echo-elimination registry so a reply cannot re-enter the pipeline
as a loop.

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
- The `TelegramApp` facade, its full signature table and the
  `teleframe.app.*` config rows: [integration.md](integration.md) §4.5.
- Where the updates come from: [ingest.md](ingest.md) · [bus.md](bus.md).
- The compose facade's store/backup/passport hands: [store-backup.md](store-backup.md) ·
  [passport.md](passport.md).

---

Related: [notify.md](notify.md) · [store-backup.md](store-backup.md) ·
[mtproto.md](mtproto.md) · [passport.md](passport.md).