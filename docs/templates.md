# Message templates — Blade for Telegram

> **Phase 5d.** Plain-PHP message templates that compile to the entity plan
> `{text, entities, reply_markup?}` — no new markup parser (Q13), the plan
> (not the template code) cached to disk and salted by the schema layer
> (Q14 + Phase 1 hard constraint 4), and a light send-time typecheck against
> what `MethodRegistry` accepts for `messages.sendMessage`.
>
> All code lives under `MeRezaRezaei\Teleframe\Message\` and is strictly
> zero-regex (`mb_*`/`str_*`/`substr`/`explode` only — enforced by phpstan
> level 5's `preg_*` ban).

---

## 1. Write a template

A template is a plain PHP file that `return`s an array. It lives under a
`resources/views/telegram/`-style directory (the compiler's constructor
default resolves `<package-root>/resources/views/telegram`; plain-PHP hosts
point the compiler at their own path).

```php
<?php // resources/views/telegram/welcome.php

$name = $name ?? 'friend';

return ['text' => "Hello, {$name}!"];
```

`message('welcome', ['name' => 'Ada'])` extracts the data array into the
template's scope (Laravel PhpEngine-style), so `$name` resolves inside the
template. The result is validated against the registry and normalized to the
canonical plan — `entities` is always present, defaulting to `[]`.

### Entities and markup (Q13)

Entities come from `EntityParser`, a zero-regex adapter over the engine's
existing entity machinery (`src/Core/Entities/EntityParser`): declared
`{offset, length, type}` spans become engine `messageEntity*` arrays.

```php
<?php // resources/views/telegram/buy.php

use MeRezaRezaei\Teleframe\Message\EntityParser;

$plan = EntityParser::spans('Buy now', [
    ['type' => 'bold', 'offset' => 0, 'length' => 3],
    ['type' => 'text_link', 'offset' => 4, 'length' => 3, 'url' => 'https://example.com/buy'],
]);

$plan['reply_markup'] = ['inline_keyboard' => [[['text' => 'Go', 'callback_data' => 'go']]]];

return $plan;
```

- `type` accepts Bot API aliases (`bold`, `italic`, `text_link`,
  `custom_emoji`, `mention`, …) or engine-native names (`messageEntityBold`,
  …); a map resolves them, no regex.
- `offset`/`length` are **UTF-16 code units** (Telegram's unit): bounds and
  overlap are validated against the text up front, so a template can never
  emit a plan Telegram rejects.
- Existing HTML / MarkdownV2 bodies are one hop away: `EntityParser::html()`
  and `EntityParser::markdown()` delegate to the engine's DOM / tokenizer
  parsers — reused, not duplicated.
- Markups reuse the 5c `Menu` render output under `reply_markup`.

## 2. Resolve a template

`MessageFactory` is the finder (Facade-adjacent). Static form for scripts
and tests; the instance form is the container-friendly entry.

```php
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Message\MessageFactory;

$compiler = new MessageCompiler();               // default dirs + salt
$plan     = MessageFactory::message('welcome', ['name' => 'Ada'], $compiler);
// ['text' => 'Hello, Ada!', 'entities' => []]

$factory = new MessageFactory($compiler);
$plan    = $factory('buy');                      // __invoke / resolve()
```

A Laravel host binds one `MessageFactory` (with a `MessageCompiler` pinned to
its own template + cache directories) into the container; plain PHP shares a
compiler. The handler then forwards `$plan['text']` (and the optional
`$plan['entities']` / `$plan['reply_markup']`) to `sendMessage`.

### Closure fallback for media/albums (Q14)

A text plan cannot describe media or albums. For those sends, run a closure
through the same typecheck — validated, never cached:

```php
$plan = $factory->media('photo', static fn (array $data): array => [
    'text' => "Photo of {$data['place']}",
    'entities' => EntityParser::spans('Photo of Paris', [['type' => 'italic', 'offset' => 9, 'length' => 5]])['entities'],
], ['place' => 'Paris']);
```

## 3. The compile+cache pipeline

`MessageCompiler::compile($name, $data)`:

1. computes the cache key as `sha1(cacheSalt() . '|' . $name)` — the
   **salt** is `SchemaLayer::cacheSalt()` (`schema-layer-<layer>`);
2. on a cache hit, reads the serialized plan back and re-runs the plan
   typecheck (a tampered cache file fails loudly);
3. on a miss, executes the template file, validates + normalizes the plan,
   and stores it in a `bootstrap/cache/telegram-templates`-style directory
   (a real `FilesystemCache` — PSR-16, persistent across processes).

### Salt invalidation beats mtime (Phase 1 constraint 4)

A schema-layer bump changes the salt string, which changes the key: the old
cache entry becomes an unreachable orphan and the template recompiles — even
when **no file mtime changed**. This is the exact mtime-independent
invalidation the gap analysis ruled in.

```php
// v1: layer 200 → salt "schema-layer-200"
compile('welcome');                 // cache file A (key K1)
// ... schema layer bumps to 201 → salt "schema-layer-201"
compile('welcome');                 // cache file B (key K2) — recompiled,
                                    // file A untouched, mtimes unchanged
```

Overseen by two `view:clear`-style ops:

```php
$compiler->forget('welcome');       // drop one name (current salt)
$compiler->flush();                 // drop the whole store
```

## 4. Send-time typecheck (Q14, registry-backed)

`MessagePlanValidator::assertPlanValid($plan)` reads
`MethodRegistry::get('messages.sendMessage')->params` and checks
conformance — plan keys map `text`→`message`, `entities`→`entities`,
`reply_markup`→`reply_markup`. Unknown keys (plan-level, per-entity, or
inside `reply_markup`) throw the domain exception
`InvalidMessagePlanException`. It is a **typecheck only**: no network call is
ever made, so invalid plans fail at compile time, not send time.

```php
use MeRezaRezaei\Teleframe\Message\MessagePlanValidator;

$plan = MessagePlanValidator::assertPlanValid(['text' => 'Hi']);   // ok, entities → []
MessagePlanValidator::assertPlanValid(['text' => 'x', 'parse_mode' => 'HTML']); // throws
```

## 5. Commands

```bash
vendor/bin/phpunit tests/Message              # 48 tests, 95 assertions
vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress   # zero-regex clean
```