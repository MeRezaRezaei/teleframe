<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Laravel\Notify\ExceptionReporter;
use MeRezaRezaei\Teleframe\Laravel\Notify\LaravelQueueNotificationQueue;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Notify\AmqpNotificationQueue;
use MeRezaRezaei\Teleframe\Notify\BotApiTransport;
use MeRezaRezaei\Teleframe\Notify\CapabilityCheck;
use MeRezaRezaei\Teleframe\Notify\CapabilityTable;
use MeRezaRezaei\Teleframe\Notify\DialogPeerResolver;
use MeRezaRezaei\Teleframe\Notify\FacadeSupport;
use MeRezaRezaei\Teleframe\Notify\HtmlAttachment;
use MeRezaRezaei\Teleframe\Notify\LazyUserScope;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\MessageSegmenter;
use MeRezaRezaei\Teleframe\Notify\MtProtoDocumentUploader;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\NotificationQueueContract;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotificationTransportContract;
use MeRezaRezaei\Teleframe\Notify\NotifyCommand;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use MeRezaRezaei\Teleframe\Notify\PremiumProbe;
use MeRezaRezaei\Teleframe\Notify\SyncNotificationQueue;
use MeRezaRezaei\Teleframe\Notify\Topic;
use MeRezaRezaei\Teleframe\Notify\UserScopeTransport;
use MeRezaRezaei\Teleframe\Store\Queue\AmqpUploadQueue;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalClientResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Throwable;

/**
 * NotifyServiceProvider — self-contained wiring for the notification core
 * (design §2/§9/G9): config merge (teleframe.notify.*), facade accessor
 * binding (FacadeSupport::SERVICE_KEY), queue seam, null schedule seam, and
 * the CLI command. Registered via composer.json extra.laravel.providers —
 * deliberately NOT hooked into TeleframeServiceProvider (parallel-session
 * ownership, never-commit).
 *
 * QUEUE POSTURE (this provider's default is the async one): the notification
 * core is a queue — the owner asked for exactly that — so teleframe.notify.queue
 * ships as 'auto', which resolves the best ASYNC transport the host actually
 * has (its own Laravel queue; ext-amqp only when the operator named a broker)
 * and never silently falls back to blocking the caller's request on a Telegram
 * round-trip. 'sync' stays available as an explicit, documented opt-in, and
 * the no-async-transport case degrades to it WITH a one-shot warning (or
 * throws, under queue_fallback=throw).
 *
 * All container seams mirror the fixed teleclient.* convention; hosts
 * rebind any key.
 */
final class NotifyServiceProvider extends ServiceProvider
{
    /**
     * Container seam: the RESOLVED notify-queue description as a plain array
     * (not a driver) — ['configured' => …, 'resolved' => …, 'async' => bool,
     * 'reason' => …, 'connection' => …, 'queue' => …, 'dead_letter' => …].
     * Bound on the same resolve that builds the driver, so it always answers
     * with the answer the container actually used. This is the discovery
     * surface: `teleframe:notify work` prints the driver, and a health/doctor
     * command can read this key (the Console module is owned elsewhere, so
     * the seam — not a console edit — is what ships here).
     */
    public const QUEUE_REPORT_KEY = 'teleclient.notify.queue.report';

    /**
     * Container seam: the PREMIUM PROBE TRANSPORT —
     * callable(string $method, array $params): array — the one method dialer
     * the live premium probe needs.
     *
     * WHY A SEAM AND NOT A WIRING: users.getFullUser is an MTProto method; a
     * BOT token cannot call it. The notification core's own transport is a
     * BotClient, so the provider cannot build a probe that would ever answer
     * anything but `unknown`. A host with an MTProto user scope binds the
     * dialer it already has and the probe starts answering; with nothing
     * bound the static teleframe.notify.premium flag governs, exactly as
     * before. The alternative — a probe wired to a bot transport — would be
     * decoration again, one level down.
     *
     *   $this->app->bind(NotifyServiceProvider::PREMIUM_TRANSPORT_KEY, fn () =>
     *       fn (string $method, array $params): array =>
     *           app(TeleframeClient::class)->user()->mtproto->call($method, $params));
     */
    public const PREMIUM_TRANSPORT_KEY = 'teleclient.notify.premium.transport';

    /**
     * Container seam: the MTProto USER-account scope for the notify
     * transport — `callable(): object` returning something with
     * `call(string, array): array` (a `UserAccountScope`).
     *
     * A host that keeps its accounts in the vault, or multiplexes several,
     * binds the one it wants. It is LAZY by contract: the transport resolves
     * it on the first send and never at register/boot, so binding it cannot
     * dial Telegram from a container factory for a notifier that may never
     * send. With nothing bound the provider falls back to the host's own
     * `TeleframeClient` (which the core provider registers), and with
     * neither the transport is built UNCONFIGURED and the first send throws
     * — never a silent no-op, because a watchdog alert that quietly vanished
     * is the failure this module exists to prevent.
     *
     *   $this->app->bind(NotifyServiceProvider::USER_SCOPE_KEY, fn () =>
     *       fn (): UserAccountScope => app(TeleframeClient::class)->userFromVault('ops'));
     */
    public const USER_SCOPE_KEY = 'teleclient.notify.user_scope';

    /**
     * Container seam: a host's own `peerResolver` —
     * `callable(mixed): array` mapping a chat id / `@username` to an
     * `InputPeer`. Wins verbatim over the shipped `DialogPeerResolver`, which
     * can only resolve a chat the account already has in its dialog list.
     * Bind it when a host can resolve a username the account has never
     * opened.
     */
    public const USER_PEER_RESOLVER_KEY = 'teleclient.notify.user_scope.peer';

    /**
     * Container seam: a host's own `uploader` —
     * `callable(string $path, string $mime, string $name): array` returning
     * an `InputMedia`. Wins verbatim over the shipped
     * `MtProtoDocumentUploader`, which is a plain `upload.saveFilePart` loop
     * over the resolved scope. Bind it when the host keeps its own file-id
     * bookkeeping.
     */
    public const USER_UPLOADER_KEY = 'teleclient.notify.user_scope.uploader';

    /** One inline-fallback warning per process, not one per resolve. */
    private bool $warned = false;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/notify.php', 'teleframe.notify');

        $config = $this->app->make('config');

        $this->registerQueue($config);

        $this->registerTransport($config);

        // Exception reporting seam. The renderer (HtmlAttachment) shipped
        // earlier but nothing fed it, so a Laravel exception only reached
        // Telegram when the host called Notify::report() by hand. The
        // reporter carries the redacting send path (ExceptionReporter) and
        // boot() wires it into the host's exception handler — INERT BY
        // DEFAULT, because auto-sending every exception to a chat is an
        // operator decision, not a package default.
        $this->app->singleton(ExceptionReporter::class, fn (): ExceptionReporter => new ExceptionReporter(
            enabled: (bool) ($config->get('teleframe.notify.exceptions.enabled') ?? false),
            sink: $this->exceptionSink(),
            logger: $this->app->bound(LoggerInterface::class)
                ? $this->app->make(LoggerInterface::class)
                : new NullLogger,
        ));

        // Live premium probe: only ever built when the host bound a
        // transport that can actually answer users.getFullUser.
        $this->app->singleton(PremiumProbe::class, fn ($app): PremiumProbe => new PremiumProbe(
            call: $app->make(self::PREMIUM_TRANSPORT_KEY),
            ttlSeconds: (int) ($app->make('config')->get('teleframe.notify.premium_probe.ttl_seconds') ?? 60),
            logger: $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : new NullLogger,
        ));

        // Schedule seam: 'null' (the shipped default — probe-only) or
        // 'temporal', READ FROM teleframe.notify.schedules. This used to be a
        // zero-arg `static fn (): SchedulePublisher => new NullSchedulePublisher`,
        // so both keys beside it were declared, documented and read by nothing.
        // Same posture, same reasoning and same refusal point as
        // StoreBackupServiceProvider::registerSchedules() and the
        // teleframe.app.schedules wiring: a host-bound client WINS verbatim,
        // else TemporalClientResolver hands back the package's own SDK-backed
        // client, and with neither the resolver refuses HERE, at resolve time,
        // naming the package to install. Never a silent degrade to
        // NullSchedulePublisher — that reports 'skipped' while the host
        // believes its no-show watchdog recurs.
        //
        // Note the watchdog is NOT stranded either way: this provider's seam
        // only decides whether the no-show check goes through TEMPORAL.
        // StoreBackupServiceProvider separately registers
        // `teleframe:notify watch --check` on Laravel's own scheduler, hourly.
        $this->app->singleton(NotifyCommand::SCHEDULES_KEY, static function ($app) use ($config): SchedulePublisher {
            $driver = (string) ($config->get('teleframe.notify.schedules') ?? 'null');

            if ($driver === 'null') {
                return new NullSchedulePublisher;
            }

            if ($driver === 'temporal') {
                $dsn = (string) ($config->get('teleframe.notify.schedules_dsn') ?? '127.0.0.1:7233');

                // Container-resolved when the host binds one, so a host or a
                // test decides SDK availability deterministically rather than
                // the environment (CI has no ext-grpc).
                $resolver = $app->bound(TemporalClientResolver::class)
                    ? $app->make(TemporalClientResolver::class)
                    : new TemporalClientResolver;

                $client = $app->bound(TemporalScheduleClient::class)
                    ? $app->make(TemporalScheduleClient::class)
                    : $resolver->resolve($dsn);

                return new TemporalSchedulePublisher(
                    dsn: $dsn,
                    client: $client,
                    // The shared fallback, not a literal: teleframe.notify has
                    // no declared task_queue key, and reading an undeclared one
                    // is the defect the app wiring had to undo. Falling back to
                    // the SAME source as tstore/app/backup is what stops a host
                    // from arming notify on one queue and tstore on another with
                    // a single worker serving neither. A host needing another
                    // queue binds its own TemporalScheduleClient, which wins
                    // verbatim.
                    taskQueue: TemporalSchedulePublisher::defaultTaskQueue(),
                );
            }

            throw new RuntimeException(
                "unknown notify schedule driver \"{$driver}\" — expected null|temporal."
            );
        });

        // BotClient is NOT autowirable: its ctor takes a required `string
        // $botToken`, so without this binding `$app->make(NotificationSender
        // ::class)` below throws BindingResolutionException on EVERY
        // install — the Notify facade could not even be resolved, token
        // configured or not. Binding it here keeps the facade callable and
        // inert: an empty token builds a BotClient that only fails when a
        // send actually reaches the transport, matching the package rule
        // "resolves and is callable, sends throw on first transport use".
        $this->app->bind(BotClient::class, static fn ($app): BotClient => new BotClient(
            (string) ($app->make('config')->get('teleframe.bot_token') ?? ''),
        ));

        $this->app->singleton(NotifyCommand::SERVICE_KEY, static function ($app) use ($config): NotifyService {
            $defaultChat = (string) ($config->get('teleframe.notify.default_chat') ?? '');

            // Topic registry: design §4 defaults (shipped by the sibling
            // Topic::defaults()) + host/package seeds merged from
            // teleframe.notify.topics — a row IS a decision. Rows map
            // 1:1 onto Topic::define(): name/level/description/chat/
            // thread_id/throttle_per_hour (threadId/throttle accepted as
            // camel legacy aliases).
            $registry = Topic::defaults();
            foreach ((array) ($config->get('teleframe.notify.topics') ?? []) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $registry = $registry->define(
                    name: (string) ($row['name'] ?? ''),
                    level: Level::fromPsr3((string) ($row['level'] ?? 'info')),
                    description: (string) ($row['description'] ?? ''),
                    chat: $row['chat'] ?? $defaultChat,
                    threadId: isset($row['thread_id']) ? (int) $row['thread_id'] : ($row['threadId'] ?? null),
                    throttlePerHour: isset($row['throttle_per_hour']) ? (int) $row['throttle_per_hour'] : ($row['throttle'] ?? null),
                );
            }

            // Capability rows: built-in table + host merge rows.
            $table = (new CapabilityTable)->merge((array) ($config->get('teleframe.notify.capabilities') ?? []));

            // MessageSegmenter is NOT autowirable (ctor: HtmlAttachment
            // renderer + string attachmentDir) — construct it explicitly
            // with the config'd temp seam. The seam's '' default means
            // "system temp", and it must arrive as NULL, not '': both
            // consumers fall back with `?? sys_get_temp_dir()` and a '' would
            // sail straight past the fallback into a renderer that cannot
            // create its directory (report() and any long-body attachment
            // then throw on first use).
            $attachmentDir = trim((string) ($config->get('teleframe.notify.attachment_dir') ?? ''));

            $segmenter = new MessageSegmenter(
                new HtmlAttachment,
                $attachmentDir !== '' ? $attachmentDir : sys_get_temp_dir(),
            );

            // The three capabilities the readiness audit found computed but
            // never consulted: the live premium flag, the rate rows
            // (checkRate) and the per-topic hourly budget
            // (Topic::throttlePerHour). All three are consulted inside
            // NotifyService on every send; these are the seams that make
            // them real in a Laravel host.
            $premiumUserId = (int) ($config->get('teleframe.notify.premium_probe.user_id') ?? 0);

            return new NotifyService(
                registry: $registry,
                checker: new CapabilityCheck($table),
                segmenter: $segmenter,
                queue: $app->make(NotifyCommand::QUEUE_KEY),
                sender: $app->make(NotificationSender::class),
                renderer: new HtmlAttachment,
                inlineBudget: (int) ($config->get('teleframe.notify.inline_budget') ?? 800),
                attachmentDir: $attachmentDir !== '' ? $attachmentDir : null,
                premium: (bool) ($config->get('teleframe.notify.premium') ?? false),
                logger: $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null,
                premiumProbe: ($premiumUserId > 0 && $app->bound(self::PREMIUM_TRANSPORT_KEY))
                    ? $app->make(PremiumProbe::class)
                    : null,
                premiumUserId: $premiumUserId > 0 ? $premiumUserId : null,
                budgets: self::psr16($app),
                enforceBudgets: (bool) ($config->get('teleframe.notify.budgets.enforce') ?? true),
            );
        });
    }

    public function boot(): void
    {
        $this->reportHostExceptions();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/notify.php' => config_path('teleframe/notify.php'),
            ], 'teleframe-notify-config');

            $this->commands([
                NotifyCommand::class,
            ]);
        }
    }

    /**
     * The host's PSR-16 store, or null. The send-rate / per-chat-rate /
     * per-topic-hourly counters live here, so a budget holds across the
     * processes of a web host instead of resetting with every request.
     * Null (no PSR-16 binding) keeps the counters per-process — the counters
     * are still consulted, they simply do not outlive the request.
     */
    private static function psr16(mixed $app): ?CacheInterface
    {
        if (! $app->bound(CacheInterface::class)) {
            return null;
        }

        try {
            $cache = $app->make(CacheInterface::class);
        } catch (Throwable) {
            return null;
        }

        return $cache instanceof CacheInterface ? $cache : null;
    }

    /**
     * The send behind ExceptionReporter: the redacted payload → the
     * notification core, through the SAME pipeline every other notification
     * uses (capability check → segment → queue → NotifySender).
     *
     * Two shapes, both from the shipped config:
     *   - teleframe.notify.exceptions.topic set → log() onto that topic at
     *     teleframe.notify.exceptions.level (a PSR-3 topic row, the operator
     *     chose where these land);
     *   - unset (the default) → report(), the browser-renderable HTML
     *     attachment the user asked for: "the final html gets there".
     *
     * The closure receives the redacted payload and nothing else — the raw
     * Throwable never crosses this boundary, so no future edit of the
     * renderer can start shipping a stack trace or a session string.
     *
     * @return \Closure(array{class: string, message: string, code: string|int, file: string, line: int}): void
     */
    private function exceptionSink(): \Closure
    {
        $app = $this->app;
        $config = $app->make('config');

        return static function (array $payload) use ($app, $config): void {
            $title = 'Exception: '.$payload['class'];
            $context = ['source' => 'teleframe/exception-reporter'];
            $topic = trim((string) ($config->get('teleframe.notify.exceptions.topic') ?? ''));

            if ($topic !== '') {
                $app->make(NotifyCommand::SERVICE_KEY)->log(
                    Level::fromPsr3((string) ($config->get('teleframe.notify.exceptions.level') ?? 'error')),
                    $payload['message'],
                    $context + [
                        // 'exception_class', NOT 'exception': the segmenter
                        // treats an `exception` context key as a trace-frame
                        // section and always produces a file for it, which is
                        // right for a real throwable and wrong for a summary.
                        'exception_class' => $payload['class'],
                        'code' => $payload['code'],
                        'at' => $payload['file'].':'.$payload['line'],
                    ],
                    $topic,
                );

                return;
            }

            $app->make(NotifyCommand::SERVICE_KEY)->report($title, [
                'Exception' => $payload['class'],
                'Message' => $payload['message'],
                'Code' => $payload['code'],
                'Location' => $payload['file'].':'.$payload['line'],
            ], $context);
        };
    }

    /**
     * THE SEAM: hand the host's own exception handler a reportable callback
     * so an unhandled exception really does reach Telegram, with no host
     * code and no `Notify::report()` by hand.
     *
     * `Illuminate\Contracts\Debug\ExceptionHandler::reportable()` is the
     * framework's own extension point (the same one Sentry/Bugsnag use) and
     * the right one here: it keeps the host's render path, its own log line
     * and its `dontReport` list completely untouched, and this package never
     * becomes the host's error pipeline — it is a guest on it.
     *
     * Two details that are load-bearing:
     *   - the callback returns VOID. A reportable callback that returns
     *     false tells Laravel the exception is handled and SKIPS the host's
     *     own logging, so this package must never return false.
     *   - registration is total: a bare install with no exception handler,
     *     a host whose handler has no reportable(), or any failure here is
     *     swallowed, because a package that breaks boot over an opt-in
     *     notification feature is worse than one that does not report.
     */
    private function reportHostExceptions(): void
    {
        try {
            if (! $this->app->bound(ExceptionHandler::class)) {
                return;
            }

            $reporter = $this->app->make(ExceptionReporter::class);

            if (! $reporter->enabled()) {
                return;
            }

            $handler = $this->app->make(ExceptionHandler::class);

            if (! method_exists($handler, 'reportable')) {
                return;
            }

            $handler->reportable(static function (Throwable $e) use ($reporter): void {
                $reporter->report($e);
            });
        } catch (Throwable) {
            // An opt-in notification must never be the reason an app fails
            // to boot. Silent by design: the feature is off by default, and
            // a host that turns it on and cannot be wired finds out from
            // the missing notifications, not from a fatal.
        }
    }

    /**
     * THE TRANSPORT SWITCH — `teleframe.notify.transport`.
     *
     * It used to be that `UserScopeTransport` existed as a first-class
     * MTProto user sender with NO config key, NO import and NO binding
     * anywhere in the package: `grep -rn UserScopeTransport src/` returned
     * exactly one hit, its own `class` line. A class, a contract and a test
     * against a fake, and a capability no shipped install could reach.
     *
     *   'bot'        (DEFAULT) — `BotApiTransport` over the container's
     *                 `BotClient`. Byte-identical to the posture before this
     *                 binding existed: the sender already built exactly this
     *                 transport from its own `$bot` argument.
     *   'user_scope' — `UserScopeTransport` over an MTProto USER account,
     *                 with BOTH MTProto-forced seams supplied by real
     *                 shipped implementors instead of null:
     *                   - peerResolver → `DialogPeerResolver`, which reads
     *                     the account's own `messages.getDialogs` for the
     *                     `access_hash` a bare topic-row chat id cannot
     *                     carry;
     *                   - uploader     → `MtProtoDocumentUploader`, the
     *                     `upload.saveFilePart` loop, because the long half
     *                     of an alert is a rendered HTML attachment and a
     *                     caption alone is a silent loss.
     *                 Either is replaceable verbatim from the container, and
     *                 a scope the host cannot produce leaves the transport
     *                 UNCONFIGURED so the FIRST SEND THROWS. There is no
     *                 configuration of this key that produces a no-op.
     *
     * An unknown driver is a `RuntimeException` at resolve time, the same
     * posture the queue driver takes: a typo must not read as a fallback.
     */
    private function registerTransport(Repository $config): void
    {
        $this->app->singleton(NotificationTransportContract::class, function ($app) use ($config): NotificationTransportContract {
            $driver = strtolower(trim((string) ($config->get('teleframe.notify.transport') ?? 'bot')));

            if ($driver === '' || $driver === 'bot') {
                return new BotApiTransport($app->make(BotClient::class));
            }

            if ($driver !== 'user_scope') {
                throw new RuntimeException("unknown notify transport \"{$driver}\" — expected bot|user_scope.");
            }

            $logger = $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null;
            $scopeResolver = $this->userScopeResolver($app);

            // No scope can be produced → build the transport UNCONFIGURED.
            // Its first send throws "no MTProto user scope bound" naming the
            // fix, instead of the notifier pretending a user account is
            // there. Every other seam is withheld for the same reason: a
            // half-wired user sender that resolves a peer against no account
            // is a path that cannot fail loudly at the right moment.
            if ($scopeResolver === null) {
                return new UserScopeTransport(logger: $logger);
            }

            $scope = new LazyUserScope($scopeResolver, $logger);

            return new UserScopeTransport(
                scopeResolver: static fn (): object => $scope->scope(),
                peerResolver: $app->bound(self::USER_PEER_RESOLVER_KEY)
                    ? $app->make(self::USER_PEER_RESOLVER_KEY)
                    : new DialogPeerResolver(
                        $scope,
                        dialogLimit: (int) ($config->get('teleframe.notify.user_scope.dialog_limit') ?? 200),
                        logger: $logger,
                    ),
                uploader: $app->bound(self::USER_UPLOADER_KEY)
                    ? $app->make(self::USER_UPLOADER_KEY)
                    : new MtProtoDocumentUploader($scope, logger: $logger),
                logger: $logger,
            );
        });
    }

    /**
     * The scope the user-scope transport sends through, chosen WITHOUT
     * resolving anything: a host-bound seam wins verbatim, else the host's
     * own `TeleframeClient` if the core provider registered one, else null
     * (unconfigured, and the first send says so). The `$app->make()` calls
     * live INSIDE the returned closure, which is what keeps "never dial at
     * register" true for a scope that is session-backed MTProto.
     *
     * @return (callable(): object)|null
     */
    private function userScopeResolver(mixed $app): ?callable
    {
        if ($app->bound(self::USER_SCOPE_KEY)) {
            $seam = $app->make(self::USER_SCOPE_KEY);

            if (! is_callable($seam)) {
                throw new RuntimeException(
                    self::USER_SCOPE_KEY.' must bind a callable(): object returning an MTProto user scope.'
                );
            }

            return $seam;
        }

        if ($app->bound(TeleframeClient::class)) {
            return static fn (): object => $app->make(TeleframeClient::class)->user();
        }

        return null;
    }

    private function registerQueue(Repository $config): void
    {
        $this->app->bind(NotifyCommand::QUEUE_KEY, function ($app) use ($config): NotificationQueueContract {
            $configured = strtolower(trim((string) ($config->get('teleframe.notify.queue') ?? 'auto')));
            $resolved = $this->resolveDriver($app, $config, $configured);

            // A value, not a driver: bound on the same resolve that built the
            // driver, so the report always states what the container used.
            $this->app->instance(self::QUEUE_REPORT_KEY, $resolved['report']);

            return $resolved['driver'];
        });
    }

    /**
     * Driver selection — the fix. The shipped default (`auto`) is the ASYNC
     * posture, not inline. `auto` takes the best async transport the host
     * actually has and never silently degrades:
     *
     *   1. the host's own queue connection is async (anything but `sync`, on
     *      teleframe.notify.queue_connection or falling back to the host's
     *      `queue.default`) → LaravelQueueNotificationQueue.
     *      illuminate/queue ships with laravel/framework and its `database`
     *      driver needs a TABLE, not a broker, so a stock install that set
     *      QUEUE_CONNECTION=database (what the Laravel installer writes) is
     *      queue-shaped with zero new infrastructure.
     *   2. ext-amqp loaded AND the operator named a broker (TELEFRAME_NOTIFY_AMQP_HOST
     *      set) → AmqpNotificationQueue (durable tframe.notify + tframe.notify.dl).
     *      Both halves matter: a machine that merely HAS ext-amqp must not
     *      start publishing to a broker nobody runs.
     *   3. nothing async configured → teleframe.notify.queue_fallback:
     *      'sync' (default) keeps notifications working inline, ONCE, with a
     *      loud warning; 'throw' is the strict opt-in for a host that must not
     *      block. Never a silent inline block.
     *
     * `laravel` and `amqp` force a driver (loud when unusable); `sync` remains
     * the explicit, documented opt-in inline path.
     *
     * @return array{driver: NotificationQueueContract, report: array<string, mixed>}
     */
    private function resolveDriver(mixed $app, Repository $config, string $configured): array
    {
        if ($configured === '' || $configured === 'auto') {
            // An EXPLICITLY configured broker host outranks the host queue.
            //
            // This used to try the Laravel queue first, and a stock Laravel
            // install writes QUEUE_CONNECTION=database, so the Laravel branch
            // essentially always won: an operator who installed RabbitMQ, set
            // TELEFRAME_NOTIFY_AMQP_HOST, and left queue=auto got the database
            // queue and never learned the broker was ignored. The intent is
            // legible from the config — a host that names a broker wants the
            // broker — so the named broker is tried first now.
            if ($this->amqpUsable($config)) {
                return $this->amqp($app, $config, 'auto', 'auto selected ext-amqp (extension loaded, a broker host is configured)');
            }

            $laravel = $this->laravelDriver($app, $config);
            if ($laravel['driver'] instanceof NotificationQueueContract) {
                return [
                    'driver' => $laravel['driver'],
                    'report' => $this->report('auto', 'laravel', $laravel['driver'], $laravel['connection'], $laravel['reason']),
                ];
            }

            return $this->fallback($app, $config, $laravel['reason']);
        }

        if ($configured === 'laravel') {
            $laravel = $this->laravelDriver($app, $config, required: true);

            /** @var NotificationQueueContract $driver */
            $driver = $laravel['driver'];

            return [
                'driver' => $driver,
                'report' => $this->report('laravel', 'laravel', $driver, $laravel['connection'], $laravel['reason']),
            ];
        }

        if ($configured === 'amqp') {
            return $this->amqp($app, $config, 'amqp', 'amqp requested explicitly');
        }

        if ($configured === 'sync') {
            return [
                'driver' => $this->sync($app),
                'report' => $this->report('sync', 'sync', null, null, 'inline opt-in: teleframe.notify.queue=sync'),
            ];
        }

        throw new RuntimeException("unknown notify queue driver \"{$configured}\" — expected auto|laravel|amqp|sync.");
    }

    /**
     * Inline delivery: enqueue runs the shared handle() path in the caller's
     * process. Still a legitimate choice (CLI probes, ordering-sensitive
     * scripts) — it is simply no longer what a host gets by accident. The lazy
     * container resolve breaks the service↔queue cycle.
     * (Statement body: `fn (): void => $expr` is a PHP fatal — an arrow
     * function always returns its body expression.)
     */
    private function sync(mixed $app): SyncNotificationQueue
    {
        return new SyncNotificationQueue(
            static function (NotificationJob $job) use ($app): void {
                $app->make(NotifyCommand::SERVICE_KEY)->handle($job);
            },
        );
    }

    /**
     * The host's own Laravel queue, when it is async. `required: true` is the
     * `queue=laravel` opt-in: unusable is a loud RuntimeException, never a
     * silent slide back to inline.
     *
     * @return array{driver: ?NotificationQueueContract, connection: ?string, reason: string}
     */
    private function laravelDriver(mixed $app, Repository $config, bool $required = false): array
    {
        $named = trim((string) ($config->get('teleframe.notify.queue_connection') ?? ''));
        $connection = $named !== '' ? $named : (string) ($config->get('queue.default') ?? 'sync');
        $driver = (string) ($config->get("queue.connections.{$connection}.driver") ?? 'sync');
        $queue = (string) ($config->get('teleframe.notify.queue_name') ?? LaravelQueueNotificationQueue::QUEUE);

        if (! $app->bound('queue')) {
            return $this->laravelUnavailable($required, 'the host app has no queue service (illuminate/queue is not registered)');
        }

        if ($driver === '' || $driver === 'sync') {
            return $this->laravelUnavailable(
                $required,
                "queue connection \"{$connection}\" is sync (set QUEUE_CONNECTION=database/redis/sqs, or teleframe.notify.queue_connection)"
            );
        }

        return [
            'driver' => new LaravelQueueNotificationQueue($app->make('queue'), $connection, $queue),
            'connection' => $connection,
            'reason' => "the host queue connection \"{$connection}\" is async ({$driver}), queue \"{$queue}\"",
        ];
    }

    /**
     * @return array{driver: null, connection: null, reason: string}
     */
    private function laravelUnavailable(bool $required, string $reason): array
    {
        if ($required) {
            throw new RuntimeException(
                'teleframe.notify.queue=laravel selected but '.$reason
                .' — point teleframe.notify.queue_connection at an async connection, or use queue=auto.'
            );
        }

        return ['driver' => null, 'connection' => null, 'reason' => $reason];
    }

    /**
     * AMQP over a lazily-built ext-amqp adapter. The adapter is anonymous on
     * purpose: AmqpNotificationQueue is broker-agnostic by design and this
     * package must not grow a hard ext-amqp dependency, so the only place the
     * ext-amqp classes are named is inside a factory that runs on first
     * enqueue/consume — never at register, never at construction.
     *
     * @return array{driver: NotificationQueueContract, report: array<string, mixed>}
     */
    private function amqp(mixed $app, Repository $config, string $configured, string $reason): array
    {
        if (! class_exists('AMQPConnection')) {
            throw new RuntimeException(
                'teleframe.notify.queue=amqp selected but ext-amqp is not loaded — install ext-amqp, '
                .'or use queue=laravel (the host queue) / queue=auto.'
            );
        }

        $options = [
            'host' => (string) ($config->get('teleframe.notify.amqp.host') ?? '127.0.0.1'),
            'port' => (int) ($config->get('teleframe.notify.amqp.port') ?? 5672),
            'vhost' => (string) ($config->get('teleframe.notify.amqp.vhost') ?? '/'),
            'user' => (string) ($config->get('teleframe.notify.amqp.user') ?? ''),
            'pass' => (string) ($config->get('teleframe.notify.amqp.password') ?? ''),
            'heartbeat' => (int) ($config->get('teleframe.notify.amqp.heartbeat') ?? 30),
            'connect_timeout' => (float) ($config->get('teleframe.notify.amqp.connect_timeout') ?? 3.0),
            'read_timeout' => (float) ($config->get('teleframe.notify.amqp.read_timeout') ?? 5.0),
            'write_timeout' => (float) ($config->get('teleframe.notify.amqp.write_timeout') ?? 5.0),
        ];

        // The store upload queue already refused the stock guest/guest pair
        // against a remote broker; the notify queue did not, so a host that
        // deployed notify to a remote broker shipped default credentials
        // without being told. This queue carries message briefs, context and
        // targets. Same guard, same reason.
        AmqpUploadQueue::assertUsableIdentity($options['host'], $options['user'], $options['pass']);

        // One builder, used for the first connection. It previously ran once
        // and its readonly channel/exchange/queue were captured forever, so a
        // broker restart left the process publishing into a dead channel for
        // its entire lifetime with no error — one restart bricked notify until
        // the app itself was restarted. The adapter it returns now re-opens
        // the connection itself.
        $build = function () use ($options): object {
            $connection = new \AMQPConnection($options);
            $connection->connect();

            $channel = new \AMQPChannel($connection);
            $exchange = new \AMQPExchange($channel);
            $exchange->setName(AmqpNotificationQueue::EXCHANGE);
            $exchange->setType(AMQP_EX_TYPE_TOPIC);
            $exchange->declareExchange();

            $queue = new \AMQPQueue($channel);
            $queue->setName(AmqpNotificationQueue::QUEUE);
            $queue->setFlags(AMQP_DURABLE);
            $queue->declareQueue();
            $queue->bind(AmqpNotificationQueue::EXCHANGE, AmqpNotificationQueue::ROUTING_KEY);

            // The dead queue is bound to the SAME exchange under its own
            // routing key. AMQP has no "publish straight to this queue" path:
            // every publish is an exchange publish, and the broker drops a
            // message whose routing key matches no bound queue. An unbound
            // dead queue therefore loses every dead-lettered body.
            $dead = new \AMQPQueue($channel);
            $dead->setName(AmqpNotificationQueue::DEAD_LETTER);
            $dead->setFlags(AMQP_DURABLE);
            $dead->declareQueue();
            $dead->bind(AmqpNotificationQueue::EXCHANGE, AmqpNotificationQueue::DEAD_LETTER);

            return new class($connection, $queue, $exchange)
            {
                private \AMQPQueue $liveQueue;

                private \AMQPExchange $liveExchange;

                public function __construct(
                    private readonly \AMQPConnection $connection,
                    \AMQPQueue $queue,
                    \AMQPExchange $exchange,
                ) {
                    $this->liveQueue = $queue;
                    $this->liveExchange = $exchange;
                }

                /**
                 * Re-open on a dead connection.
                 *
                 * ext-amqp keeps the connection object across a broker restart;
                 * only the channel and everything derived from it (exchange,
                 * queue, bindings) has to be rebuilt. This is the half the store
                 * upload queue already had (AmqpUploadQueue::channel() checks
                 * isConnected()) and the notify side did not — which is why a
                 * single broker restart used to brick notify for the life of
                 * the process.
                 */
                private function channelBound(): void
                {
                    if ($this->connection->isConnected()) {
                        return;
                    }
                    $this->connection->connect();

                    $channel = new \AMQPChannel($this->connection);

                    $exchange = new \AMQPExchange($channel);
                    $exchange->setName(AmqpNotificationQueue::EXCHANGE);
                    $exchange->setType(AMQP_EX_TYPE_TOPIC);
                    $exchange->declareExchange();

                    $queue = new \AMQPQueue($channel);
                    $queue->setName(AmqpNotificationQueue::QUEUE);
                    $queue->setFlags(AMQP_DURABLE);
                    $queue->declareQueue();
                    $queue->bind(AmqpNotificationQueue::EXCHANGE, AmqpNotificationQueue::ROUTING_KEY);

                    $dead = new \AMQPQueue($channel);
                    $dead->setName(AmqpNotificationQueue::DEAD_LETTER);
                    $dead->setFlags(AMQP_DURABLE);
                    $dead->declareQueue();
                    $dead->bind(AmqpNotificationQueue::EXCHANGE, AmqpNotificationQueue::DEAD_LETTER);

                    $this->liveExchange = $exchange;
                    $this->liveQueue = $queue;
                }

                public function publish(string $message): void
                {
                    $this->channelBound();
                    // Durable queue + persistent delivery (delivery_mode 2),
                    // the AmqpUploadQueue wire convention.
                    //
                    // Publishing is AMQPExchange::publish(string, routingKey,
                    // flags, headers). AMQPQueue has NO publish() method at
                    // all — it only declares/binds/gets/consumes — so the
                    // exchange does the publish and the queue is what the
                    // routing key lands on. AMQPMessage is a php-amqplib
                    // class; ext-amqp takes the body as a STRING plus a
                    // headers array, and php-amqplib is not a dependency of
                    // this package, so no message object is ever built here.
                    $this->liveExchange->publish($message, AmqpNotificationQueue::ROUTING_KEY, \AMQP_NOPARAM, [
                        'content_type' => 'application/json',
                        'delivery_mode' => 2,
                    ]);
                }

                public function publishDead(string $message): void
                {
                    $this->channelBound();
                    // Dead-letter the raw body: same exchange, the dead
                    // queue's own routing key.
                    $this->liveExchange->publish($message, AmqpNotificationQueue::DEAD_LETTER, \AMQP_NOPARAM, [
                        'content_type' => 'application/json',
                        'delivery_mode' => 2,
                    ]);
                }

                public function get(): ?array
                {
                    $this->channelBound();
                    $envelope = $this->liveQueue->get();

                    // ext-amqp AMQPQueue::get() returns ?AMQPEnvelope (null on
                    // an empty queue) — the php-amqplib `false` sentinel does
                    // not exist on this transport.
                    if ($envelope === null) {
                        return null;
                    }

                    return ['body' => $envelope->getBody(), 'delivery_tag' => $envelope->getDeliveryTag()];
                }

                public function ack(int|string $deliveryTag): void
                {
                    // Manual ack AFTER the handler ran (finally-ack in the
                    // driver): the broker must not redeliver the same envelope.
                    $this->liveQueue->ack((int) $deliveryTag);
                }
            };
        };

        $driver = new AmqpNotificationQueue($build);

        return [
            'driver' => $driver,
            'report' => $this->report($configured, 'amqp', $driver, null, $reason),
        ];
    }

    /**
     * No async transport anywhere: the queue_fallback decision. 'sync' keeps
     * notifications WORKING and says so once, loudly — an inline logger with
     * no warning is the defect this change exists to remove. 'throw' is the
     * strict opt-in for a host that must never block a request.
     *
     * @return array{driver: NotificationQueueContract, report: array<string, mixed>}
     */
    private function fallback(mixed $app, Repository $config, string $why): array
    {
        $fallback = strtolower(trim((string) ($config->get('teleframe.notify.queue_fallback') ?? 'sync')));

        if ($fallback === 'throw') {
            throw new RuntimeException(
                'teleframe.notify.queue=auto found no async transport ('.$why
                .') and teleframe.notify.queue_fallback=throw forbids the inline fallback — '
                .'configure a queue (QUEUE_CONNECTION=database) or set the fallback to sync.'
            );
        }

        if ($fallback !== 'sync') {
            throw new RuntimeException("unknown notify queue fallback \"{$fallback}\" — expected sync|throw.");
        }

        $this->warnOnce(
            $app,
            'teleframe notify: no async queue transport found, so notifications are being sent INLINE in the caller\'s process '
            ."({$why}). Every log line now costs a blocking Telegram round-trip on the host's request. "
            .'Fix: set QUEUE_CONNECTION=database (a table, not a broker) or point teleframe.notify.queue_connection at an async '
            .'connection; teleframe.notify.queue_fallback=throw turns this into a hard error instead.'
        );

        return [
            'driver' => $this->sync($app),
            'report' => $this->report('auto', 'sync', null, null, 'no async transport ('.$why.') — inline fallback, warned once'),
        ];
    }

    /**
     * ext-amqp BOTH loaded and aimed: an extension that merely exists on the
     * box is not a broker somebody runs. Naming a host is the operator saying
     * "this one is mine" (the shipped 127.0.0.1/guest is a local-dev default
     * and does not count as naming).
     */
    private function amqpUsable(Repository $config): bool
    {
        if (! class_exists('AMQPConnection') || ! class_exists('AMQPChannel') || ! class_exists('AMQPQueue')) {
            return false;
        }

        $host = trim((string) ($config->get('teleframe.notify.amqp.host') ?? ''));

        return $host !== '' && $host !== '127.0.0.1' && $host !== 'localhost';
    }

    /**
     * The discovery surface: what was asked for, what was built, whether it is
     * async, and why. `teleframe:notify work` prints the driver; a doctor
     * command can read self::QUEUE_REPORT_KEY and print this array verbatim.
     *
     * @return array<string, mixed>
     */
    private function report(string $configured, string $resolved, ?NotificationQueueContract $driver, ?string $connection, string $reason): array
    {
        return [
            'configured' => $configured,
            'resolved' => $resolved,
            'async' => $driver?->isAsync() ?? false,
            'reason' => $reason,
            'connection' => $connection,
            'queue' => $resolved === 'laravel' ? LaravelQueueNotificationQueue::QUEUE : null,
            'dead_letter' => $driver?->deadLetterName() ?? SyncNotificationQueue::DEAD_LETTER,
        ];
    }

    /**
     * One warning per process, through the host's PSR-3 logger when there is
     * one (a bare install without a logger binding still gets its driver — the
     * warning is best-effort, the behaviour is not).
     */
    private function warnOnce(mixed $app, string $message): void
    {
        if ($this->warned) {
            return;
        }
        $this->warned = true;

        $logger = $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : new NullLogger;
        $logger->warning($message);
    }
}
