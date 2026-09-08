# Message Templates (Phase 5d) — Design

**Parent:** `docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5d.
**Status:** Spec — owner gate = spec review only. RULINGS Q13 + Q14 applied
verbatim from `2026-09-07-framework-layers-gap-analysis.md` §RULINGS by the
worker; nothing here re-opens decided rulings.

## 1. RULINGS applied

- **Q13 — template syntax:** (a) plain PHP templates returning
  `{text, entities, reply_markup?}`. ZERO new parser = zero-regex-safe. The
  template IS PHP code; entity construction reuses the engine's existing
  entity machinery (`src/Core/Entities/EntityParser`) through a thin
  conventional-input adapter — never a NEW markup parser. This is
  ⚠ dev-facing (veto-able at this review) and matches Laravel's PhpEngine
  shape (`$data` extracted into template scope).
- **Q14 — cache artifact:** (a) cache the compiled **entity plan** (the
  data, not the template code) in a `bootstrap/cache`-style directory file
  cache, PSR-16, persistent across processes; **closure fallback** for
  media/albums, which a text plan cannot describe — validated, never cached.
- **Hard constraint 4 — salt invalidation:** every compiled-plan key is
  salted with Phase 1's `SchemaLayer::cacheSalt()`; a layer bump orphanes
  the old artifact and recompiles with NO mtime dependence. Proven in tests
  by bumping a manifest layer while leaving every file mtime untouched.

## 2. New surface (all under `MeRezaRezaei\Teleframe\Message\`)

### `EntityParser` — conventional-input adapter (zero-regex)

- `spans(string $text, list<array{offset:int, length:int, type:string}>
  $spans): array{text, entities}` — Bot API `type` aliases (`bold`,
  `text_link`, `mention`, `custom_emoji`, …) map to engine `messageEntity*`
  constructor names via a const map; engine-native names pass through.
  Bounds (UTF-16 code units, reused `CoreEntityParser::getUtf16Length`),
  per-type extras (`url`, `document_id`, `user_id`, `language`), unknown
  keys and overlap are all validated up front (`InvalidArgumentException`);
  entities are sorted by offset.
- `html()` / `markdown()` delegate to the EXISTING `Core\Entities
  \EntityParser` DOM / tokenizer parsers — reused, not duplicated.

### `FilesystemCache` — PSR-16 directory store

- `CacheInterface` implementation backed by `Illuminate\Filesystem\
  Filesystem`; one `.cache` file per key under a named directory; TTL
  honoured lazily; values `serialize()`d (never objects —
  `unserialize(..., allowed_classes: false)`); `strcspn` key validation
  (PSR-16 reserved `{}()/\@:`), zero-regex. `files()` exposes absolute paths
  (cache-inventory / test proof).

### `MessageCompiler` — compile + salt-keyed cache

- `compile(string $name, array $data = []): array` — cache-hit: read plan +
  re-typecheck; miss: include the template (data extracted) → validate →
  cache → return. Key = `sha1(salt . '|' . name)`.
- `compileFrom(string $name, callable $factory, array $data = []): array`
  — Q14 closure fallback (media/albums): validated, NOT cached.
- `forget(string $name): bool` (view:clear-style single op) / `flush():
  bool` (whole store). `cacheSalt()`, `templatesDir()`, `cacheDir()`,
  `cacheStore()` exposed for hosts + tests.
- Default dirs: `resources/views/telegram` (templates), `bootstrap/cache/
  telegram-templates` (cache) resolved from the package root; both
  constructor-injectable. `schemaManifestPath` seam lets tests/single-apps
  point `SchemaLayer` at their own manifest.

### `MessageFactory` — the `message('name')` finder

- `message(name, data, ?compiler)` static + `resolve(name, data)` /
  `__invoke(name, data)` instance; `media(name, factory, data)` = closure
  fallback shim. Returns the compiled plan directly, ready for the handler
  to send.

### `MessagePlanValidator` — send-time typecheck vs MethodRegistry

- `assertPlanValid(array $plan, string $method = 'messages.sendMessage'):
  array` — reads `MethodRegistry::get($method)->params`, maps plan keys
  `text→message`, `entities→entities`, `reply_markup→reply_markup`,
  rejects UNKNOWN keys (plan / entity / reply_markup levels) with the domain
  exception `InvalidMessagePlanException`, requires `messageEntity*` entity
  constructors with non-negative offset / positive length, `reply_markup`
  array with known `ReplyMarkup` variant keys. Returns the canonical plan
  (`entities` always present). Typecheck ONLY — no network.

### Exceptions

- `InvalidMessagePlanException extends DomainException` (typecheck failures).
- `MessageTemplateException extends RuntimeException` (missing template /
  non-array return), carrying `$name`.

## 3. Compile + cache contract

```
message('welcome', ['name' => 'Ada'])
  └─ MessageFactory::message/resolve ── MessageCompiler::compile
       ├─ key = sha1(SchemaLayer::cacheSalt() . '|' . name)
       ├─ FilesystemCache->get(key)
       │    ├─ hit  → MessagePlanValidator::assertPlanValid (tamper gate) → plan
       │    └─ miss → include {templatesDir}/welcome.php (data extracted)
       │             → assertPlanValid → normalized plan
       │             → FilesystemCache->set(key, plan)
       └─ return {text, entities, reply_markup?}
```

Invalidation: layer bump ⇒ salt string changes ⇒ key changes ⇒ old artifact
orphaned, template recompiles. mtime is NEVER part of the decision (hard
constraint 4). `forget()` removes one current-salt key; `flush()` clears the
store.

## 4. Template sample (tests also ship these)

`tests/Message/fixtures/templates/`:
- `greeting.php` — `message('greeting', ['name' => …])` → interpolated text.
- `markup.php` — `EntityParser::spans()` + `reply_markup` inline keyboard.
- `stamp.php` — writes `executed.txt` on execution: the cache-hit / salt-
  invalidation proof probe.
- `invalid_key.php` — unknown plan key → compile-time `InvalidMessagePlan
  Exception`.

## 5. Gate evidence (2026-09-08)

- `vendor/bin/phpunit tests/Message --no-coverage` → `OK (48 tests, 95
  assertions)` — parse/entities, compile + cache hit/miss, **salt-layer
  invalidation with untouched mtimes**, forget/flush, `message(name,data)`,
  typecheck pass + reject.
- `vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress` →
  `[OK] No errors` (zero-regex enforced; no `preg_*` in
  `src/Teleframe/Message/`).
- `composer verify` → full suite green (report attached).

**Commits:** none (coordinator commits; Phase 5d files in the tree remain
owned by the 5d worker). Roadmap Phase 5d tick is the coordinator's step
(`docs/superpowers/plans/2026-09-07-master-roadmap.md` is on the do-not-touch
list for this worker).