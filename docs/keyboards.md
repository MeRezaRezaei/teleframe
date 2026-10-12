# Keyboards — signed, live, and disposable

> **Phase 5c.** Keyboard objects with deterministic identity, a stateless
> HMAC-signed callback format that fits the 64-byte budget (Q15), and a
> version byte that expires old menus the moment a new one is registered
> (Q16). Callbacks are just another uprate: decode → registry → the shared
> handler table, with sscanf `%s` arg receipt identical to messages.
>
> All code lives under `MeRezaRezaei\Teleframe\Handler\Keyboard\` and is
> zero-regex (colon-split, string funcs only).

---

## 1. Build a menu

A `Menu` is an immutable value object: rows of buttons. Each button names the
uprate route it should wake up, plus an optional sscanf arg.

```php
use MeRezaRezaei\Teleframe\Handler\Keyboard\Menu;

$product = new Menu([
    [Menu::button('View', 'catalog:view', 'sku-blue')],
    [Menu::button('Buy', 'catalog:buy', 'sku-blue'), Menu::button('Back', 'catalog:back')],
], version: 1);
```

- `id()` — deterministic `sha1` content hash, stable across deploys and
  processes (Q16). The same menu built on any node has the same id.
- `version()` — a byte (1–255). Bumping it **rotates** the token key while
  the content hash stays put: old buttons stop resolving, live ones go on.
- `keyId()` — the 9-char token key id = `id()[0..7] . dechex(version)`.

### Signed tokens (Q15)

The token shape is `v1:<keyId>:<keyIdx>:<arg>:<sig>` where `sig` is the
first 10 bytes of `HMAC-SHA256("keyId:keyIdx:arg\0msgBind\0chatBind", secret)`
base64url-encoded. The message id and chat id are bound **into the MAC**, so
the same token replayed against another message or relayed into another chat
fails recomputation and decodes to `null`.

`CallbackData` is a stateless codec:

```php
use MeRezaRezaei\Teleframe\Handler\Keyboard\CallbackData;

$token   = CallbackData::encode('a1b2c3d8f', 0, 'dark', 7, 421, $secret);
$decoded = CallbackData::decode($token, 7, 421, $secret);       // {route:'a1b2c3d8f:0', arg:'dark'}
$garbage = CallbackData::decode($token, 8, 421, $secret);       // null (rebound message)
$garbage = CallbackData::decode($flippedSig, 7, 421, $secret);  // null (forged)
```

- `keyIdx` is the button's flat, row-major index; the decoded `route` is the
  key reference `keyId:keyIdx` the registry resolves to an uprate route.
- A `null` message binding is the **pre-send wildcard**: you cannot sign a
  message id that does not exist yet, so a freshly rendered menu is
  chat-bound (narrowest scope the sender knows). An edit / re-render with a
  real message id binds it exactly. The router confirms both scopes (below).
- `encode()` throws `InvalidArgumentException` on `:`/NUL inside `keyId` or
  `arg`, negative `keyIdx`, or any token that would exceed **64 bytes** — the
  Telegram budget is enforced at the construction site, not found later.

## 2. Send it

`render()` emits the exact Bot API `InlineKeyboardMarkup` array the send
engine accepts under `reply_markup`:

```php
$payload = $product->render(secret: $secret, bindChatId: 421, bindMsgId: 7);
// ['inline_keyboard' => [ [ ['text' => 'View', 'callback_data' => 'v1:...:14' ] ] ]]

$client->sendMessage(421, 'Pick a size', ['reply_markup' => $payload]);
```

Pass `bindMsgId: null` for a pre-send render (chat-bound token); pass the
real `message_id` when re-rendering/editing so the token becomes
message-scoped.

## 3. Register it (the live table)

`KeyboardRegistry` is the key→action truth: `keyId → Menu → route`. Register
the live menu; registering a newer version of the same menu id **unseats the
old one**:

```php
use MeRezaRezaei\Teleframe\Handler\Keyboard\KeyboardRegistry;

$keyboards->register($product);                     // v1 live
$keyboards->register(new Menu($product->rows, version: 2));  // v1 gone

$keyboards->isLive($product->keyId());             // bool
$keyboards->routeOf($keyId, 0);                    // 'catalog:view' | null
```

Any token from an unseated version resolves to `null` — the "menu expired"
path. Route strings live in the shared `HandlerRegistry::on()` table, not
here; the table only maps buttons to the routes they wake.

## 4. Receive it — callbacks are just uprates

Wire one entry point in the shared table and every future menu button flows
through it automatically:

```php
use MeRezaRezaei\Teleframe\Handler\Keyboard\MenuRouter;

$handlers->on('callback_query', fn (Update $u, MenuRouter $router) => $router->dispatch($u));
$handlers->on('catalog:view %s', function (Update $u, string $arg) {
    // $arg === 'sku-blue' — the signed token arg, message-style receipt
});
```

`MenuRouter` is the bridge:

1. **Decode** the signed data against the bound chat/message ids. Any codec
   mismatch (forged, tampered, rebound, relayed) → expiry answer.
   The pre-send chat-bound token is confirmed via its chat binding — the
   only scope the sender could know.
2. **Resolve** the key reference through the live `KeyboardRegistry`. Old
   version → `null` → expiry answer.
3. **Dispatch** a synthetic uprate whose constructor is the route plus the
   signed arg, so the shared table matches and arg-injects it exactly like a
   message update.

The expiry answer ("menu expired") goes through an injectable closure — wrap
`BotClient::answerCallbackQuery` in your app:

```php
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;

$bot = new BotClient($botToken);
$router = new MenuRouter($dispatcher, $keyboards, $secret,
    fn (string $queryId, string $text) => $bot->answerCallbackQuery($queryId, $text));
```

## 5. Injection model

| Vector | Where it dies |
| --- | --- |
| Forged `callback_data` (modified client) | MAC recompute mismatch → `decode()` null |
| Replay same token, rebound message | msg id bound into MAC → recompute mismatch |
| Relay into another chat | chat id bound into MAC → recompute mismatch |
| Old menu (rotated version) | keyId no longer in the live registry → "menu expired" |

Covered end-to-end in `tests/Handler/Keyboard/InjectionTest.php` (the
gate), plus codec- and registry-level suites in the same directory.