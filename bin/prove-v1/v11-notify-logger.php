<?php

declare(strict_types=1);

/**
 * V1.1 — THE NOTIFY/LOGGER SYSTEM, PROVEN FROM INSIDE A REAL HOST INSTALL.
 *
 * THE OPERATOR'S WORDS
 * (.brain/verbatim/2026-09-24-notification-logger-backup-and-bot-framework.md):
 *
 *   "first the notification system its more like logger system but no one uses telegram for high rate of
 *   notifications it can happen inside a channel using a bot or inside bot session or inside a supergroup
 *   managing by the bot so it can use different topics as different predefined log levels so things are more
 *   managed and it's just like using a queue to queue sending that message into that channel with the way it
 *   should get there but its constraints must have been managed since telegram has limits for message length
 *   and files so the log or whatever it is in shape separates into two sections one is long section that will be
 *   files and one is the message context and the metadata and a brief start of things gets into context the rest
 *   will be injected into files and those files should contain way that lets browser renders them like if it is
 *   laravel exception the exception should be rendered and the final html gets there and so on"
 *
 *   "you must do an enhanced check on what one can send or cannot also if they have premium or not since if they
 *   have premium the limit will be lost"
 *
 *   "having the facade for calling a special kind of work is the predefined of them since all of these or other
 *   actions needs some calculations that are the same whether you want to send a message as bot service or as
 *   notifier the process of sending won't change"
 *
 * WHAT THIS SECTION PROVES, AND WHERE EACH CLAIM LANDS
 *
 *   claim 1 — "different topics as different predefined log levels"
 *     -> level-to-topic-to-thread: all 8 PSR-3 levels resolve through the HOST's
 *        own config rows to 8 DISTINCT topic names, 8 DISTINCT message_thread_ids
 *        and the host's default chat. Asserted by value, from the registry the
 *        container built, not from a constant written here.
 *
 *   claim 2 — "separates into two sections … the rest will be injected into files"
 *     -> long-payload-splits-into-message-and-file: a 6.7 KB brief is refused by
 *        the message-length row, so the send becomes sendDocument — the message
 *        keeps the opening (badge/topic/head) under the caption cap, the file
 *        carries the whole remainder INCLUDING a marker planted past the cap.
 *        Both halves are read back off the wire; neither is asserted by method
 *        name alone.
 *
 *   claim 3 — "enhanced check on … premium or not … the limit will be lost"
 *     -> premium-changes-the-effective-limit: the same payload is REFUSED without
 *        premium and ALLOWED with it, on the shipped mtproto file row and on a
 *        host-merged caption row, with both effective values read off the table.
 *     -> live-premium-probe-upgrades-the-flag: the shipped PremiumProbe really
 *        reads a user's premium flag out of a users.getFullUser answer, and a
 *        failing probe degrades to `unknown` instead of throwing.
 *     -> premium-lifts-the-enforced-per-chat-rate: the behavioural half, at a
 *        pinned clock — the second send in one second is REFUSED on the 1 msg/s
 *        row and ALLOWED when a live probe upgrades the account to premium (4.5x).
 *
 *   claim 4 — "it's just like using a queue to queue sending that message"
 *     -> logger-half-reaches-the-wire: the bound NotificationLogger and the bound
 *        Psr3NotifyBridge each enqueue, and the queue carries the job to the
 *        transport with the RIGHT chat and thread_id, on the caller's thread.
 *
 *   claim 5 — "like if it is laravel exception … the final html gets there"
 *     -> laravel-exception-renders-to-browser-html: a real Throwable handed to the
 *        shipped ExceptionReporter (the object the provider registers on the host's
 *        own handler) lands as a text/html file whose bytes are a complete,
 *        self-contained, escaped HTML5 document.
 *
 * HOW IT ENTERS THE BODY INSTEAD OF STOPPING AT THE FIRST REFUSAL
 *
 * The `Notify` section of this same gate stops at the refusal, because the host
 * ships unconfigured: "no target chat". Every check below configures the host the
 * way an operator configures it (default_chat + topic rows), binds the SHIPPED
 * default transport over the package's own offline BotClient double
 * (tests/Notify/FakeBotClient.php), and drives a real send through the real
 * pipeline. Nothing dials: the double answers every call, and BotApiTransport's
 * multipart seam is the shipped `FakeBotClient::recordMultipart()` recording.
 */

use MeRezaRezaei\Teleframe\Laravel\Facades\Notify as NotifyFacade;
use MeRezaRezaei\Teleframe\Laravel\Notify\ExceptionReporter;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Notify\BotApiTransport;
use MeRezaRezaei\Teleframe\Notify\CapabilityCheck;
use MeRezaRezaei\Teleframe\Notify\CapabilityTable;
use MeRezaRezaei\Teleframe\Notify\FacadeSupport;
use MeRezaRezaei\Teleframe\Notify\HtmlAttachment;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\MessageSegmenter;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\NotificationLogger;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotificationTransportContract;
use MeRezaRezaei\Teleframe\Notify\NotifyException;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use MeRezaRezaei\Teleframe\Notify\PremiumProbe;
use MeRezaRezaei\Teleframe\Notify\PremiumStatus;
use MeRezaRezaei\Teleframe\Notify\Psr3NotifyBridge;
use MeRezaRezaei\Teleframe\Notify\SyncNotificationQueue;
use MeRezaRezaei\Teleframe\Notify\Topic;
use MeRezaRezaei\Teleframe\Tests\Notify\FakeBotClient;
use Psr\Log\NullLogger;

// ---------------------------------------------------------------------------
// THE FIXTURE. Built lazily on the FIRST check and memoized, so a setup failure
// surfaces as a per-check "threw instead of answering: <the real reason>" line
// rather than a fatal that takes the whole gate with it.
// ---------------------------------------------------------------------------
$v11 = static function () use ($root): array {
    /** @var array<string, mixed>|null $world */
    static $world = null;

    if ($world !== null) {
        return $world;
    }

    $package = $root.'/vendor/merezarezaei/teleframe';

    // The package's OWN offline BotClient double. It lives under tests/, which IS
    // published, but `autoload-dev` is not installed in a host — so the file is
    // required by path, exactly as this gate's V1.5 probe requires its fixtures.
    require_once $package.'/tests/Notify/FakeBotClient.php';

    // A forum supergroup id and its per-level thread ids. RFC-shaped, never
    // dialled: the transport is the double above.
    $chat = '-1007770001234';
    $threads = [
        'app.debug' => 101,
        'app.info' => 102,
        'app.notice' => 103,
        'app.warning' => 104,
        'app.error' => 105,
        'app.critical' => 106,
        'app.alert' => 107,
        'app.emergency' => 108,
        'report' => 109,
        'backup' => 110,
        'backup.missing' => 111,
    ];

    $attachmentDir = rtrim(sys_get_temp_dir(), '/').'/teleframe-v11-attachments';

    // ---- WHAT A HOST WRITES IN config/teleframe/notify.php ------------------
    // Every row below is a config row the shipped NotifyServiceProvider merges
    // over Topic::defaults(); the thread ids deliberately REPLACE the shipped
    // 1..8 defaults, so a check that passes cannot be reading those defaults.
    $topicRows = [];
    foreach ($threads as $name => $threadId) {
        $topicRows[] = [
            'name' => $name,
            'level' => Level::fromPsr3($name === 'report' ? 'notice' : ($name === 'backup' ? 'info' : ($name === 'backup.missing' ? 'alert' : substr($name, 4))))->value,
            'description' => 'v11 host-probe row',
            'thread_id' => $threadId,
            'throttle_per_hour' => 600,
        ];
    }

    config([
        'teleframe.notify.default_chat' => $chat,
        // 'sync' is the package's own documented inline opt-in. On a stock host
        // QUEUE_CONNECTION=database, so 'auto' would hand the job to a worker and
        // this process would never see the wire — and the point of the section is
        // to watch the wire.
        'teleframe.notify.queue' => 'sync',
        'teleframe.notify.attachment_dir' => $attachmentDir,
        'teleframe.notify.exceptions.enabled' => true,
        'teleframe.notify.topics' => $topicRows,
        'teleframe.notify.capabilities' => [
            ['feature' => 'caption', 'base' => 1024, 'unit' => 'chars', 'premiumMultiplier' => 2.0],
        ],
    ]);

    // ---- THE RECORDING TRANSPORT, ON THE SHIPPED SEAM ----------------------
    // BotApiTransport is the package's OWN default transport (driver 'bot'), and
    // FakeBotClient is its own offline terminal. The only thing supplied here is
    // the multipart poster BotApiTransport accepts as its second constructor
    // argument — it records the part the way the wire carries it (an
    // `attach://<name>` reference plus the REAL bytes), through the double's own
    // `recordMultipart()` seam. Without it, an attachment would leave as a real
    // HTTP POST to api.telegram.org.
    $bot = new FakeBotClient;
    $shipped = new ArrayObject;
    $transport = new BotApiTransport(
        $bot,
        static function (string $method, array $fields, array $file) use ($bot, $shipped, $attachmentDir): array {
            $shipped->append([
                'method' => $method,
                'fields' => $fields,
                'name' => $file['name'],
                'mime' => $file['mime'],
                'bytes' => $file['bytes'],
                'on_disk' => is_file($attachmentDir.'/'.$file['name']),
            ]);
            $bot->recordMultipart($method, $fields, $file);

            return ['ok' => true, 'result' => ['message_id' => 4242]];
        },
    );
    app()->instance(NotificationTransportContract::class, $transport);

    // The NotifyService singleton was ALREADY resolved by the `Notify` section of
    // this same probe (it has to resolve for the unconfigured refusal). Drop it,
    // and the logger singletons beside it, so the service is rebuilt from the
    // configuration written above — the only moment config can still reach it.
    app()->forgetInstance(FacadeSupport::SERVICE_KEY);
    app()->forgetInstance(NotificationLogger::class);
    app()->forgetInstance(Psr3NotifyBridge::class);
    app()->forgetInstance(ExceptionReporter::class);
    NotifyFacade::clearResolvedInstance(FacadeSupport::SERVICE_KEY);

    $service = app(FacadeSupport::SERVICE_KEY);

    // A REAL NotifyService, assembled exactly as NotifyServiceProvider assembles
    // it (registry + merged capability table + segmenter + sync queue + the
    // container's sender), with the two things a host cannot configure pinned so
    // the rate rows are deterministic: a fixed clock and ENFORCED budgets. Used
    // only by the premium-rate check.
    $rateService = static function (?bool $probePremium) use ($chat, $attachmentDir): NotifyService {
        $registry = Topic::defaults()->define('app.error', Level::Error, 'v11 rate-probe row', $chat, 105, 600);

        $service = null;
        $service = new NotifyService(
            registry: $registry,
            checker: new CapabilityCheck((new CapabilityTable)->merge((array) config('teleframe.notify.capabilities'))),
            segmenter: new MessageSegmenter(new HtmlAttachment, $attachmentDir),
            queue: new SyncNotificationQueue(static function (NotificationJob $job) use (&$service): void {
                $service?->handle($job);
            }),
            sender: app()->make(NotificationSender::class),
            renderer: new HtmlAttachment,
            inlineBudget: 800,
            attachmentDir: $attachmentDir,
            premium: false,
            now: static fn (): int => 1758000000,
            logger: new NullLogger,
            premiumProbe: $probePremium === null ? null : new PremiumProbe(
                static fn (string $method, array $params): array => [
                    'users' => [['id' => 4242, 'premium' => $probePremium]],
                ],
                null,
            ),
            premiumUserId: $probePremium === null ? null : 4242,
            budgets: null,
            enforceBudgets: true,
        );

        return $service;
    };

    $world = [
        'chat' => $chat,
        'threads' => $threads,
        'bot' => $bot,
        'shipped' => $shipped,
        'service' => $service,
        'registry' => $service->registry(),
        'attachment_dir' => $attachmentDir,
        'table' => (new CapabilityTable)->merge((array) config('teleframe.notify.capabilities')),
        'rate_service' => $rateService,
    ];

    return $world;
};

/** The sendDocument call recorded for $fileName, or null. */
$v11Shipped = static function (array $world, string $fileName): ?array {
    $found = null;
    foreach ($world['shipped'] as $entry) {
        if (($entry['name'] ?? '') === $fileName) {
            $found = $entry;
        }
    }

    return $found;
};

/** The last recorded transport call for $method, or null. */
$v11LastCall = static function (array $world, string $method): ?array {
    $found = null;
    foreach ($world['bot']->calls as $call) {
        if (($call['method'] ?? '') === $method) {
            $found = $call;
        }
    }

    return $found;
};

// ---------------------------------------------------------------------------
// THE CHECKS
// ---------------------------------------------------------------------------

return [
    'V1.1 notify-logger' => [

        // claim 1 — a level becomes a topic, the topic becomes a real thread id.
        'level-to-topic-to-thread' => mustBeTrue(static function () use ($v11): bool {
            $world = $v11();

            $logger = app(FacadeSupport::LEVEL_LOGGER_KEY);
            $rows = $world['service']->registry()->topics();

            $seenThreads = [];

            foreach (Level::cases() as $level) {
                $topic = $logger->topicFor($level);
                $row = $rows[$topic] ?? null;

                // The level routes to its own topic name…
                if ($topic !== $level->defaultTopic() || $row === null) {
                    return false;
                }

                // …the topic row is the level's own row…
                if ($row->level() !== $level) {
                    return false;
                }

                // …it carries the HOST's chat (the row carried none itself)…
                if ((string) $row->chat() !== $world['chat']) {
                    return false;
                }

                // …and the HOST's forum thread id, which replaced the shipped 1..8.
                if ((int) $row->threadId() !== $world['threads'][$topic]) {
                    return false;
                }

                $seenThreads[] = (int) $row->threadId();
            }

            // Eight levels, eight topics, EIGHT DISTINCT threads: "it can use
            // different topics as different predefined log levels", asserted by
            // value rather than by the existence of a method.
            return count($seenThreads) === 8 && count(array_unique($seenThreads)) === 8;
        }, 'the 8 PSR-3 levels each resolve, through the host\'s own topic config, to their own topic row carrying the host\'s chat and 8 distinct forum thread_ids'),

        // claim 4 + the "logger system, not just a notifier" half.
        'logger-half-reaches-the-wire' => mustBeTrue(static function () use ($v11): bool {
            $world = $v11();

            $before = count($world['bot']->calls);

            // The typed Level facade the provider binds on teleclient.notify.level_logger.
            $jobId = app(FacadeSupport::LEVEL_LOGGER_KEY)->error(
                'backup run finished',
                ['set' => 'default', 'bytes' => 48127],
            );

            // The PSR-3 half: an ordinary log record becomes a notification on its
            // OWN level's topic, which is the "more like logger system" claim.
            app(FacadeSupport::PSR3_KEY)->warning('laravel logged a warning', ['component' => 'probe']);

            $calls = $world['bot']->calls;
            $count = count($calls) - $before;

            if ($count !== 2) {
                return false; // a job id back with no wire call is a silent drop
            }

            if (! is_string($jobId) || ! str_starts_with($jobId, 'tf-')) {
                return false; // the queue must answer with a real job id
            }

            $error = $calls[$before] ?? null;
            $warn = $calls[$before + 1] ?? null;

            if (! is_array($error) || ! is_array($warn)) {
                return false;
            }

            if (($error['method'] ?? '') !== 'sendMessage' || ($warn['method'] ?? '') !== 'sendMessage') {
                return false;
            }

            $errorParams = $error['params'] ?? [];
            $warnParams = $warn['params'] ?? [];

            // The chat came from the host's default_chat; the thread came from
            // the host's topic row — the level decided the row.
            if ((string) ($errorParams['chat_id'] ?? '') !== $world['chat']) {
                return false;
            }
            if ((int) ($errorParams['message_thread_id'] ?? 0) !== 105) {
                return false;
            }
            if ((int) ($warnParams['message_thread_id'] ?? 0) !== 104) {
                return false;
            }

            $errorText = (string) ($errorParams['text'] ?? '');
            $warnText = (string) ($warnParams['text'] ?? '');

            // The composed message carries the level badge, the topic it landed
            // on, the brief and the scalar context pairs.
            if (! str_contains($errorText, '[ERROR] app.error') || ! str_contains($errorText, 'backup run finished')) {
                return false;
            }
            if (! str_contains($errorText, 'set=default') || ! str_contains($errorText, 'bytes=48127')) {
                return false;
            }

            if (! str_contains($warnText, '[WARNING] app.warning') || ! str_contains($warnText, 'component=probe')) {
                return false;
            }

            // …and a queue really carried it: the resolved driver is reported by
            // the provider on its own discovery seam.
            $report = app(NotifyServiceProvider::QUEUE_REPORT_KEY);

            return is_array($report)
                && ($report['resolved'] ?? '') === 'sync'
                && ($report['async'] ?? true) === false;
        }, 'the container-bound NotificationLogger and Psr3NotifyBridge each enqueue a job id and the resolved queue carries it to the transport with the host chat and the thread_id of the level\'s own topic row'),

        // claim 2 — the two-section split: a short opening + metadata on the wire
        // message, the long remainder into a file. Both halves read back.
        'long-payload-splits-into-message-and-file' => mustBeTrue(static function () use ($v11, $v11Shipped, $v11LastCall): bool {
            $world = $v11();

            // 6.7 KB: over the message-length row (4096), and a marker planted at
            // the very END so "the rest went into the file" is decidable.
            $tail = 'THE-TAIL-MARKER-9f3a';
            $long = 'V11 LONG PAYLOAD start. '.str_repeat('lorem ipsum dolor sit amet ', 250).$tail;

            app(FacadeSupport::LEVEL_LOGGER_KEY)->critical($long, [
                'phase' => 'import',
                'rows' => ['alpha', 'beta'],
            ]);

            $call = $v11LastCall($world, 'sendDocument');
            if ($call === null) {
                return false; // over the message cap and NO file: nothing was split
            }

            $params = $call['params'] ?? [];
            $document = (string) ($params['document'] ?? '');
            $caption = (string) ($params['caption'] ?? '');

            // The shipped transport translated a LOCAL PATH into the multipart
            // reference the Bot API actually accepts.
            if (! str_starts_with($document, 'attach://') || ! str_ends_with($document, '.html')) {
                return false;
            }
            if ((int) ($params['message_thread_id'] ?? 0) !== 106) {
                return false;
            }

            // ---- half one, the message: short opening, UNDER Telegram's cap.
            if (mb_strlen($caption) > 1024) {
                return false;
            }
            if (! str_contains($caption, '[CRITICAL] app.critical')) {
                return false;
            }
            if (str_contains($caption, $tail)) {
                return false; // the tail rode the message: nothing was split off
            }

            // ---- half two, the file: the whole remainder + the context.
            $name = substr($document, strlen('attach://'));
            $shipped = $v11Shipped($world, $name);
            if ($shipped === null) {
                return false;
            }
            if (($shipped['mime'] ?? '') !== 'text/html' || ($shipped['on_disk'] ?? false) !== true) {
                return false;
            }

            $bytes = (string) ($shipped['bytes'] ?? '');

            if (! str_contains($bytes, $tail)) {
                return false; // the long section did NOT reach the file
            }
            if (! str_contains($bytes, 'import') || ! str_contains($bytes, 'rows')) {
                return false; // the context/metadata did not reach the file either
            }
            if (! str_contains($bytes, '<!DOCTYPE html>')) {
                return false;
            }

            return strlen($bytes) > strlen($caption);
        }, 'a 6.7 KB payload over the message-length row becomes a sendDocument: the wire message keeps the opening under the caption cap and the file that went out carries the entire remainder plus the context and metadata'),

        // claim 3a — premium changes the LIMIT, on the shipped row and on a row
        // the host merged. Both values are read off the table, not written here.
        'premium-changes-the-effective-limit' => mustBeTrue(static function () use ($v11): bool {
            $world = $v11();
            $table = $world['table'];
            $checker = new CapabilityCheck($table);

            // The shipped mtproto file row (2 GiB x2 premium). The payload is
            // derived from the free cap, so no number is written here.
            $over = $table->effective('file_upload_mtproto', false) + 1;

            $free = $checker->checkFile($over, PremiumStatus::fromBool(false), 'mtproto');
            $prem = $checker->checkFile($over, PremiumStatus::fromBool(true), 'mtproto');

            if (! $free->denied() || ! $prem->allowed()) {
                return false; // the same payload must flip with the flag
            }
            if ($prem->effective() <= $free->effective()) {
                return false; // "if they have premium the limit will be lost"
            }
            if ($prem->effective() !== $free->effective() * 2) {
                return false; // the shipped row's x2 premium multiplier
            }

            // A row the HOST raised through teleframe.notify.capabilities: the
            // caption row ships 1024 with a x2 premium multiplier, so the same
            // 1.5 KB caption is TRIMMED without premium and ALLOWED with it.
            $captionFree = $checker->checkCaption(str_repeat('c', 1500), PremiumStatus::fromBool(false));
            $captionPrem = $checker->checkCaption(str_repeat('c', 1500), PremiumStatus::fromBool(true));

            if (! $captionFree->truncated() || $captionFree->effective() !== 1024) {
                return false;
            }

            return $captionPrem->allowed() && $captionPrem->effective() === 2048;
        }, 'the same payload is refused without premium and allowed with it: the shipped mtproto file row goes 2 GiB to 4 GiB and the host-merged caption row goes 1024 to 2048, both read off the capability table'),

        // claim 3b — the "enhanced check": the LIVE per-user premium flag.
        'live-premium-probe-upgrades-the-flag' => mustBeTrue(static function (): bool {
            // A Layer 229 users.getFullUser answer: the premium flag rides the
            // user object inside the response's users vector.
            $probe = new PremiumProbe(static fn (string $method, array $params): array => [
                'users' => [['id' => 4242, 'premium' => true, 'first_name' => 'Probe']],
            ]);
            $status = $probe->probe(4242);

            if (! $status->isPremium()) {
                return false;
            }

            // The non-premium answer, on the same code path.
            $plain = new PremiumProbe(static fn (string $method, array $params): array => [
                'users' => [['id' => 4242, 'premium' => false]],
            ]);

            if ($plain->probe(4242)->isPremium()) {
                return false;
            }

            // A BROKEN probe is best-effort by contract: it answers unknown and
            // never throws into a send (unknown is treated as not premium, so a
            // broken probe can never grant a raised cap it cannot prove).
            $broken = new PremiumProbe(static function (string $method, array $params): array {
                throw new RuntimeException('users.getFullUser is unavailable');
            });

            $unknown = $broken->probe(4242);

            // `unknown` is a VALUE here, not an identity: it answers
            // not-premium, which is what keeps a broken probe from granting a
            // raised cap it cannot prove — and it never throws into a send.
            if ($unknown->premium() !== false) {
                return false;
            }

            return true;
        }, 'the shipped PremiumProbe reads a user\'s premium flag out of a real users.getFullUser answer, distinguishes a non-premium answer, and degrades a failing probe to unknown instead of throwing into a send'),

        // claim 3c — the behavioural half, at a pinned clock with budgets
        // ENFORCED: premium is the difference between a refusal and a delivery.
        'premium-lifts-the-enforced-per-chat-rate' => mustBeTrue(static function () use ($v11, $v11LastCall): bool {
            $world = $v11();
            $rateService = $world['rate_service'];

            // ---- without premium: 1 msg/s on the same chat, enforced.
            $plain = $rateService(null);
            $plain->log(Level::Error, 'plain rate probe one');

            $refused = false;
            $why = '';
            try {
                $plain->log(Level::Error, 'plain rate probe two');
            } catch (NotifyException $e) {
                $why = $e->getMessage();
                $refused = str_contains($why, 'per_chat_rate') && str_contains($why, 'the 1 cap');
            }

            if (! $refused) {
                return false; // the cap must actually refuse without premium
            }

            // ---- with a live probe upgrading the same account to premium.
            $before = count($world['bot']->calls);

            $premium = $rateService(true);
            $premium->log(Level::Error, 'premium rate probe one');
            $premium->log(Level::Error, 'premium rate probe two');

            $calls = $world['bot']->calls;
            if (count($calls) - $before !== 2) {
                return false; // the premium pair must both reach the transport
            }

            $last = $calls[array_key_last($calls)] ?? null;
            if (! is_array($last) || (string) ($last['params']['text'] ?? '') === '') {
                return false;
            }
            if (! str_contains((string) $last['params']['text'], 'premium rate probe two')) {
                return false;
            }

            return $v11LastCall($world, 'sendMessage') === $last;
        }, 'with budgets enforced at a pinned clock the second send on one chat is REFUSED by the 1 msg/s per_chat_rate row, while the same two sends with a live premium probe are both allowed and both reach the transport'),

        // claim 5 — a Laravel exception, rendered, and the final HTML gets there.
        'laravel-exception-renders-to-browser-html' => mustBeTrue(static function () use ($v11, $v11Shipped, $v11LastCall): bool {
            $world = $v11();

            // The object the provider registers on the HOST's own exception
            // handler (Illuminate\Contracts\Debug\ExceptionHandler::reportable),
            // re-resolved with teleframe.notify.exceptions.enabled turned on.
            $reporter = app(ExceptionReporter::class);
            if (! $reporter->enabled()) {
                return false;
            }

            $sent = $reporter->report(new RuntimeException(
                'the host probe exception for the notify render check <b>markup</b>',
            ));

            if ($sent !== true) {
                return false; // the reporter swallows failures and answers false
            }

            $call = $v11LastCall($world, 'sendDocument');
            if ($call === null) {
                return false;
            }

            $params = $call['params'] ?? [];
            $document = (string) ($params['document'] ?? '');
            $caption = (string) ($params['caption'] ?? '');

            // The report landed on the host's `report` topic row, thread 109.
            if (! str_starts_with($document, 'attach://') || (int) ($params['message_thread_id'] ?? 0) !== 109) {
                return false;
            }
            if (! str_contains($caption, 'Exception: RuntimeException')) {
                return false;
            }

            $shipped = $v11Shipped($world, substr($document, strlen('attach://')));
            if ($shipped === null || ($shipped['mime'] ?? '') !== 'text/html' || ($shipped['on_disk'] ?? false) !== true) {
                return false;
            }

            $html = (string) ($shipped['bytes'] ?? '');

            // A COMPLETE, SELF-CONTAINED, ESCAPED HTML5 document — the bytes a
            // browser is handed, not a method that could render one.
            if (! str_starts_with($html, '<!DOCTYPE html>') || ! str_ends_with(rtrim($html), '</html>')) {
                return false;
            }
            if (! str_contains($html, '<html lang="en">') || ! str_contains($html, '<meta charset="utf-8">')) {
                return false;
            }
            if (! str_contains($html, '<style>') || ! str_contains($html, '</style>')) {
                return false; // the stylesheet travels with the file
            }

            // The exception itself is in there: class, and the message prose the
            // redactor left alone, escaped.
            if (! str_contains($html, 'RuntimeException')) {
                return false;
            }
            if (! str_contains($html, 'the host probe exception for the notify render check')) {
                return false;
            }
            if (! str_contains($html, '&lt;b&gt;markup&lt;/b&gt;')) {
                return false;
            }
            if (str_contains($html, '<b>markup</b>')) {
                return false; // unescaped host text shipped into the chat surface
            }

            // Self-contained: no stylesheet, script, image or anchor is fetched.
            return ! str_contains($html, 'src="http') && ! str_contains($html, 'href="http');
        }, 'a real Throwable handed to the shipped ExceptionReporter reaches the host\'s report topic as a text/html attachment whose bytes are a complete, self-contained HTML5 document with the exception class and its escaped message and no external reference'),

        // Informational. A gate that reports which seams answered, under their own
        // names, is worth more than one that reports only ticks.
        'host-configuration-used' => 'default_chat=-1007770001234 · queue=sync (the package\'s documented inline opt-in) · 11 topic rows, threads 101..111 replacing the shipped 1..8 · capabilities: caption 1024 x2 premium · attachment_dir='.rtrim(sys_get_temp_dir(), '/').'/teleframe-v11-attachments',

        'transport-and-double' => 'bot-api — the package\'s own default BotApiTransport over tests/Notify/FakeBotClient (the published offline double); its multipart seam is FakeBotClient::recordMultipart(), so not one byte left the process',

        'premium-truth' => 'the shipped table raises file_upload_mtproto (2 GiB x2) and the three rate rows (x4.5) with premium; text_message (4096) and caption (1024) ship a x1.0 multiplier, so a host row is the only thing that lifts them — which is why the caption half of the premium check uses a host-merged row',

        'not-proven-here' => 'no live Telegram send: the host ships unconfigured and nothing here dials. What is proven is that a real level/log call becomes a correctly addressed, correctly split send on the wire of the package\'s own default transport',
    ],
];
