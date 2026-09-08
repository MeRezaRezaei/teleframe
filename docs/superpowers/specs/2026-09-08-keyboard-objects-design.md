# Keyboard Objects (Phase 5c) — Design

**Parent:** `docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5c.
**Status:** Spec — owner gate = spec review only. RULINGS Q15 + Q16 applied
verbatim from `2026-09-07-framework-layers-gap-analysis.md` §RULINGS; nothing
here re-opens decided rulings.

## 1. RULINGS applied

- **Q15 — callback format:** HMAC-signed compact callback
  `v1:<kid>:<keyIdx>:<arg>:<sig ≤10B>`, msg+chat bound into the MAC.
  Implemented: `hash_hmac('sha256', payload, secret, true)` truncated to the
  first 10 raw bytes, base64url-encoded with trailing `=` trimmed (~14
  chars), compared with `hash_equals`. The MAC is recomputed over
  `keyId:keyIdx:arg "\0" msgBind "\0" chatBind` — the exact values bound at
  encode time. `kid` (a.k.a. `keyId`) = the menu key id **with the Q16
  version byte appended**; `keyIdx` = the button's flat row-major index; the
  decoded `route` is the key reference `keyId:keyIdx`. The registered
  key→action = the uprate route string held by `KeyboardRegistry`; the
  expiry answer goes through `BotClient::answerCallbackQuery`.
- **Q16 — id lifecycle:** deterministic content-hash keyboard ids, stable
  across deploys; `version()` byte rotates the token key without changing
  content. `Menu::id()` = `sha1` of the canonical button shapes;
  `Menu::keyId()` = `id()[0..7] . dechex(version)`. Re-registering a newer
  version of the same menu id unseats the previous version's keyId in the
  registry, so old tokens stop resolving.

## 2. New surface (all under `MeRezaRezaei\Teleframe\Handler\Keyboard\`)

### `CallbackData` — pure stateless codec

- `encode(string $keyId, int $keyIdx, string $arg, int|string|null
  $bindMsgId, int|string|null $bindChatId, string $secret): string`
- `decode(string $token, int|string|null $bindMsgId, int|string|null
  $bindChatId, string $secret): ?array{route: string, arg: string}` — null on
  ANY mismatch: unknown format, malformed shape (colon-split, exactly 5
  parts), tampered field, rebound msg/chat, wrong secret.
- Enforces the 64-byte budget at encode (throws when exceeded), and rejects
  `:`/NUL inside the signed fields so the token stays zero-regex splittable.
- Signature is recomputed-and-compared over the exact bound ids; a `null`
  message id is the pre-send chat-bound wildcard (the narrowest scope the
  sender knows — you cannot sign a message id that does not exist yet). Any
  message-bound token stays message-bound: demoting it to a wildcard recompute
  fails on the MAC.

### `Menu` — immutable keyboard value object

- `__construct(list<list<button>> $rows, int $version = 1)`; `button()` =
  `{text: string, route: string, arg?: string}`; validated at construction.
- `buttonAt(int $keyIdx): ?array` — flat, 0-based, row-major.
- `id(): string` / `version(): int` / `keyId(): string` — Q16 trio.
- `render(string $secret, int|string $bindChatId, int|string|null
  $bindMsgId = null): array` — the exact Bot API `InlineKeyboardMarkup`
  array (`['inline_keyboard' => [[['text'=>…,'callback_data'=>…]]]]`), every
  token signed with the menu's own keyId.

### `KeyboardRegistry` — the live key→action table

- `register(Menu): static` (fluent, mirrors `HandlerRegistry` bone-structure:
  immutable records, `count()`, `all()`); registering a higher version of the
  SAME menu `id()` unseats the prior version's `keyId()`.
- `isLive(string $keyId): bool`, `routeOf(string $keyId, int $keyIdx):
  ?string` — route = the button's uprate route string; `null` = unknown or
  rotated (the "menu expired" trigger).

### `MenuRouter` — callback → uprate bridge

- `__construct(UpdateDispatcher $dispatcher, KeyboardRegistry $keyboards,
  string $secret, ?\Closure $answer = null)` — the `$answer` closure is the
  `BotClient::answerCallbackQuery($id, $text)` seam (schema skill:
  `answerCallbackQuery.md`); null disables the network call.
- `dispatch(Update): mixed` — no-op on non-`callback_query` / dataless
  updates; two-scope decode (message-bound strictly, else chat-bound
  wildcard) → live-key resolution → re-dispatch a synthetic uprate whose
  constructor is `route` (arg appended), landing on the shared
  `HandlerRegistry::on()` table with `%s` arg receipt — a callback routes
  just like a message update.
- `expire(array $callback): null` — the "menu expired" answer (public so
  other send surfaces reuse the exact wording).

## 3. Routing contract

```
callback_query update
  └─ MenuRouter::dispatch                        (registered on('callback_query', …))
       ├─ CallbackData::decode(data, msgId, chatId, secret) ─ mismatched → expire()
       ├─ KeyboardRegistry::routeOf(keyId, keyIdx)          ─ null       → expire()
       └─ UpdateDispatcher::dispatch(new Update('_': route [ ' ' arg ]))
            └─ shared HandlerRegistry::on(route-as-pattern, handler)  ← the dev's
               handler runs with $arg (sscanf %s), exactly like /start %s
```

Dispatch precedence (gap doc §III "stage machine" note) is untouched: the
keyboard stage sits where the host mounts the `callback_query` entry point.

## 4. Injection model (the gate)

| Vector | Mechanism |
| --- | --- |
| Forged signature | MAC recompute over bound ids mismatches → `decode()` null |
| Replay, rebound message | msg id is inside the MAC → mismatch |
| Relay, other chat | chat id inside the MAC → mismatch (plus chat-bound pre-send tokens cannot leave their chat) |
| Rotated/old menu | keyId not live in `KeyboardRegistry` → `routeOf` null → "menu expired" |

## 5. Non-goals (deferred, non-breaking)

- `keyIdx`-to-route sugar on the registry (button groups) — the button's own
  route + arg already covers it.
- MTProto `updateBotCallbackQuery` mirror path (Bot API `callback_query`
  shape is the `answerCallbackQuery` surface; the MTProto-side
  handler-attribution bridge is a later phase).
- A send-engine convenience (`sendMessage(…, reply_markup: $menu->render(…))`)
  — `render()` returns the exact array the existing `BotClient::sendMessage`
  options accept.
- Template / stage-machine integration (5d/5e).

## 6. Plan pointer

Execution plan: `plans/2026-09-08-phase5c-keyboard-objects.md` (ticked).