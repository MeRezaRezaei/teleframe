<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;

/**
 * NotifyService (design §2/G9) — the notification-core orchestrator: the
 * one facade surface over topic=level routing → capability check →
 * queue → ONE shared send process (NotifySender). Plain-PHP constructible
 * with injected seams; the Laravel facade is sugar over the container
 * binding under FacadeSupport::SERVICE_KEY.
 *
 * Entry points (the done-triple):
 *   log(Level, string, array, ?topic)  — topic = level's default topic
 *   to(topic, brief, context)          — fluent PendingNotify builder
 *   report(title, sections, context)   — browser-renderable HTML attachment
 * plus the queue consumer `handle(NotificationJob)` shared by the sync
 * driver (inline) and the wave-2 async worker.
 *
 * Pipeline per job: resolve the topic row from the Topic registry (loud
 * on unknown — NotifyException), capability-check the text (premium
 * honored; truncate/refuse, never silently drop), split brief+metadata
 * inline vs long body → HTML file, build the NotificationJob (design §5
 * wire shape), enqueue (the sync driver runs the send inline through
 * handle(), correct ordering). Sends go through NotifySender ONLY —
 * bot-service and notifier are byte-identical past this class (design
 * §0/§7), and the sender's failure posture is log-at-error + ALWAYS
 * rethrow (the queue's retry/dead-letter owns recovery).
 *
 * Runtime cost zero when unconfigured: constructible and callable
 * pre-credentials — sends throw NotifyException on first use when the
 * topic row carries no target chat (TStore posture), never silent-drop.
 */
final class NotifyService
{
    /** Bot API media caption length (data row `caption` in CapabilityTable — §6). */
    public const CAPTION_BUDGET = 1024;

    /** Key namespace for the durable budget counters (opaque, never renamed). */
    private const BUDGET_PREFIX = 'teleframe.notify.budget.';

    /** @var array<string, list<int>> per-process counters, used when no PSR-16 store is injected */
    private array $localBudgets = [];

    /** Upper bound on a stored counter's timestamps (a runaway loop cannot grow the store). */
    private const BUDGET_HISTORY = 240;

    /**
     * @param  Topic  $registry  topic = predefined log level + placement rows (Topic::defaults() + host define())
     * @param  CapabilityCheck  $checker  data-driven send-capability gate (premium honored by the table rows)
     * @param  MessageSegmenter  $segmenter  inline brief+metadata vs long-body-to-file split
     * @param  NotificationQueueContract  $queue  queue seam (SyncNotificationQueue inline default — design §5)
     * @param  NotificationSender  $sender  THE one shared send process (plan → RateLimitPolicy → transport)
     * @param  HtmlAttachment|null  $renderer  HTML attachment renderer; defaults to a fresh HtmlAttachment
     * @param  int|null  $inlineBudget  teleframe.notify.inline_budget (default 800)
     * @param  string|null  $attachmentDir  temp dir seam for rendered attachments (default system temp)
     * @param  bool|null  $premium  default premium flag (config teleframe.notify.premium)
     * @param  \Closure(): int|null  $now  unix clock (default time)
     * @param  LoggerInterface|null  $logger  failures logged at error — ALWAYS rethrown, never swallowed
     * @param  PremiumProbe|null  $premiumProbe  the LIVE per-user premium flag. The owner asked for an
     *                                           "enhanced check on … if they have premium" and the static
     *                                           flag alone only ever honours the table's multiplier;
     *                                           with a probe wired the flag is RE-CONFIRMED per send.
     *                                           Best-effort: an `unknown` answer falls back to the
     *                                           static flag, never to a raised cap it cannot prove.
     * @param  int|null  $premiumUserId  the user id the probe answers for; null = no probe
     * @param  CacheInterface|null  $budgets  durable PSR-16 store for the
     *                                        send-rate / per-chat-rate / per-topic-hourly counters. Null
     *                                        = per-process counters (see guardBudgets()).
     * @param  bool  $enforceBudgets  true = an over-budget send is REFUSED loudly
     *                                (NotifyException, never a silent drop); false = advisory
     *                                (logged at warning, the send still goes out — the
     *                                right posture for a watchdog alert you cannot afford
     *                                to lose).
     */
    public function __construct(
        private readonly Topic $registry,
        private readonly CapabilityCheck $checker,
        private readonly MessageSegmenter $segmenter,
        private readonly NotificationQueueContract $queue,
        private readonly NotificationSender $sender,
        private readonly ?HtmlAttachment $renderer = null,
        private readonly ?int $inlineBudget = null,
        private readonly ?string $attachmentDir = null,
        private readonly ?bool $premium = null,
        private readonly ?\Closure $now = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?PremiumProbe $premiumProbe = null,
        private readonly ?int $premiumUserId = null,
        private readonly ?CacheInterface $budgets = null,
        private readonly bool $enforceBudgets = true,
    ) {}

    /**
     * The effective premium status for a send: the per-send/config flag
     * FIRST (design §6.4 — an explicit flag is a decision), and when it says
     * "not premium" AND a live probe is wired for a known user, the probe is
     * consulted to upgrade it. A probe that fails or cannot read the flag
     * answers `unknown`, which falls back to the flag — a broken probe must
     * never cost a send its premium caps, and never break the send.
     */
    private function premiumStatus(bool $flag): PremiumStatus
    {
        $status = PremiumStatus::fromBool($flag);

        if ($status->isPremium()) {
            return $status; // an explicit premium flag is final
        }

        if ($this->premiumProbe === null || $this->premiumUserId === null) {
            return $status;
        }

        $probed = $this->premiumProbe->probe($this->premiumUserId);

        return $probed->isPremium() ? $probed : $status;
    }

    /**
     * Log a notification: topic defaults to the level's predefined topic
     * (§4 — Level::defaultTopic(), 'app.'.$level). Capability-checked
     * (premium honored), split through the segmenter, enqueued. Returns
     * the queue job id (the sync driver has already sent by the time it
     * returns).
     */
    public function log(Level $level, string $brief, array $context = [], ?string $topic = null): string
    {
        $topic ??= $level->defaultTopic();
        $status = $this->premiumStatus($this->premium ?? false);
        $premium = $status->isPremium();

        $verdict = $this->checker->checkText($brief, $status);
        $body = null;
        if ($verdict->truncated()) {
            // Design §6: the message carries the head (≤ text cap), the
            // FULL brief survives in the file attachment — never lost.
            $body = $brief;
            $brief = $this->truncateTo($brief, $verdict->effective());
        } elseif (! $verdict->allowed()) {
            throw new NotifyException('notify send refused: '.($verdict->reason() ?? 'text exceeds capability'));
        }

        $segment = $this->segmenter->split($level, $brief, $context, $body, $this->inlineBudget());
        $attachment = null;
        if (isset($segment['attachment'])) {
            $attachment = AttachmentFile::fromArray($segment['attachment']);
        }

        return $this->enqueue($topic, $level, $brief, $context, $attachment, $premium);
    }

    /**
     * Build an immutable fluent PendingNotify (design §3): caller sets
     * level/attach/chat/premium and calls send() or queue(). The builder
     * carries THIS service's queue seam, so send() reaches the same
     * pipeline as log()/report().
     */
    public function to(string $topic, string $brief, array $context = []): PendingNotify
    {
        return new PendingNotify(
            topic: $topic,
            brief: $brief,
            context: new NotifyContext(context: $context),
            queue: $this->queue,
            premium: $this->premium ?? false,
        );
    }

    /**
     * Report: lands on the 'report' topic (§4) with a browser-renderable
     * HTML attachment of the sections, caption = title (≤ the text cap).
     * Returns the job id.
     *
     * @param  array<string|int, mixed>  $sections
     */
    public function report(string $title, array $sections, array $context = []): string
    {
        $status = $this->premiumStatus($this->premium ?? false);
        $premium = $status->isPremium();

        $file = AttachmentFile::fromArray(
            $this->renderer()->render($title, $context, $this->renderSections($sections), $this->attachmentDir()),
        );

        // §7: file upload caps (bot 50 MiB) + the message text cap.
        $fileVerdict = $this->checker->checkFile($file->bytes(), $status, 'bot');
        if (! $fileVerdict->allowed()) {
            throw new NotifyException('notify report refused: '.($fileVerdict->reason() ?? 'attachment exceeds capability'));
        }

        $captionVerdict = $this->checker->checkText($title, $status);
        $caption = $captionVerdict->allowed() ? $title : $this->truncateTo($title, $captionVerdict->effective());

        return $this->enqueue('report', Level::Notice, $caption, $context, $file, $premium);
    }

    /**
     * Queue consumer — the shared job→send path. The sync driver runs it
     * inline at enqueue time; a wave-2 async worker drains via
     * consume(fn (NotificationJob $job) => $service->handle($job)).
     * Unknown topics and capability refusals throw NotifyException here —
     * the loud send-time gate (design §4). Failures bubble (logged at
     * error, ALWAYS rethrown): the queue's retry/DL owns recovery, never
     * silent drops (AGENTS.md logger seam, design §5).
     */
    public function handle(NotificationJob $job): void
    {
        $topic = $job->topic;
        $level = Level::fromPsr3($job->level);

        $rows = $this->registry->topics();
        if (! isset($rows[$topic])) {
            throw new NotifyException('unknown topic "'.$topic.'"');
        }
        $def = $rows[$topic];

        // The job's OWN target wins over the topic row. `target` is written by
        // every producer (PendingNotify::to()/chat(), enqueue()) and carried
        // on the wire by both async drivers, but until now NOTHING read it:
        // a per-send `->to($chat, $threadId)` override was silently ignored
        // and the notification landed in the registry row's chat/topic — no
        // exception, no log line. The registry stays the FALLBACK for a job
        // that carries no target of its own.
        $chat = $this->targetChat($job) ?? $def->chat();
        if ($chat === null || $chat === '') {
            throw new NotifyException('unknown topic "'.$topic.'" — no target chat on the topic row or the send');
        }

        // The job carries the flag the producer resolved; when it says "not
        // premium" the live probe gets a say here too, so an async consumer
        // is not stuck with a stale flag.
        $status = $this->premiumStatus($job->premium);

        [$metadataBlock] = $this->segmenter->metadataBlock($job->context, $this->inlineBudget());
        $text = $this->composeMessage($level, $topic, $job->brief, $metadataBlock);

        $attachment = $job->attachment;
        if ($attachment !== null) {
            $fileVerdict = $this->checker->checkFile((int) ($attachment['bytes'] ?? 0), $status, 'bot');
            if (! $fileVerdict->allowed()) {
                throw new NotifyException('notify send refused: '.($fileVerdict->reason() ?? 'attachment exceeds capability'));
            }
            // §7: sendDocument carries the message as the caption — the
            // 1024-char caption cap applies (data row `caption`).
            $text = $this->truncateTo($text, self::CAPTION_BUDGET);
        }

        try {
            $receipt = $this->sender->sendWithReceipt($text, [
                'chat' => $chat,
                'thread_id' => $this->targetThread($job) ?? $def->threadId(),
            ], $attachment);
        } catch (Throwable $e) {
            $this->logger?->error('notify send failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                // A rate limit that was deferred (demanded wait over the
                // in-line cap) or exhausted throws, so the throwing path is
                // exactly where the demanded seconds have to be visible.
                'rate_limit' => $this->sender->lastRateLimit()?->toArray(),
                'exception' => $e,
            ]);

            throw $e;
        }

        // A send that slept through Telegram's demand is a send that cost
        // the caller real time: say so, at notice, with the numbers. A
        // rate limit that was honoured and retried is a success, not an
        // error, so it must not be logged as one.
        if ($receipt->rateLimited()) {
            $this->logger?->notice('notify send honoured a Telegram rate limit', [
                'topic' => $topic,
                'transport' => $receipt->transport,
                'rate_limit' => $receipt->rateLimit?->toArray(),
            ]);
        }

        // A send that never left the machine is not a send.
        //
        // With `live_mode` off — the default outside a fully wired Laravel
        // host — Client::call() returns a SELF-DESCRIBING offline marker
        // (`_offline => true`) rather than an rpc_result, and nothing in this
        // chain inspected it. So an offline send was reported delivered AND its
        // temp attachment was unlinked: the message was never sent and the only
        // copy left to retry it was gone. Two failures stacked, and the second
        // made the first unrecoverable.
        //
        // The marker is the contract's own failure signal, so honour it here:
        // keep the artefact, and say loudly that nothing was sent. The marker
        // itself is unchanged and still returned by Client — this only refuses
        // to treat it as a delivery.
        if (($receipt->response['_offline'] ?? false) === true) {
            $this->logger?->error('notify send did not happen: transport is offline (live_mode off)', [
                'topic' => $topic,
                'transport' => $receipt->transport,
                'method' => $receipt->method,
                'attachment_kept' => $attachment !== null,
            ]);

            return;
        }

        // Sent: drop the temp attachment the worker owns (retry/DL keep it
        // on failure — the file must survive for redelivery).
        if ($attachment !== null) {
            $path = (string) ($attachment['path'] ?? '');
            if ($path !== '' && is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * The chat THIS job asked for, or null when it asked for none.
     *
     * `target` is the design's {chat: int|string, thread_id: ?int}. A shape
     * the job does not carry a usable chat in (absent key, null, '', a
     * non-scalar) answers null so the caller falls back to the topic row
     * rather than dialling a nonsense peer.
     */
    private function targetChat(NotificationJob $job): int|string|null
    {
        $target = $job->target ?? [];
        $chat = $target['chat'] ?? null;

        return is_int($chat) || (is_string($chat) && $chat !== '') ? $chat : null;
    }

    /** The forum thread THIS job asked for, or null. Thread 0 / null is "no thread". */
    private function targetThread(NotificationJob $job): ?int
    {
        $target = $job->target ?? [];
        $thread = $target['thread_id'] ?? null;

        return is_int($thread) && $thread > 0 ? $thread : null;
    }

    /** The registry behind the facade — topics() powers `teleframe:notify topics`. */
    public function registry(): Topic
    {
        return $this->registry;
    }

    /**
     * Compile the job (design §5 wire shape) and hand it to the queue.
     * Unknown topic → NotifyException here, before anything is queued.
     *
     * UNDELIVERABLE JOB → NotifyException here too, for EVERY driver. The
     * chat is known at enqueue time (it is the value the job carries in
     * `target.chat`), so a job with no target chat can never be delivered by
     * any consumer. On the sync driver handle() raised that; on the async
     * drivers the job was accepted, the caller got a job id back, and the
     * notification died later inside a worker — a silent drop from the
     * caller's point of view, and the exact failure the facade's
     * "sends throw on first transport use, never silent-drop" promise
     * forbids. Refusing at the boundary is driver-independent: the caller's
     * thread is the only place the throw can be seen and acted on.
     *
     * @return string the queue job id
     */
    private function enqueue(string $topic, Level $level, string $brief, array $context, ?AttachmentFile $attachment, bool $premium): string
    {
        $rows = $this->registry->topics();
        if (! isset($rows[$topic])) {
            throw new NotifyException('unknown topic "'.$topic.'"');
        }
        $def = $rows[$topic];

        $chat = $def->chatOr('');
        if ($chat === '') {
            throw new NotifyException('unknown topic "'.$topic.'" — no target chat on the topic row or the send');
        }

        $this->guardBudgets($topic, $def, PremiumStatus::fromBool($premium));

        $job = new NotificationJob(
            jobId: 'tf-'.bin2hex(random_bytes(8)),
            topic: $topic,
            level: $level->value, // wire shape carries the PSR-3 word, not the enum (§5)
            brief: $brief,
            context: $context,
            attachment: $attachment?->toArray(),
            target: [
                'chat' => $chat,
                'thread_id' => $def->threadId(),
            ],
            premium: $premium,
            attempts: 0,
        );

        return $this->queue->enqueue($job);
    }

    /** §7 message text: `[{LEVEL}] {topic} · {ISO-8601}` + brief + metadata block. */
    private function composeMessage(Level $level, string $topic, string $brief, string $metadataBlock): string
    {
        $now = ($this->now ?? static fn (): int => time())();
        $text = '['.strtoupper($level->value).'] '.$topic.' · '.date('c', $now)."\n\n".$brief;
        if ($metadataBlock !== '') {
            $text .= "\n\n".$metadataBlock;
        }

        return $text;
    }

    /** @return string  trimmed to the budget, trailed by the truncation marker */
    private function truncateTo(string $value, int $budget): string
    {
        if ($budget <= 0 || strlen($value) <= $budget) {
            return $value;
        }

        $marker = '… [truncated]';
        $room = $budget - strlen($marker);
        if ($room <= 0) {
            return mb_substr($value, 0, $budget);
        }

        return mb_substr($value, 0, $room).$marker;
    }

    /**
     * The RATE rows, enforced (design §6 "its constraints must have been
     * managed"): send_rate (30 msg/s), per_chat_rate (1 msg/s) and
     * topic_budget (60 msg/h) were rows nothing read — checkRate() shipped a
     * verdict nobody ever asked for, and Topic::throttlePerHour() was a
     * number printed by `teleframe:notify topics` and honoured by nothing.
     * They are consulted here, at the ONE place a notification is decided.
     *
     * Counting is per (scope, window) in the injected PSR-16 store, so the
     * budget holds across the processes of a web host. With no store the
     * counters are per-process — honest, but only as real as the process
     * lives, which is why a host that cares binds a store (the provider
     * wires the host's own cache). A refused send throws NotifyException
     * (never a silent drop) unless the service is in advisory mode, where it
     * is logged at warning and the send proceeds.
     */
    private function guardBudgets(string $topic, Topic $def, PremiumStatus $status): void
    {
        $now = ($this->now ?? static fn (): int => time())();

        // 1. global send rate.
        $this->applyRateVerdict(
            $this->checker->checkRate($this->hits('send', 1, $now) + 1, $status, 'send_rate'),
        );

        // 2. per-chat rate — the row's own chat, never the payload's.
        $chat = $def->chatOr('');
        if ((string) $chat !== '') {
            $this->applyRateVerdict(
                $this->checker->checkRate($this->hits('chat:'.(string) $chat, 1, $now) + 1, $status, 'per_chat_rate'),
            );
        }

        // 3. the topic's own hourly budget: the row's throttle_per_hour is
        //    the operator's decision, the table's topic_budget row is the
        //    premium-raised ceiling — the lower of the two governs.
        $budget = $def->throttlePerHour();
        if ($budget !== null && $budget > 0) {
            $key = 'topic:'.$topic;
            $count = $this->hits($key, 3600, $now) + 1;
            $ceiling = min($budget, $this->checker->checkRate($count, $status, 'topic_budget')->effective());

            if ($count > $ceiling) {
                $this->applyRateVerdict(CapabilityCheckResult::refuse(
                    'topic "'.$topic.'" sent '.$count.' messages in the last hour, over its budget of '.$ceiling
                    .' (topic_budget / throttle_per_hour) — refusing',
                    $ceiling,
                ));
            }

            $this->record($key, $now);
        }

        $this->record('send', $now);
        if ((string) $chat !== '') {
            $this->record('chat:'.(string) $chat, $now);
        }
    }

    /** Enforce (throw) or advise (log) one rate verdict. */
    private function applyRateVerdict(CapabilityCheckResult $verdict): void
    {
        if ($verdict->allowed()) {
            return;
        }

        if (! $this->enforceBudgets) {
            $this->logger?->warning('notify budget exceeded (advisory — sending anyway)', [
                'reason' => $verdict->reason(),
                'effective' => $verdict->effective(),
            ]);

            return;
        }

        throw new NotifyException('notify send refused: '.($verdict->reason() ?? 'rate budget exceeded'));
    }

    /**
     * How many sends the counter for $key recorded inside the last $window
     * seconds. Read from the PSR-16 store when one is wired, else from this
     * process's own map.
     *
     * @return int
     */
    private function hits(string $key, int $window, int $now)
    {
        $stamps = $this->budgetStamps($key);
        $count = 0;
        foreach ($stamps as $stamp) {
            if ($stamp > $now - $window) {
                $count++;
            }
        }

        return $count;
    }

    /** Record one send against $key's counter. */
    private function record(string $key, int $now): void
    {
        $stamps = $this->budgetStamps($key);
        $stamps[] = $now;

        if (count($stamps) > self::BUDGET_HISTORY) {
            $stamps = array_slice($stamps, -self::BUDGET_HISTORY);
        }

        if ($this->budgets !== null) {
            $this->budgets->set(self::BUDGET_PREFIX.$key, $stamps, 7_200);

            return;
        }

        $this->localBudgets[$key] = $stamps;
    }

    /**
     * @return list<int> unix timestamps recorded for $key
     */
    private function budgetStamps(string $key): array
    {
        if ($this->budgets !== null) {
            $stored = $this->budgets->get(self::BUDGET_PREFIX.$key);
            if (is_array($stored)) {
                return array_values(array_map(static fn ($stamp): int => (int) $stamp, $stored));
            }

            return [];
        }

        return $this->localBudgets[$key] ?? [];
    }

    private function renderer(): HtmlAttachment
    {
        return $this->renderer ?? new HtmlAttachment;
    }

    private function inlineBudget(): int
    {
        return $this->inlineBudget ?? 800;
    }

    private function attachmentDir(): string
    {
        return $this->attachmentDir ?? sys_get_temp_dir();
    }

    /**
     * First-slice section body (report multi-section polish is wave 3):
     * heading + stringified content blocks, escaped by the renderer.
     *
     * @param  array<string|int, mixed>  $sections
     */
    private function renderSections(array $sections): string
    {
        $chunks = [];
        foreach ($sections as $heading => $content) {
            if (! is_int($heading) && (string) $heading !== '') {
                $chunks[] = (string) $heading;
            }
            $chunks[] = $this->stringify($content);
        }

        return implode("\n\n", $chunks);
    }

    private function stringify(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? (string) json_last_error_msg() : $json;
    }
}
