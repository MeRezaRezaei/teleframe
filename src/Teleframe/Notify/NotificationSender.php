<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Message\FilesystemCache;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Message\MessageFactory;
use MeRezaRezaei\Teleframe\Message\MessagePlanValidator;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * THE ONE shared send process (design §0/§5, spec G5/G11): builds the message
 * through the existing `src/Teleframe/Message/` pipeline — MessageFactory →
 * MessageCompiler (FilesystemCache-backed) → MessagePlanValidator as the plan
 * gate — then hands it to a TRANSPORT and lets `RateLimitPolicy` govern the
 * retries. Whether the send was triggered by a bot service or the notifier,
 * and whether it leaves as a bot or as an MTProto user account, this process
 * is byte-identical past the facade: the verbatim asked for exactly that
 * ("the process of sending won't change"), and the transport seam is what
 * makes it true rather than aspirational.
 *
 * Pipeline calls (per wave contract):
 *   - `MessageFactory::media()`  → `MessageCompiler::compileFrom()` — the Q14
 *     closure fallback: a dynamic notification text can never be a named
 *     template artifact, so the plan is built (validated, uncached) per send.
 *   - `MessagePlanValidator::assertPlanValid()` — the plan gate: NotifySender
 *     feeds assertPlanValid-compatible plans only (design §12).
 *   - `FilesystemCache`          — the compiler's PSR-16 store, reachable via
 *     `cacheStore()` (the same store named-template sends share).
 *
 * Transport (V1.1's deferred item, now shipped): the constructor takes an
 * optional `NotificationTransportContract`. Unbound, it is a `BotApiTransport`
 * over the injected `BotClient` — the shipped default, unchanged. Bound (a
 * host binding the contract in the container, or a plain-PHP caller passing
 * `UserScopeTransport`), the same process sends as an MTProto USER account.
 * With NEITHER a transport nor a bot, the first send refuses loudly; nothing
 * is dropped quietly.
 *
 * Rate limits (V1.1's other deferred item, now shipped): every transport call
 * funnels through `RateLimitPolicy`, which reads the wait Telegram demanded
 * (FLOOD_WAIT_x, FLOOD_PREMIUM_WAIT_x, SLOWMODE_WAIT_x, Bot API 429), sleeps
 * it when it is inside the in-line cap, defers it loudly when it is not, and
 * records what it did in a `RateLimitOutcome`. `send()` keeps returning the
 * raw transport reply (every existing caller depends on that);
 * `sendWithReceipt()` returns the same send with that record attached, and
 * `lastRateLimit()` answers even when the send threw.
 *
 * Failure posture (spec §7): every failure is logged through the PSR-3 logger
 * at `error` and ALWAYS rethrown — logging never swallows; the notify queue's
 * retry/dead-letter owns recovery, never a silent drop.
 *
 * Plain-PHP constructible: constructor wires the compiler, the transport and
 * the rate-limit policy; no container calls inside.
 */
final class NotificationSender
{
    private readonly MessageFactory $messages;

    private readonly RateLimitPolicy $policy;

    private readonly ?NotificationTransportContract $transport;

    /**
     * @param  MessageCompiler  $compiler  the shared message pipeline
     * @param  BotClient|null  $bot  the shipped Bot API default; ignored when a transport is given
     * @param  LoggerInterface|null  $logger  failures logged at error — ALWAYS rethrown
     * @param  int  $maxWaitSeconds  the rate-limit in-line cap (was $maxFloodWaitSeconds; now covers
     *                               every rate-limit shape, not only the MTProto-typed flood)
     * @param  int  $maxAttempts  total transport dials per send
     * @param  callable(int): void|null  $sleep  injectable sleeper, default real sleep()
     * @param  NotificationTransportContract|null  $transport  the transport to send through;
     *                                                         null = BotApiTransport over $bot
     */
    public function __construct(
        private readonly MessageCompiler $compiler,
        ?BotClient $bot = null,
        private readonly ?LoggerInterface $logger = null,
        int $maxWaitSeconds = 120,
        int $maxAttempts = 3,
        ?callable $sleep = null,
        ?NotificationTransportContract $transport = null,
    ) {
        $this->messages = new MessageFactory($compiler);

        // A host with neither a transport nor a bot is a configuration
        // error, and it is reported at FIRST SEND (this class stays
        // constructible so the facade resolves and can report), never as a
        // silent no-op.
        $this->transport = $transport
            ?? ($bot !== null ? new BotApiTransport($bot) : null);

        $this->policy = new RateLimitPolicy(
            fn (string $method, array $params): array => $this->dispatch($method, $params),
            null,
            $maxWaitSeconds,
            $maxAttempts,
            $sleep,
            logger: $this->logger,
        );
    }

    /**
     * One transport call, or the loud refusal for a sender that was built
     * with neither a bot nor a transport. A notification that cannot be
     * sent must be VISIBLE, so this throws instead of returning an empty
     * reply that the queue would happily ack.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function dispatch(string $method, array $params): array
    {
        if ($this->transport === null) {
            throw new NotifyException(
                'teleframe.notify: no transport — the sender was built without a BotClient and without a NotificationTransportContract. Bind a bot token, or pass a transport (e.g. UserScopeTransport for an MTProto user account). Refusing rather than dropping the notification.',
            );
        }

        return $this->transport->call($method, $params);
    }

    /**
     * Build the entity plan for one notification text through the message
     * pipeline and return the validated plan.
     *
     * @return array{text: string, entities: list<array<string, mixed>>, reply_markup?: array<string, mixed>}
     */
    public function plan(string $text, string $label = 'notify'): array
    {
        $plan = $this->messages->media(
            $label,
            static fn (array $data): array => ['text' => (string) $data['text']],
            ['text' => $text],
        );

        return MessagePlanValidator::assertPlanValid($plan);
    }

    /**
     * Send one notification: build the message via the pipeline, then ship it
     * through the transport. With an attachment the send becomes
     * `sendDocument` (caption = the brief message, design §7); without one it
     * is `sendMessage` with the parsed entity plan. Both route through the
     * rate-limit policy.
     *
     * @param  array{chat?: int|string|array<string, mixed>, thread_id?: int|null}  $target
     *        `chat` is a Bot-API chat id / @username (the Bot API path) or a
     *        ready InputPeer array (the MTProto user-scope path — a topic row
     *        can only ever carry an int|string, so an array chat comes from a
     *        direct caller)
     * @param  array{name: string, path: string, mime: string, bytes: int}|null  $attachment
     * @return array<string, mixed> the transport's reply; the sender rethrows on failure
     *
     * @throws InvalidArgumentException when the target has no chat
     * @throws NotifyException when the transport is unconfigured, or a rate limit is deferred/exhausted
     * @throws Throwable ALWAYS — failures are logged at `error` and rethrown
     */
    public function send(string $text, array $target, ?array $attachment = null, string $label = 'notify'): array
    {
        return $this->sendWithReceipt($text, $target, $attachment, $label)->response;
    }

    /**
     * The same send as send(), returning the receipt: the transport's reply
     * plus the `RateLimitOutcome` recording every wait honoured and whether
     * a rate limit was deferred. The queue worker uses it so an operator can
     * see that a send cost 30 seconds of sleep, or that Telegram's demanded
     * wait was over the cap and the job went back to the queue.
     *
     * @param  array{chat?: int|string|array<string, mixed>, thread_id?: int|null}  $target
     *        `chat` is a Bot-API chat id / @username (the Bot API path) or a
     *        ready InputPeer array (the MTProto user-scope path — a topic row
     *        can only ever carry an int|string, so an array chat comes from a
     *        direct caller)
     * @param  array{name: string, path: string, mime: string, bytes: int}|null  $attachment
     *
     * @throws InvalidArgumentException when the target has no chat
     * @throws NotifyException when the transport is unconfigured, or a rate limit is deferred/exhausted
     * @throws Throwable ALWAYS — failures are logged at `error` and rethrown
     */
    public function sendWithReceipt(string $text, array $target, ?array $attachment = null, string $label = 'notify'): SendReceipt
    {
        if (! array_key_exists('chat', $target)) {
            throw new InvalidArgumentException('Notification target needs a chat (int|string).');
        }

        $chat = $target['chat'];
        $threadId = $target['thread_id'] ?? null;

        try {
            $params = [
                'chat_id' => $chat,
                'message_thread_id' => $threadId,
            ];

            if ($attachment !== null) {
                $params['document'] = $attachment['path'];
                $params['mime'] = $attachment['mime'];
                $params['caption'] = $text;

                $response = ($this->policy)('sendDocument', $params);
            } else {
                $plan = $this->plan($text, $label);
                $params['text'] = $plan['text'];
                if ($plan['entities'] !== []) {
                    $params['entities'] = $plan['entities'];
                }

                $response = ($this->policy)('sendMessage', $params);
            }
        } catch (Throwable $throwable) {
            $this->logger?->error('teleframe.notify: send failed', [
                'exception' => $throwable,
                'chat' => $chat,
                'text_length' => strlen($text),
                'attachment' => $attachment !== null,
                // The rate-limit record is kept even on the throwing path:
                // the deferred/exhausted cases have no receipt, and this
                // is where the demanded seconds become visible.
                'rate_limit' => $this->lastRateLimit()?->toArray(),
            ]);

            throw $throwable;
        }

        return new SendReceipt(
            $this->transportName(),
            $attachment !== null ? 'sendDocument' : 'sendMessage',
            $response,
            $this->lastRateLimit(),
        );
    }

    /**
     * What the LAST send's rate-limit policy did — available even when the
     * send threw, which is exactly the deferred/exhausted case.
     */
    public function lastRateLimit(): ?RateLimitOutcome
    {
        return $this->policy->lastOutcome();
    }

    /** The transport this sender ships through, log-safe label ('bot-api', …). */
    public function transportName(): string
    {
        return $this->transport?->describe() ?? 'none';
    }

    /** The FilesystemCache store backing this sender's message compiler. */
    public function cacheStore(): FilesystemCache
    {
        return $this->compiler->cacheStore();
    }
}
