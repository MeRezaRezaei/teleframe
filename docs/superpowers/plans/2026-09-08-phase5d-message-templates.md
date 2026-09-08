# Phase 5d — Message Templates (execution plan)

**Parent:** `specs/2026-09-08-message-templates-design.md` (this layer's spec)
+ `docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5d.
**Created:** 2026-09-08. **Depends on:** Phase 1 schema layer salt
(`SchemaLayer::cacheSalt()` — hard constraint 4: mtime-only expiry is
insufficient).

## RULINGS enacted (gap doc §III)

- **Q13 (a):** plain PHP templates returning `{text, entities, reply_markup?}`
  — zero NEW parser, zero-regex-safe. Entity construction reuses the
  existing engine machinery (`src/Core/Entities/EntityParser`) through a thin
  conventional-input adapter (`Message\EntityParser::spans()`), so templates
  never write a markup dialect.
- **Q14 (a):** cache the **compiled entity plan** (data, not template code)
  in a PSR-16 filesystem store (`bootstrap/cache`-style dir), salted by
  `SchemaLayer::cacheSalt()`; closure fallback (`compileFrom()`) for
  media/albums — validated, never cached.

## Findings recap

- `src/Core/Entities/EntityParser` already exists and defines the engine
  entity shape (`'_' => 'messageEntity*'`, `offset`/`length` in UTF-16 code
  units) plus a `getUtf16Length()` primitive — REUSE it (bounds + shape
  stay single-source); do not duplicate a parser.
- `Illuminate\Filesystem\Filesystem::files()` returns `SplFileInfo[]`, not
  path strings — the cache inventory maps `getPathname()`.
- `SchemaLayer::cacheSalt(?string $manifestPath)` accepts a manifest seam —
  `MessageCompiler` adopts it, which makes the salt-invalidation test fully
  hermetic (rewrite a temp manifest, keep every mtime).
- Generated schema artifacts carry NO envelope-level `objects` map — the
  registry typecheck reads the method's `params` (incl. types like
  `Vector<MessageEntity>`, `ReplyMarkup`) instead of an object table.

## Tasks

- [x] **Task 1: `Message\EntityParser` (Q13).** Zero-regex adapter over the
      existing entity machinery: `spans(text, [{offset,length,type,…}])`
      with alias→`messageEntity*` map, engine-native passthrough, UTF-16
      bounds + overlap + per-type extra-key validation, offset sort; `html()`
      / `markdown()` delegate to `Core\Entities\EntityParser`. Tests: aliases,
      native passthrough, url/document_id extras, mention, ordering, bounds
      reject, unknown key/type/overlap/negative/zero-length rejects,
      html/markdown delegation.
- [x] **Task 2: `Message\FilesystemCache`.** PSR-16 (`CacheInterface`) store
      over `Illuminate\Filesystem\Filesystem`; TTL-lazy expiry,
      `serialize()`/`unserialize(allowed_classes:false)` envelopes,
      `strcspn` key validation, `files()` path inventory.
- [x] **Task 3: `Message\MessageCompiler` (Q14).** `compile(name, data)`
      cache-first with key `sha1(salt.'|'.name)`; template include runs with
      `$data` extracted; miss → validate → cache → return; hit → validate
      (tamper gate). `compileFrom(name, factory, data)` closure fallback
      (uncached); `forget(name)` / `flush()`; exposed `cacheSalt()`,
      `templatesDir()`, `cacheDir()`, `cacheStore()`; default dirs
      `resources/views/telegram` + `bootstrap/cache/telegram-templates`;
      `schemaManifestPath` seam. Tests: compile+data, default data, packaged
      markup plan, cache-hit without re-execution (stamp probe), **salt-bump
      recompile with untouched mtimes** (salt changes, file count grows,
      old artifact mtime constant, template re-executes), forget, flush,
      unknown template / non-array / unsafe-name / invalid-plan rejections,
      closure fallback not cached.
- [x] **Task 4: `Message\MessageFactory`.** `message(name, data, ?compiler)`
      static finder + `resolve()` / `__invoke()` instance + `media(name,
      factory, data)` closure shim. Tests: static/instance finder, markup
      plan, media fallback uncached, cross-call caching.
- [x] **Task 5: `Message\MessagePlanValidator` (Q14 typecheck).** Reads
      `MethodRegistry::get('messages.sendMessage')->params`; plan key map
      text/message, entities/entities, reply_markup/reply_markup; rejects
      unknown plan/entity/markup keys with `InvalidMessagePlanException`
      (DomainException); validates `messageEntity*` constructors, non-negative
      offset, positive length, per-type extras; returns normalized plan
      (entities always present). Never touches the network. Tests: pass +
      normalize, entities default, unknown key / empty text / non-string /
      non-array entities / missing `_` / negative offset / zero length /
      unknown entity key / non-array markup / unknown markup key rejects, url
      normalization.
- [x] **Task 6: exceptions.** `InvalidMessagePlanException` (DomainException)
      + `MessageTemplateException` (RuntimeException, `$name`), both under
      `Message\Exceptions\`.
- [x] **Task 7: docs.** `docs/templates.md` (write/compile/typecheck/invalidate
      guide), this spec + plan; roadmap Phase 5d tick left to the coordinator
      (roadmap is do-not-touch for this worker). `phpunit.xml.dist` gets a
      `Message` testsuite entry so `composer verify` covers the new suite.

## Gate evidence (2026-09-08)

- `composer dump-autoload` → `Generated autoload files`.
- `vendor/bin/phpunit tests/Message --no-coverage` → `OK (48 tests, 95
  assertions)` — includes parse/entities, compile + cache hit/miss,
  layer-salt invalidation with untouched mtimes, forget/flush,
  `message(name, data)`, typecheck pass + reject.
- `vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress` →
  `[OK] No errors` (zero-regex enforced; no `preg_*` in
  `src/Teleframe/Message/`).
- `composer verify` → full suite green (see task report / coordinator output).

**Commits:** none (coordinator commits; Phase 5d files in the tree remain
owned by this worker's report).