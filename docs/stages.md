# Stages — same route, telegram-paced

> **Phase 5e.** A stage machine: one constructor name, several sequential
> captures — a "wizard" or "form" that unfolds as a dialogue on ONE Telegram
> route. State is plain PSR-16 arrays that survive supervisor restarts (Q17),
> finished flows submit in-process through `Request::create` (Q20), and the
> stage middleware sits between echo/dedup and your handler (F5).
>
> All code lives under `MeRezaRezaei\Teleframe\Stage\` and is zero-regex.
> The host composes the middleware into the onion; `Teleframe` does not own
> it.

---

## 1. Define the flow

A `StageSet` is a route plus its sequential captures. Build it declaratively
or fluently — the two shapes are interchangeable:

```php
use MeRezaRezaei\Teleframe\Stage\StageRegistry;
use MeRezaRezaei\Teleframe\Stage\StageSet;

$signup = StageSet::define('updateNewMessage')
    ->submit('/forms/signup', SignupFormRequest::class)   // reserved for the finish
    ->stage('email')
        ->field('email')->rule('email')                   // default: required|string
        ->prompt('ask_email')                             // stage prompt template
    ->final('confirm')
        ->field('tos')->rule('accepted')
        ->prompt('ask_tos')
    ->compile();                                          // immutable afterwards
```

- The set **name is the route**: `updateNewMessage` here, but any
  HandlerMatcher shape works (`prefix*`, single `%s`) — set `expects()`
  per field to pin it.
- `final($name)` marks the stage that completes the flow (only one).
- `submit($uri, $formRequest, $method = 'POST')` names the web route the
  completed flow posts to and the `StageFormRequest` that gates it.

## 2. Register

```php
$registry = (new StageRegistry())
    ->on($signup, $submitClosure /* optional: skip the Request leg */)
    ->compile();
```

## 3. Put the flow in the onion (F5)

```php
$stage = new StageMiddleware(
    registry: $registry,
    state: new StageState($cache, $registry),
    messages: $messageFactory,
    container: $container,          // get/has (+ set/delete for the flag)
    reply: $sendPlan,               // callable → host send(); else middleware outbox()
    resolver: $resolveRequest,      // ?callable(Request) → host controller bridge
);

$dispatcher = new UpdateDispatcher(
    $handlers,
    new Pipeline([$echo, $dedup, $stage, $recorder]),
    $container, $sends,
);
```

Middleware order decides precedence: echo and replay-dedup still run before
the stage (an echo inside a dialogue must not wake a handler), then the stage
consumes matching frames, and only genuinely non-flow traffic reaches your
handler.

## 4. Start, answer, finish

No flow → the route behaves normally. The moment a flow exists, the middleware
owns the frames that match its expectations:

```php
$state->start($signup, '100');          // begin (e.g. from your handler)
```

For every captured answer the middleware advances the state and sends the
next stage's prompt (`ask_tos` after `ask_email` above). `Reply …` text comes
from your MessageCompiler templates just like every other message. When the
final stage is complete, the flow submits:

```php
$submit        // closure → receives $data, host acts
// OR $resolver ?→ Request::create($uri, $method, $data) hits the web route
// OR neither   → submitErrorTemplate() sent, flow finished anyway
```

Invalid stage values send the stage `error()` template and ask again; invalid
**form** data at submit time sends `submitErrorTemplate()` and keeps the
state — the account can fix it and resubmit.

## 5. Restart survival (Q17)

State is plain arrays in your PSR-16 store, keyed `teleframe.stage.state.{accountId}`
(dot prefix — `FilesystemCache` rejects PSR-16 reserved characters):
`{stageSet, currentStage, data[], msgIds[]}`. A supervisor restart, a new
process, a new node over the same store — the dialogue resumes exactly where
it stopped. `msgIds[]` records the prompts already sent, so the host knows
the precise messages the account has seen.

## 6. The in-flow flag (Q18)

While the middleware handles an exchange, the container carries
`MeRezaRezaei\Teleframe\Stage\StageMiddleware::FLAG`
(`teleframe.stage.in_telegram_flow`) and the `TelegramContext` passed through
the onion reports `inStageFlow()`. The host's send loop and any controller
bound to the same account can tell "dialogue traffic" from ordinary replies,
and can never re-trigger the flow that is already advancing.

## 7. Rules

- **Per-stage** — the fallback dependency-free engine:
  `required`, `email`, `accepted`, `array`, `bool`, `int`, `string`, `in:a,b`.
- **At submit** — your `StageFormRequest::rules()`, run through the real
  Laravel validator. Stage rules guard each capture; the FormRequest's own
  rules are the final gate. One `StageFormRequest` per wizard.

## 8. Proof

`tests/Stage/` walks every branch: pass-through when idle, capture/advance/
prompt, template-shaped errors, all three submit modes, the in-process
`Request::create` leg with the container flag raised, unknown-stage defense,
and the F5 precedence suite (echo → stage → keyboard → general). The Q17
restart proofs run a **second instance and a real separate PHP process**
over the same store.