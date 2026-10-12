# Usage — NOTIFY area: clients, sending, and the notify core

> **Release area 1 of 5.** Everything you need to acquire a Telegram client
> (user or bot), send messages through it, and run the notification/logger
> core. Every call below was verified against the shipped source.

---

## 1. Acquiring a client

The Laravel **facade** `MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe`
(alias `TF`) proxies `Laravel\Services\TeleframeClient`. This is where
`user` / `fromSession` / `bot` / `botMtproto` live — they are **not** methods
of the `MeRezaRezaei\Teleframe\Teleframe` compose class (that one is the
handler/ingest facade; see [app-framework.md](app-framework.md)).

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe;

// User account over MTProto (lazy session, dc 2 default)
$scope = Teleframe::user();                    // fills from default apiId/apiHash/session
$scope = Teleframe::user(accountId: 7);        // by tenant account
$scope = Teleframe::forAccount(7);             // alias for user()

// Directly from an exported session STRING (never commit these)
$scope = Teleframe::fromSession('1:AAH...:user:delta:salt', apiId: 123, apiHash: 'abc');

// Bot over the HTTP Bot API
$bot = Teleframe::bot('123:ABC-TOKEN');

// Bot over binary MTProto (no HTTP)
$bot = Teleframe::botMtproto('123:ABC-TOKEN');

// From the encrypted DB vault (Teleframe\Vault)
$user = Teleframe::userFromVault('main');
$bot  = Teleframe::botFromVault('supportbot', transport: 'http'); // http|mtproto

// The declared schema layer of the packaged catalog
$layer = Teleframe::schemaLayer();             // 229 (catalog) — wire speaks 227
```

The underlying client also exposes `dispatch(array): array` (one call) and
`callMany(array): array` (batched) for raw driving.

## 2. Sending as a bot (HTTP Bot API)

`BotClient` — get one from `Teleframe::bot()` — is a thin typed wrapper over
`call(string $method, array $params = []): array`:

```php
$bot->getMe();

$bot->sendMessage('@channel', 'hello', ['disable_notification' => true]);
$bot->sendMessage(123456789, 'hello');          // chat_id int|string
```

Typed helpers (same `$options` array tail): `forwardMessage`, `copyMessage`,
`sendPhoto`, `sendAudio`, `sendDocument`, `sendVideo`, `sendAnimation`,
`sendVoice`, `sendVideoNote`, `sendMediaGroup`, `sendLocation`, `sendVenue`,
`sendContact`, `sendPoll`.

## 3. Sending via the fluent builders

The shipped schema builders live under `Methods` (MTProto) and `Methods::bots()`
(Bot API). Build a request array, then hand it to your client:

```php
use MeRezaRezaei\Teleframe\Core\Methods\Methods;
use MeRezaRezaei\Teleframe\Core\Types\InputPeer;

// MTProto user send
$request = Methods::messages()->sendMessage()
    ->peer(InputPeer::user(900, 'access_hash_abc'))
    ->message('hi')
    ->randomId(random_int(1, PHP_INT_MAX))
    ->toRequest();

$scope->call('messages.sendMessage', $request);

// Bot-API style send through the same builder surface
$request = Methods::bots()->sendMessage()
    ->chatId('@channel')
    ->text('hi')
    ->toRequest();
```

`InputPeer` helpers: `InputPeer::user($id, $hash)`, `InputPeer::channel($id, $hash)`,
`InputPeer::chat($id)`, `InputPeer::self()`. Any TL constructor works as an
array with a `'_'` key (`['_' => 'inputPeerUser', 'user_id' => 900, 'access_hash' => '…']`).

Send-message builder options include `replyTo`, `replyMarkup(mixed)`,
`entities`, `scheduleDate`, `sendAs`, `effect`, `allowPaidStars`, and
`richMessage`; the Bot-side builder adds `parseMode`, `linkPreviewOptions`,
`disableNotification`, `protectContent`, `messageEffectId`, `replyParameters`,
`replyMarkup(string)`. Every builder ends in `toRequest(): array`.

### Keyboards

```php
use MeRezaRezaei\Teleframe\Core\Types\ReplyKeyboard;
use MeRezaRezaei\Teleframe\Core\Types\InlineKeyboard;

$kb = (new ReplyKeyboard)
    ->row(['/start', 'Help'])
    ->button('/stop')
    ->resize()->oneTime();                      // ->toArray() | ->toJson()

$ikb = (new InlineKeyboard)->row([['text' => 'Go', 'callback_data' => 'go']]);
```

## 4. The notify core

Facade: `MeRezaRezaei\Teleframe\Laravel\Facades\Notify`
(accessor `teleclient.notify.service`, a rebindable seam).

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\Notify;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\AttachmentFile;

// Plain log: routed by LEVEL to the level's topic / forum thread
Notify::log(Level::Info, 'backup completed', ['bytes' => 48127]);

// Full control: topic → brief → context → (attachment, chat, premium) → send
Notify::to('app.warning', 'ingest stalled', ['account_id' => 7])
    ->attachFile(new AttachmentFile('stalled.html', '/tmp/stalled.html', 'text/html', 1024))
    ->to('@ops-log', threadId: 12)
    ->send();

// Structured report: title + sections (heading => content, listed as
// HTML sections) — lands on the 'report' topic with a file attachment
Notify::report('deploy', ['Checks' => ['phpunit: ok', 'smoke: ok'], 'Output' => '…'], ['rev' => 'abc123']);

// The service root (same instance the facade routes through)
$service = Notify::service();
```

**Levels** are the 8 PSR-3 names: `Level::Debug|Info|Notice|Warning|Error|Critical|Alert|Emergency` (`Level::names()`, `Level::fromPsr3('info')`).

**PendingNotify** is immutable — each method returns a new instance:
`topic(string)`, `level(Level)`, `to(int|string $chat, ?int $threadId = null)`,
`with(array $context)`, `premium(bool)`, `attach(AttachmentFile)`,
`send(): array` (inline), `queue(): string` (async, if a queue driver is bound).

**Topics** are predefined level routes (`Topic::predefined()`: `app.debug`↔thread 1 …
`app.emergency`↔thread 8, plus `backup`, `backup.missing`, `report`). A topic row
is: name, level, description, optional chat/threadId, optional `throttlePerHour`.
Tune rows in config — `teleframe.notify.topics` (seeds shown in
`src/Laravel/config/notify.php`). `Topic::resolve('app.warning')` /
`Topic::topicFor(Level::Warning)` look a row up.

**Capabilities** are enforced via `CapabilityCheck`/`CapabilityTable`
(ship defaults: 4096-char text, 1024 caption, 50 MiB bot file upload ×2 premium,
30 msg/s send rate ×4.5 …; rate rows are advisory in this slice). Premium
honoring is behind `PremiumProbe`/`PremiumStatus` and `PendingNotify::premium()`.

### `teleframe:notify`

```bash
php artisan teleframe:notify send                 # probe: --brief=… --topic=… --level=info
php artisan teleframe:notify topics               # list topics + threads
php artisan teleframe:notify work --max=10        # drain the queue
php artisan teleframe:notify watch --register=backup --every=86400   # watchdog a topic
php artisan teleframe:notify watch --touch=backup                    # record a heartbeat
php artisan teleframe:notify watch --check        # run the no-show check, print alerts
php artisan teleframe:notify schedules --install  # probe-install the check on the schedule seam
```

### Config (`teleframe.notify.*`, env `TELEFRAME_NOTIFY_*`)

| Key | Default | Meaning |
| --- | --- | --- |
| `default_chat` | `''` | `'@channel' \| chat_id \| forum supergroup id` for topics with no explicit chat. **Empty = unconfigured**: the facade resolves and is callable, sends throw on first transport use — never silent-drop |
| `inline_budget` | `800` | chars for brief + metadata inline; the long body goes to an HTML file attachment |
| `attachment_dir` | `''` | temp dir for rendered attachments ('' = system temp) |
| `premium` | `false` | default premium flag (×2 file, ×4.5 rate rows) |
| `queue` | `sync` | `sync` (inline, zero infra, shipped default) \| `amqp` (durable `tframe.notify`; loud without `ext-amqp`) |
| `schedules` | `null` | `null` (off — probe only) \| `temporal` (wave 2) |
| `capabilities` | `[]` | merge rows over the built-in capability table |

## 5. Template plans (`MessageFactory`)

`Teleframe\Message\MessageFactory` compiles named template plans into
`{text, entities, reply_markup?}` — reusable message shapes:

```php
use MeRezaRezaei\Teleframe\Message\MessageFactory;

// file-based templates through the compiler (resolve by name)
$plan = MessageFactory::message('user.welcome', ['name' => 'Ada']);
// => ['text' => '…', 'entities' => […], 'reply_markup' => […]]

$factory = new MessageFactory($compiler);
$same    = $factory('user.welcome', ['name' => 'Ada']);   // __invoke

// media fallback: run a factory to a validated media plan
$media = $factory->media('user.photo', fn () => [...], ['user_id' => 7]);
```

Feed `$plan['text']`/`$plan['reply_markup']` straight into `sendMessage`
(options), or `BotClient::call('sendMessage', …)`.

---

Related: [store-backup.md](store-backup.md) (where notify's `backup` heartbeat /
`backup.missing` watchdog originate) · [app-framework.md](app-framework.md)
(handlers that reply) · [mtproto.md](mtproto.md) (the wire under `user()` /
`botMtproto()`).