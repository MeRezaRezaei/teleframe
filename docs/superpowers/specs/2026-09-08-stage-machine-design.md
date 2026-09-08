# Phase 5e — Stage Machine design

> **Phase 5e.** A same-route stage machine: one constructor name, several
> sequential captures — a "wizard" or "form" that unfolds as a dialogue on the
> same Telegram route. Plain-array state survives supervisor restarts
> (Q17), the in-process `Request::create` submit needs no socket (Q20), and
> the stage middleware sits in the dispatch onion between echo/dedup and the
> handler (F5).
>
> All code lives under `MeRezaRezaei\Teleframe\Stage\`, is zero-regex, and is
> composed — not owned — by `MeRezaRezaei\Teleframe\Teleframe`.

---

## 1. The idea

The bus handler table is **receive-ordered**: first match on constructor
wins. A wizard must not fight that — it *is* one route. The stage machine
keeps a per-account flow on a single constructor (`updateNewMessage` here)
and re-paces it against real Telegram messages:

```php
$registry = new StageRegistry();

$signup = StageSet::define('updateNewMessage')
    ->submit('/forms/signup', SignupFormRequest::class)
    ->stage('email')->field('email')->rule('email')->prompt('ask_email')
    ->final('confirm')->field('tos')->rule('accepted')->prompt('ask_tos')
    ->compile();

$registry->on($signup)->compile();
```

Every message that matches `updateNewMessage` for an account **with an active
flow** is consumed by the stage middleware *before* the general handler sees
it (F5). When no flow is active, the same middleware passes through and the
message reaches your handler — one route, two lifetimes.

## 2. Livecycle

A flow is **started**, **advanced**, and **finished** against the PSR-16
state store.

```php
use MeRezaRezaei\Teleframe\Stage\StageState;

$state = new StageState($cache, $registry);
$state->start($signup, '100');                       // blank plain-array state
$state->advance('100', ['email' => 'ada@example.com'], $signup);
$state->current('100');                              // ['stageSet' => ..., 'currentStage' => ..., 'data' => [...], 'msgIds' => [...]]
$state->finish('100');                               // drop the flow
```

- `start()` — persists `{stageSet, currentStage: null, data: [], msgIds: []}`.
- `advance()` — merges validated values into `data`, walks to the next not-yet
  complete stage, and stores the new state **in one write** (Q17). When the
  registry cannot resolve the set it stores the data and leaves the stage
  pointer untouched.
- `current()` / `has()` — read the flow; `null` when none.
- `touch()` — records sent message ids into `msgIds`, deduplicated. The host
  calls this on every stage prompt it emits, which is how the middleware
  knows the exact prompts the account already saw (restart-proof).

Everything is plain PHP arrays — no serialized objects — so a supervisor
restart resumes the flow verbatim from the store.

## 3. Stage sets (`StageSet`)

A set is an immutable value object after `compile()` (building methods throw
afterwards). The name IS the entry constructor route.

### Declarative shape

```php
StageSet::define('updateNewMessage', [
    'email'   => ['steps' => ['email']],
    'profile' => ['steps' => ['name', 'bio']],
    'confirm' => ['steps' => ['tos'], 'final_step' => true],
])
```

### Fluent shape

```php
StageSet::define('updateNewMessage')
    ->submit('/forms/signup', SignupFormRequest::class, 'POST') // Q20 wiring
    ->submitError('stages.invalid_submit')                     // default
    ->stage('email')
        ->field('email')->rule('email')                        // default: required|string
        ->expects('updateNewMessage')                          // default: set name
        ->prompt('ask_email')                                  // stage prompt template
    ->stage('confirm')
        ->field('tos')->rule('accepted')
        ->prompt('ask_tos')
    ->final('confirm')                          // marks the final stage
    ->compile();
```

- `field($field)` — begin accepting one field on the current stage.
- `expected()`s default to the set name; set it to any HandlerMatcher grammar
  (exact / `prefix*` / `*` / single `%s`).
- `final($name)` — flags the stage that completes the flow (only the last one;
  a second explicit final throws).
- `submit($uri, $formRequest, $method = 'POST')` — the web route the finished
  flow posts to, and the `StageFormRequest` class that gates it. `submit()`
  may only be called before the final mark.

### Introspection used by the middleware

`expectsFor()`, `rulesFor()`, `promptFor()`, `errorFor()`, `remainingFields()`,
`pendingField()`, `finalStep()`/`isFinal()`, `stageNames()`/`stageAfter()`,
`submitUri()`, `submitMethod()`, `submitFormRequest()`, `submitErrorTemplate()`,
`hasSubmit()`.

`pendingField(?string $currentStage, array $data)` is the walker: it resumes
from the current stage, skips already-captured fields, and returns
`{stage, field, complete}` — `complete: true` when the final stage is fully
captured and the flow is ready to submit.

## 4. Registry (`StageRegistry`)

`on(StageSet $set, ?callable $submit = null)` registers a set; `compile()`
freezes it. `match($constructor)` resolves the first set (exact name first,
then pattern, first-match-wins — same grammar as `HandlerMatcher`). The
optional `$submit` closure returns the finished `$data` to the host action
instead of the FormRequest/Request path (the two submit modes, §6).

## 5. Validation (`StageValidator`, `StageFormRequest`)

- `StageValidator` — the fallback, dependency-free rules engine. `validate()`
  throws `StageValidationException` (`errors()` carries the violations);
  `data()` trims; `errors()` returns them. Rules: `required`, `email`,
  `accepted`, `array`, `bool`|`boolean`, `int`|`integer`, `string`, `in:a,b`.
- `StageFormRequest extends Illuminate\Foundation\Http\FormRequest` — abstract
  `rules()` (the parent declares no concrete rules, so this is additive).
  Static, cache-free accessors: `rulesFor($class)` (guards the subclass
  requirement), `validateStageData($class, $data)` (throws
  `StageValidationException`), `stageErrors($class, $data)`. Stage rules are
  checked per capture; the FormRequest's **own** rules are the submit-time
  gate.

## 6. Middleware (`StageMiddleware`) — where the flow lives

```php
$stage = new StageMiddleware(
    registry: $registry,          // StageRegistry
    state: $state,                // StageState
    messages: $messageFactory,    // MessageFactory (resolve('ask_email'))
    container: $container,        // PSR-11, get/has + optional set/delete
    reply: $sendPlan,             // callable(array{text, entities, reply_markup?}) — outbox fallback
    resolver: $resolveRequest,    // ?callable(Request) → host controller bridge (Q20)
);
```

Register it in the onion **after** echo elimination and replay dedup and
**before** the recorder/general handler (F5). It:

1. Reads the account's state (none → pass through).
2. Resolves the set; unknown stageSet → pass through, defensively.
3. Skips the frame if the constructor does not match the pending field's
   `expects()`.
4. **Captures** the value (reads the text, or the callback arg) and advances
   the state. Consumed frames stop the onion — the general handler never sees
   them.
5. Prompts the next pending stage (or errors, through the stage's `error()`
   template, shaped by MessageCompiler like every other message) using
   `reply()`; with no reply seam, pushes onto the middleware `outbox()`.
6. On completion, **submits**.

### Submit modes (Q20)

1. **Registry submit closure** — registered via `on($set, $submit)`; receives
   `$data`, host acts, state finished.
2. **FormRequest + in-process Request** — `stageErrors()` gates the data
   against the FormRequest's own rules (failures send `submitErrorTemplate()`
   and keep the state for a retry); otherwise
   `Request::create($uri, $method, $data)` is resolved through `$resolver` to
   the host's controller route. No socket, no HTTP, loop-safe.
3. **Neither** — `submitErrorTemplate()` sent, state finished (the flow is
   complete but unwired).

### The in-flow flag

Throughout the exchange the container carries
`StageMiddleware::FLAG` = `teleframe.stage.in_telegram_flow` so the host's
`send()` loop (echo elimination) and controllers know to treat the reply as
part of the flow, and so a controller bound to the same account cannot
re-trigger the flow it caused. The flag and the flagged `TelegramContext`
(`inStageFlow()`) are set on entry and restored to their prior values on the
leave path — nested dispatches stack safely.

## 7. Wiring one-liner

There is no facade: the host composes the onion.

```php
$dispatcher = new UpdateDispatcher(
    $handlers,                                   // HandlerRegistry
    new Pipeline([$echo, $dedup, $stage, $recorder]),
    $container, $sends,
);
```

When a flow is **active**, echo/dedup run first (an echo inside a stage
dialogue must not wake a handler), then the stage middleware consumes
matching frames, then — only for non-flow traffic — your handler.

## 8. Proof surface (tests/Stage)

- `StageSetTest` — walker, defaults, explicit rules/finals, duplicate and
  mis-declaration rejection, submit wiring.
- `StageRegistryTest` — precedence (exact over pattern), submit closures,
  defensive unknown-stage handling.
- `StageStateTest` — plain state, per-account keys, PSR-16 survival across a
  second instance **and** a real separate PHP process (`exec` supervisor
  restart proof), touch dedup, finish.
- `StageValidatorTest` — rules, trimming, typed violations.
- `StageFormRequestTest` — subclass gate, passing + failing data through the
  real Illuminate validator, agreement with the fallback engine.
- `StageMiddlewareTest` — pass-through when idle, capture/advance/prompt,
  template-shaped errors, all three submit modes, in-process
  `Request::create` dispatch with the container flag raised, defensive
  unknown-stage handler routing.
- `PrecedenceTest` — F5: echo first, then active stage, then keyboard route,
  the general handler only for truly general traffic.