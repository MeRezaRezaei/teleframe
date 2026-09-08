# Phase 5c — Keyboard Objects (execution plan)

**Parent:** `specs/2026-09-08-keyboard-objects-design.md` (this layer's spec) +
`docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5c.
**Created:** 2026-09-08. **Depends on:** Phase 5a uprate router (the shared
`HandlerRegistry`/`UpdateDispatcher` the callback bridge routes through).

## RULINGS enacted (gap doc §III)

- **Q15 (c):** HMAC-signed compact callback `v1:<kid>:<keyIdx>:<arg>:<sig≤10B>`,
  msg+chat bound into the MAC (`hash_hmac('sha256', …)` truncated to 10 raw
  bytes, base64url, `hash_equals` compare) — stateless, replay-scoped, fits
  64B (enforced at encode). `kid` = `Menu::keyId()` incl. the Q16 version
  byte; the key→action table resolves the uprate route; "menu expired" goes
  via `BotClient::answerCallbackQuery` (injectable closure seam).
- **Q16 (a):** `Menu::id()` = deterministic content-hash (`sha1` of canonical
  button shapes), stable across deploys; `version()` byte rotates `keyId()`
  without changing content; re-registration unseats old versions.

## Findings recap

- `callable` is not a valid promoted-property type in PHP 8.3 — the router's
  answer seam is typed `?\Closure`.
- phpstan treats array-shape PHPDocs as certain: the rows validator must read
  the raw `array<mixed>` input, not the documented post-validation shape, or
  level 5 flags the guard clauses as tautologies.
- A callback token can only bind what the sender knew: `render()` before a
  message exists signs the **chat only** (message-id wildcard); the router
  confirms "message-bound strictly, else chat-bound" on decode. This is the
  narrowest scope information-theoretically available at sign time.

## Tasks

- [x] **Task 1: `CallbackData` codec (Q15).** `encode`/`decode` with the fixed
      Q15 signature: MAC over `keyId:keyIdx:arg "\0" msgBind "\0" chatBind`,
      10-byte truncation, base64url, `hash_equals`; colon-split (zero-regex);
      64-byte budget + delimiter guards at encode; decode null on any
      mismatch. Tests: round-trip, budget, forge/tamper/rebound-msg/relay/
      wrong-secret/malformed rejection.
- [x] **Task 2: `Menu` value object (Q16).** Immutable rows of
      `{text, route, arg?}`; `id()` content hash (stable across deploys),
      `version()` byte, `keyId()` = hash-prefix + version; `buttonAt()`
      flat lookup; `render()` → Bot API `inline_keyboard` array with every
      callback_data chat/message-bound. Tests: id determinism vs content,
      version rotation, render shape + decodable tokens.
- [x] **Task 3: `KeyboardRegistry`.** keyId→Menu table, `register()` fluent +
      `count()`/`all()` (HandlerRegistry-bone), `isLive()`, `routeOf()`;
      registering a newer menu version unseats the old keyId. Tests:
      resolution, unknown/out-of-range null, rotation expiry, idempotence.
- [x] **Task 4: `MenuRouter` + expiry.** `dispatch()` bridge (decode →
      two-scope confirm → registry resolve → synthetic uprate through the
      shared `UpdateDispatcher`/`HandlerRegistry::on()` table, arg receipt via
      `%s`); `expire()` answers "menu expired" through the injectable
      BotClient `answerCallbackQuery` closure (`MenuRouter::EXPIRED_TEXT`).
      Tests: no-op surfaces, chat-bound confirmation, message-bound strictness,
      expiry answer payload.
- [x] **Task 5: injection suite.** `tests/Handler/Keyboard/InjectionTest.php`:
      forged signature rejected (403-equivalent), rotated menu rejected as
      expired, replayed token with rebound msg/chat rejected, legit token
      routes to the right handler (entry-point wiring + `%s` arg).
- [x] **Task 6: docs.** `docs/keyboards.md` (build/send/register/receive +
      injection model), this spec + plan, roadmap Phase 5c specs not touched
      (coordinator ticks roadmap per worker constraint).

## Gate evidence (2026-09-08)

- `vendor/bin/phpunit tests/Handler/Keyboard --no-coverage` → `OK (42 tests,
  99 assertions)` — includes the InjectionTest vectors, codec, menu,
  registry, and router suites.
- `vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress` →
  `[OK] No errors` (zero-regex enforced; no `preg_*`).
- `composer verify` → full suite green (see proof commands in the task
  report); `composer dump-autoload` clean.

**Commits:** none (coordinator commits; Phase 5c files in the tree remain
owned by the 5c worker). Roadmap Phase 5c ticks are the coordinator's step
(`docs/superpowers/plans/2026-09-07-master-roadmap.md` is on the do-not-
touch list for this worker).