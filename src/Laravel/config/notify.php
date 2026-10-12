<?php

/*
|--------------------------------------------------------------------------
| Teleframe Notify — notification/logger core (design 2026-09-24)
|--------------------------------------------------------------------------
| Merged into config under the `teleframe.notify` key by
| NotifyServiceProvider (this package's own provider — registered via
| composer.json extra.laravel.providers). Hosts may publish and override
| any key; the facade resolves `teleclient.notify.service` from these.
|
| Topic = predefined log level: the 8 PSR-3 levels map to the notify chat
| (forum supergroup: each level = one message_thread_id). Rows below are
| the registry seeds merged over the sibling TopicRegistry::defaults() —
| edit level/chat/thread/throttle here without touching the engine.
*/

return [

    /*
    | Default target chat for the topic rows that carry no explicit chat:
    | '@channel' | chat_id | forum supergroup id. '' = unconfigured — the
    | facade stays constructible and callable; sends throw on first
    | transport use (TStore posture), never silent-drop.
    */
    'default_chat' => env('TELEFRAME_NOTIFY_CHAT', ''),

    /*
    |--------------------------------------------------------------------------
    | Transport — WHICH Telegram account the notifier sends as. This is the
    | key that made `UserScopeTransport` reachable; before it existed the
    | class shipped with no config key, no import and no binding anywhere in
    | the package, so a capability, a contract and a test existed and no
    | install could reach it.
    |
    |   'bot'         (DEFAULT) — Bot API over `teleframe.bot_token`. A bot
    |                  cannot reach a chat it was not added to, cannot post
    |                  into a supergroup it is not in, and spends a bot's
    |                  rate budget.
    |   'user_scope'  — MTProto as a signed-in USER account. Needs an api_id
    |                  / api_hash plus a session (teleframe config, or a
    |                  `USER_SCOPE_KEY` binding). It reaches every chat the
    |                  account is in and spends that account's budget.
    |
    | MTProto forces two seams a topic row cannot carry, and BOTH now have
    | shipped implementors, so this key alone is enough — no host code:
    |   - the chat id / @username on a topic row → `DialogPeerResolver`,
    |     which reads the account's own `messages.getDialogs` for the
    |     `access_hash` MTProto requires. A chat the account has never
    |     opened cannot be resolved this way and the send THROWS naming the
    |     chat; open it once with the account, or bind
    |     `USER_PEER_RESOLVER_KEY` with a host resolver.
    |   - the rendered HTML attachment → `MtProtoDocumentUploader`, the
    |     `upload.saveFilePart` loop. Without it the long half of an alert
    |     cannot ship, so that send throws too rather than going out as a
    |     caption with the report missing.
    |
    | Selecting 'user_scope' with NO session available builds the transport
    | unconfigured: the first send throws and names the fix. There is no
    | value of this key, and no host setup, that produces a silent no-op —
    | a watchdog alert that quietly vanished is the failure this module
    | exists to prevent.
    |
    | NOT PROVEN AGAINST LIVE TELEGRAM: nothing in this repo has ever run a
    | user-scope notify send against a real account. What is proven is that
    | a config value alone reaches a real `messages.sendMessage` on the user
    | path, and that the refusals are loud.
    */
    'transport' => env('TELEFRAME_NOTIFY_TRANSPORT', 'bot'),

    'user_scope' => [
        /* How many dialogs the peer resolver reads when it needs an
           access_hash. A topic row's chat must be inside this window; raise
           it for an account with a long dialog list. */
        'dialog_limit' => (int) env('TELEFRAME_NOTIFY_USER_SCOPE_DIALOG_LIMIT', 200),
    ],

    /* Inline budget (chars) for the brief + metadata block in the message;
       the long body goes to the HTML file attachment instead (§7). */
    'inline_budget' => (int) env('TELEFRAME_NOTIFY_INLINE_BUDGET', 800),

    /* Temp dir seam for rendered attachments ('' = system temp). */
    'attachment_dir' => env('TELEFRAME_NOTIFY_ATTACHMENT_DIR', ''),

    /* Default premium flag — raises capability limits (×2 file, ×4.5
       send-rate rows). Per-send override: PendingNotify::premium(bool).

       THE OWNER ASKED FOR MORE THAN THIS: "enhanced check on … if they have
       premium" is a LIVE flag, not a config guess. With
       `premium_probe.user_id` set AND the host binding
       teleclient.notify.premium.transport (users.getFullUser is MTProto — a
       bot token cannot call it), every send re-confirms the flag against the
       account and the raised caps apply. A probe that fails answers
       `unknown` and the static flag above still governs, so a broken probe
       never costs a send its caps and never breaks the send.

           $this->app->bind(NotifyServiceProvider::PREMIUM_TRANSPORT_KEY, fn () =>
               fn (string $method, array $params): array =>
                   app(\\MeRezaRezaei\\Teleframe\\Laravel\\Services\\TeleframeClient::class)
                       ->user()->mtproto->call($method, $params)); */
    'premium' => (bool) env('TELEFRAME_NOTIFY_PREMIUM', false),
    'premium_probe' => [
        /* Telegram user id whose premium flag is probed. 0 = no live probe
           (the static flag above governs, as before). */
        'user_id' => (int) env('TELEFRAME_NOTIFY_PREMIUM_USER_ID', 0),
        /* Sync cache TTL for the probe result (a failing target is not
           re-probed on every send). */
        'ttl_seconds' => (int) env('TELEFRAME_NOTIFY_PREMIUM_TTL', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate + budget guards — the constraints that were computed and never
    | consulted (CapabilityCheck::checkRate's send_rate / per_chat_rate /
    | topic_budget rows and each topic row's throttle_per_hour).
    |
    | They are now enforced on every send, counted in the host's PSR-16 cache
    | so a budget holds across processes. Over budget = a LOUD
    | NotifyException, never a silent drop — the same posture as every other
    | cap in the pipeline.
    |
    | `enforce => false` makes the guards ADVISORY: the over-budget send is
    | logged at warning and still goes out. The right posture for a host
    | that would rather flood than lose a watchdog alert.
    |
    | WATCH OUT: the shipped per_chat_rate row is 1 msg/s (Telegram's real
    | per-chat bot limit), so two notifications in the same second to the
    | same chat raise. Merge a `capabilities` row to widen it, or set
    | enforce=false.
    */
    'budgets' => [
        'enforce' => (bool) env('TELEFRAME_NOTIFY_BUDGET_ENFORCE', true),
    ],

    /*
    | Exception reporting. OFF by default: sending every Laravel exception to
    | a chat is an operator decision, not a package default.
    |
    | When 'enabled' is true the package registers a reportable callback on
    | the HOST's own exception handler
    | (Illuminate\Contracts\Debug\ExceptionHandler::reportable — the seam
    | Sentry/Bugsnag use), so an unhandled exception is rendered to a
    | self-contained, browser-openable HTML file and delivered to the notify
    | chat with no host code. The host's render path, its log line and its
    | dontReport list are untouched: this is a guest on the host's pipeline,
    | never its owner. A notification failure is logged and swallowed — an
    | exception handler that itself throws would REPLACE the exception the
    | host was reporting.
    |
    | What is actually sent is the exception CLASS, a short REDACTED summary,
    | the code and file:line. Never a stack trace, and never an unredacted
    | string — a session string, auth key or bot token quoted inside an
    | exception message is replaced with [redacted] before it leaves the
    | process (ExceptionRedactor).
    |
    | 'topic' empty (default) → the browser-renderable HTML report on the
    | 'report' topic. 'topic' set → a PSR-3 line on that topic row at
    | 'level' ('error' by default), for operators who want exceptions in the
    | same stream as the rest of their log.
    */
    'exceptions' => [
        'enabled' => (bool) env('TELEFRAME_NOTIFY_EXCEPTIONS', false),
        'level' => env('TELEFRAME_NOTIFY_EXCEPTIONS_LEVEL', 'error'),
        'topic' => env('TELEFRAME_NOTIFY_EXCEPTIONS_TOPIC', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue choice — the notification core is a QUEUE, so the shipped default
    | is queue-shaped (design §5; the owner: "it's just like using a queue to
    | queue sending that message into that channel with the way it should get
    | there"). `auto` is NOT "whatever is easiest" — it is the async posture
    | with a resolvable fallback, so a logger never silently blocks the host's
    | request on a Telegram round-trip.
    |
    |   'auto'    (DEFAULT) — pick the best async transport the host actually
    |             has, in order: (1) ext-amqp loaded → the durable AMQP driver
    |             (tframe.notify, tframe.notify.dl); (2) the host's own queue
    |             connection is async (database/redis/sqs/… — the `database`
    |             driver needs a table, not a broker) → the Laravel-queue
    |             driver; (3) nothing async is configured → the `queue_fallback`
    |             below, WITH a loud one-shot warning. Never a silent inline
    |             block: the warning is the point.
    |   'laravel' — force the host-queue driver (loud if unavailable).
    |   'amqp'    — force the AMQP driver (loud without ext-amqp).
    |   'sync'    — OPT-IN inline delivery: the handler runs in the caller's
    |             process, on the caller's thread. Correct for a CLI probe,
    |             a script, or a host that genuinely wants ordered inline
    |             sends; wrong for a web request's logger, because one slow
    |             Telegram call becomes the request's latency. Existing hosts
    |             pinned to 'sync' are untouched.
    |
    | What the resolved driver IS: `teleframe:notify work` prints it, and the
    | resolved description is bound as `teleclient.notify.queue.report`
    | (['configured' => …, 'resolved' => …, 'async' => bool, 'reason' => …,
    | 'connection' => …, 'queue' => …, 'dead_letter' => …]) so a health/doctor
    | command can show the operator the same answer the container resolved.
    */
    'queue' => env('TELEFRAME_NOTIFY_QUEUE', 'auto'),

    /*
    | Queue connection + queue name for the 'laravel' driver. '' connection =
    | follow the host's `queue.default` (the package never picks an infra the
    | host did not choose). The name is the Laravel queue the host's own
    | `queue:work` (or `teleframe:notify work`) drains; tframe.notify is an
    | opaque wire key, never renamed.
    */
    'queue_connection' => env('TELEFRAME_NOTIFY_QUEUE_CONNECTION', ''),
    'queue_name' => env('TELEFRAME_NOTIFY_QUEUE_NAME', 'tframe.notify'),

    /*
    | What 'auto' does when the host has NO async transport. 'sync' (default)
    | keeps notifications working — inline, plus a loud one-shot warning naming
    | this key — so a fresh install still notifies. 'throw' is the strict
    | opt-in: a host that believes it is queue-shaped gets a RuntimeException
    | at resolve time instead of a silent inline block.
    */
    'queue_fallback' => env('TELEFRAME_NOTIFY_QUEUE_FALLBACK', 'sync'),

    /* AMQP creds for the wave-2 driver (mirrors teleframe.tstore.amqp).

       NOT 'guest'/'guest'. The store queue refuses the stock pair against a
       remote broker, and the notify queue now refuses it too — this queue
       carries message briefs, context and targets, so "default credentials
       happen to work" is the wrong default to ship. Empty means "unset": on a
       loopback broker that still works, and off loopback it refuses loudly
       instead of quietly publishing to a broker nobody chose. */
    'amqp' => [
        'host' => env('TELEFRAME_NOTIFY_AMQP_HOST', '127.0.0.1'),
        'port' => (int) env('TELEFRAME_NOTIFY_AMQP_PORT', 5672),
        'vhost' => env('TELEFRAME_NOTIFY_AMQP_VHOST', '/'),
        'user' => env('TELEFRAME_NOTIFY_AMQP_USER', ''),
        'password' => env('TELEFRAME_NOTIFY_AMQP_PASSWORD', ''),
        // Socket-level facts, so a half-open connection is detected by the
        // broker instead of by the OS TCP timeout on the first write.
        'heartbeat' => (int) env('TELEFRAME_NOTIFY_AMQP_HEARTBEAT', 30),
        'connect_timeout' => (float) env('TELEFRAME_NOTIFY_AMQP_CONNECT_TIMEOUT', 3.0),
        'read_timeout' => (float) env('TELEFRAME_NOTIFY_AMQP_READ_TIMEOUT', 5.0),
        'write_timeout' => (float) env('TELEFRAME_NOTIFY_AMQP_WRITE_TIMEOUT', 5.0),
    ],

    /*
    | WIRED (dead-config audit 2026-09-27; both keys were DEAD until then).
    |
    | These two used to read "Schedule seam: 'null' (off — probe only) or
    | 'temporal' (wave 2). The gap watchdog (backup heartbeat no-show) lands
    | on this seam" — and the seam existed, but these keys did not reach it:
    | src/Laravel/NotifyServiceProvider.php bound teleclient.notify.schedules
    | to `new NullSchedulePublisher` in a closure that read no config, so
    | TELEFRAME_NOTIFY_SCHEDULES=temporal changed nothing and nothing read
    | schedules_dsn.
    |
    | Both are now read by NotifyServiceProvider, at RESOLVE time, with the
    | same posture as the two wirings Cycle 19 already fixed
    | (teleframe.app.schedules, teleframe.store.backup.schedule.publisher):
    |   'null'      the shipped default → NullSchedulePublisher, and install()
    |               honestly reports 'skipped'. Unchanged: a package that arms
    |               a recurring no-show check on install is a surprise, and
    |               that ruling is in the ledger.
    |   'temporal'  a host-bound TemporalScheduleClient WINS verbatim; else
    |               TemporalClientResolver builds the package's own SDK-backed
    |               client from the DSN below; with neither it REFUSES HERE by
    |               name, naming temporal/sdk. It never degrades to 'null'.
    |   else        LOUD RuntimeException at resolve time.
    |
    | This seam only decides whether the no-show check goes through TEMPORAL.
    | The check is NOT stranded either way: StoreBackupServiceProvider
    | registers `teleframe:notify watch --check` on Laravel's own scheduler,
    | hourly, which is why the no-show check works with this key at 'null'.
    |
    | The task queue is the publisher's literal 'default' and is NOT a key
    | here: reading an undeclared one is the defect the app wiring had to undo.
    | Bind your own client for a different queue.
    */
    'schedules' => env('TELEFRAME_NOTIFY_SCHEDULES', 'null'),
    /* WIRED — read by the 'temporal' branch above and handed to the Temporal
       client as the FRONTEND address. Nothing in this package dials it; the
       resolved client does. */
    'schedules_dsn' => env('TELEFRAME_NOTIFY_TEMPORAL_DSN', '127.0.0.1:7233'),

    /*
    | Topic registry seeds (§4) — merged over TopicRegistry::defaults().
    | Level topics default to teleframe.notify.default_chat; a forum
    | supergroup maps each level to its own message_thread_id (1–8).
    | Domain topics: backup (heartbeat), backup.missing (watchdog alert),
    | report (report() lands here). Hosts add/override rows freely.
    */
    'topics' => [
        ['name' => 'app.debug', 'level' => 'debug', 'thread_id' => 1],
        ['name' => 'app.info', 'level' => 'info', 'thread_id' => 2],
        ['name' => 'app.notice', 'level' => 'notice', 'thread_id' => 3],
        ['name' => 'app.warning', 'level' => 'warning', 'thread_id' => 4],
        ['name' => 'app.error', 'level' => 'error', 'thread_id' => 5],
        ['name' => 'app.critical', 'level' => 'critical', 'thread_id' => 6],
        ['name' => 'app.alert', 'level' => 'alert', 'thread_id' => 7],
        ['name' => 'app.emergency', 'level' => 'emergency', 'thread_id' => 8],
        ['name' => 'backup', 'level' => 'info'],
        ['name' => 'backup.missing', 'level' => 'alert'],
        ['name' => 'report', 'level' => 'notice'],
    ],

    /*
    | Capability merge rows (§6) — the built-in table ships in
    | CapabilityTable (text_message 4096, caption 1024, media_album 10,
    | file_upload 50 MiB bot / 2 GiB mtproto ×2, send_rate 30 msg/s ×4.5,
    | per_chat_rate 1 msg/s ×4.5, topic_budget 60 msg/h ×4.5 — enforcement
    | of the rate rows is wave 3). A row IS a decision; merge overrides:
    |   ['feature' => 'file_upload', 'base' => 100, 'unit' => 'MiB',
    |    'premiumMultiplier' => 2.0]
    */
    'capabilities' => [],
];
