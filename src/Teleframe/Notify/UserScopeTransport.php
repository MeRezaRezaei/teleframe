<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Closure;
use MeRezaRezaei\Teleframe\Core\Types\InputPeer;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * UserScopeTransport — the MTProto USER-account sender (V1.1's first
 * deferred item, design wave 3).
 *
 * Until now the notification core could only send as a BOT: the send
 * process dialled `BotClient`, and a bot cannot reach a chat the user is
 * in, cannot post into a supergroup the bot was not added to, and cannot
 * use a user session's rate budget. This class makes the user-account path
 * a first-class sender without forking the send process: it implements the
 * same `NotificationTransportContract`, so `NotificationSender` builds the
 * plan, applies the capability gate, splits brief+attachment and runs the
 * rate-limit policy EXACTLY as it does for a bot.
 *
 * WHAT IS LAZY, AND WHY IT MATTERS:
 *
 * The account scope is resolved through an INJECTED resolver
 * (`callable(): object`), once, on the FIRST send — never at construction.
 * A `UserAccountScope` is a session-backed MTProto client: resolving one
 * at bind time would mean dialling Telegram from a container factory, at
 * boot, for a notifier that may never send. This is the posture the repo's
 * other lazy scope-resolved senders already hold (`RelayTerminal`,
 * `AccountRelay`, `PeerMembership`): construct cheap, resolve at first use,
 * memoize, and REFUSE LOUDLY when nothing can be resolved. `resolved()`
 * exists so that posture is assertable rather than asserted-in-a-comment.
 *
 * THE TWO SEAMS MTPROTO FORCES, BOTH INJECTED, BOTH LOUD:
 *
 *  1. **A peer is not an id.** `messages.sendMessage` takes an
 *     `InputPeer`, which for users and channels carries an `access_hash`
 *     that only a prior API call can produce. The notify core's topic rows
 *     carry Bot-API chats (int id or `@username`), so this transport
 *     accepts a ready peer array as-is and otherwise asks the injected
 *     `peerResolver` (`callable(mixed): array`) to resolve one. With no
 *     resolver, or a resolver that answers with something that is not a
 *     peer, the send throws `NotifyException` — it never guesses an
 *     `access_hash` and never silently drops the notification.
 *  2. **A file is not a filename.** The long half of a notification is a
 *     rendered HTML attachment, and shipping it over MTProto means
 *     `upload.saveFilePart` to build an `inputMediaUploadedDocument`
 *     first. That is a host concern (it needs the account's file
 *     bookkeeping), so it arrives as the injected `uploader`
 *     (`callable(string $path, string $mime, string $name): array`).
 *     Without it, an attachment send throws `NotifyException` rather than
 *     sending a caption and losing the report.
 *
 * Only the two methods the notify core emits are carried
 * (`sendMessage`, `sendDocument`); any other method is refused loudly
 * rather than passed through as a half-translated call.
 *
 * The payload translation itself:
 *  - `chat_id`           → `peer` (through the peer seam above)
 *  - `text`              → `message`
 *  - `message_thread_id` → `reply_to` (an `InputReplyTo`). MTProto has no
 *    forum-topic parameter: a topic is addressed by replying to its root
 *    service message, so the Bot API's thread id is carried across as an
 *    `inputReplyToMessage{reply_to_msg_id}`. A host that wants true topic
 *    routing resolves the topic's root message id into the topic row's
 *    thread slot. The NESTED field is `reply_to_msg_id`, but the field on
 *    `messages.sendMessage` itself is `reply_to:flags.0?InputReplyTo` —
 *    see `replyTo()` for why sending the bare `reply_to_msg_id` was a
 *    SILENT no-op, not a loud one.
 *  - `entities`          → `entities`, passed through verbatim. The shared
 *    message pipeline already emits MTProto constructor names
 *    (`messageEntityBold`, …) with UTF-16 offsets, so the plan needs no
 *    translation; a plain-text notification carries none.
 *  - `document`+`caption`→ `messages.sendMedia` with the uploaded media and
 *    the caption as `message`.
 *
 * Rate limits on this path are the real MTProto ones, and they are read
 * and honoured by `RateLimitPolicy` (FLOOD_WAIT_x, FLOOD_PREMIUM_WAIT_x,
 * SLOWMODE_WAIT_x) because the `UserAccountScope::call()` surface raises
 * the same typed/coded errors the Bot API path raises its own.
 *
 * Plain-PHP constructible: the constructor stores closures and a logger
 * and touches nothing. No container, no network, no clock, no session
 * string — the resolver hands over an already-built scope, exactly as
 * `RelayFactory`/`RelaySessionResolver` do for the relay seam.
 *
 * HONEST LIMITS (what the tests do and do not prove):
 *  - The tests inject a stub scope, a stub peer resolver and a stub
 *    uploader, and prove the translation, the laziness, the memoization,
 *    the loud refusals and the rate-limit hand-off. No test here opens a
 *    socket, and none of the fixtures is a capture from a live account.
 *  - Only a live MTProto session can confirm that the peer a host's
 *    `peerResolver` returns is accepted by Telegram, that the reply
 *    mapping really lands in the intended forum topic, and that the
 *    uploader's media is uploadable. This repo has never run against a
 *    live Telegram user session; the wire shapes follow the shipped
 *    `UserAccountScope` and `InputPeer` helpers, which is the strongest
 *    evidence available offline and no more than that.
 */
final class UserScopeTransport implements NotificationTransportContract
{
    /** The Bot-API methods this transport knows how to carry. */
    private const CARRIED = ['sendMessage' => true, 'sendDocument' => true];

    /**
     * How long an `random_id` stays bound to one wire shape after a FAILED
     * dial, so a retry of the same logical send reuses it. Bounded on
     * purpose: without a bound, two genuinely different sends that happen to
     * be byte-identical would collide on one `random_id` and Telegram would
     * dedup the second away. 120s comfortably covers a queue retry cycle
     * (both drivers re-publish immediately) while staying well inside the
     * interval in which a human re-typing the same text is plausible.
     */
    private const RANDOM_ID_WINDOW = 120;

    /** @var array<string, array{id: int, at: int}> wire fingerprint => memoised `random_id` */
    private array $randomIds = [];

    /** @var (Closure(): int)|null */
    private readonly ?Closure $now;

    /** @var (Closure(): mixed)|null */
    private readonly ?Closure $scopeResolver;

    /** @var (Closure(mixed): mixed)|null */
    private readonly ?Closure $peerResolver;

    /** @var (Closure(string, string, string): mixed)|null */
    private readonly ?Closure $uploader;

    private bool $resolved = false;

    private mixed $scope = null;

    /**
     * @param  callable(): mixed|null  $scopeResolver  production: a host's lazily built
     *                                                 UserAccountScope (e.g. over the vault /
     *                                                 RelayFactory), injected so this class
     *                                                 stays plain-PHP and never dials on
     *                                                 construction. null = unconfigured, and
     *                                                 the FIRST send refuses loudly.
     * @param  callable(mixed): mixed|null  $peerResolver  (chat id|@username) => InputPeer array
     * @param  callable(string, string, string): mixed|null  $uploader  (path, mime, name) => InputMedia array
     * @param  LoggerInterface|null  $logger  failures logged at error and ALWAYS rethrown
     * @param  callable(): int|null  $now  unix clock seam (default time()), bounds the `random_id` memo
     */
    public function __construct(
        ?callable $scopeResolver = null,
        ?callable $peerResolver = null,
        ?callable $uploader = null,
        private readonly ?LoggerInterface $logger = null,
        ?callable $now = null,
    ) {
        $this->scopeResolver = $scopeResolver === null ? null : Closure::fromCallable($scopeResolver);
        $this->peerResolver = $peerResolver === null ? null : Closure::fromCallable($peerResolver);
        $this->uploader = $uploader === null ? null : Closure::fromCallable($uploader);
        $this->now = $now === null
            ? null
            : Closure::fromCallable($now);
    }

    /**
     * Whether the account scope has been resolved. False straight after
     * construction — the "never dials on construct" proof, assertable.
     */
    public function resolved(): bool
    {
        return $this->resolved;
    }

    /**
     * The live account scope, resolved on FIRST USE and memoized for the
     * life of the transport, or a loud `NotifyException`. Construction
     * never resolves: a scope is a session-backed client and a notifier
     * that has not sent yet must not have dialled anything.
     *
     * @throws NotifyException when nothing can resolve a scope, or the resolver returns something that is not an object
     */
    public function scope(): object
    {
        if (! $this->resolved) {
            if ($this->scopeResolver === null) {
                throw new NotifyException(
                    'teleframe.notify: no MTProto user scope bound — inject a scope resolver that returns a live UserAccountScope (e.g. fn () => app(TeleframeClient::class)->user()), or bind a bot transport instead. Refusing rather than silently dropping the notification.',
                );
            }

            $scope = ($this->scopeResolver)();

            if (! is_object($scope)) {
                throw new NotifyException(sprintf(
                    'teleframe.notify: the MTProto user-scope resolver returned %s, not an object — refusing the send.',
                    get_debug_type($scope),
                ));
            }

            $this->scope = $scope;
            $this->resolved = true;
        }

        /** @var object $scope */
        $scope = $this->scope;

        return $scope;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws NotifyException on an unconfigured transport, an uncarried method, an unresolvable peer or an unuploadable attachment
     */
    public function call(string $method, array $params = []): array
    {
        if (! isset(self::CARRIED[$method])) {
            throw new NotifyException(sprintf(
                'teleframe.notify: the MTProto user-scope transport carries %s only (got "%s") — refusing rather than passing a half-translated call through.',
                implode('/', array_keys(self::CARRIED)),
                $method,
            ));
        }

        // The scope is resolved FIRST: an unconfigured transport must say
        // so before it starts resolving peers or uploading anything, and
        // "never dial at construction" must be true of the refusal path
        // too — the resolver is what would have dialled.
        $scope = $this->scope();

        if (! method_exists($scope, 'call')) {
            throw new NotifyException(sprintf(
                'teleframe.notify: the bound MTProto user scope %s does not expose call(string, array) — refusing the send.',
                $scope::class,
            ));
        }

        return $method === 'sendMessage'
            ? $this->sendMessage($scope, $params)
            : $this->sendDocument($scope, $params);
    }

    /** Log-safe label; says "unconfigured" so a status line never implies a live account. */
    public function describe(): string
    {
        return $this->scopeResolver === null ? 'mtproto:user-scope (unconfigured)' : 'mtproto:user-scope';
    }

    /**
     * `sendMessage` → `messages.sendMessage`.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed> the raw Updates reply
     */
    private function sendMessage(object $scope, array $params): array
    {
        $wire = [
            'peer' => $this->peer($params['chat_id'] ?? null),
            'message' => (string) ($params['text'] ?? ''),
            'random_id' => $this->randomId($params),
        ];

        $thread = $params['message_thread_id'] ?? null;
        $replyTo = $this->replyTo($thread);
        if ($replyTo !== null) {
            $wire['reply_to'] = $replyTo;
        }

        $entities = $params['entities'] ?? null;
        if (is_array($entities) && $entities !== []) {
            $wire['entities'] = array_values($entities);
        }

        return $this->dial($scope, 'messages.sendMessage', $params, $wire);
    }

    /**
     * `sendDocument` → `messages.sendMedia` with an uploaded document and
     * the caption as the message. Without an uploader this throws — the
     * long half of a notification is the report, and a caption alone would
     * be a silent loss.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed> the raw Updates reply
     */
    private function sendDocument(object $scope, array $params): array
    {
        $path = (string) ($params['document'] ?? '');

        if ($this->uploader === null) {
            throw new NotifyException(
                'teleframe.notify: an MTProto attachment send needs an uploader seam (callable(path, mime, name): InputMedia) — the rendered report would be lost without it, so the send is refused rather than reduced to its caption.',
            );
        }

        $media = ($this->uploader)(
            $path,
            (string) ($params['mime'] ?? 'application/octet-stream'),
            basename($path),
        );

        if (! is_array($media) || $media === []) {
            throw new NotifyException('teleframe.notify: the MTProto uploader seam returned no media — refusing the attachment send.');
        }

        return $this->dial($scope, 'messages.sendMedia', $params, [
            'peer' => $this->peer($params['chat_id'] ?? null),
            'reply_to' => $this->replyTo($params['message_thread_id'] ?? null),
            'media' => $media,
            'message' => (string) ($params['caption'] ?? ''),
            'random_id' => $this->randomId($params),
        ]);
    }

    /**
     * The `reply_to` value for one send, or null for "no reply target".
     *
     * WHY IT IS A NESTED OBJECT AND NOT A BARE `reply_to_msg_id`.
     * The declared constructor — `messages.sendMessage#fef48f62` in
     * `schema/sources/api.tl:2495` — has NO `reply_to_msg_id` field. It has
     * `reply_to:flags.0?InputReplyTo`. `reply_to_msg_id` is a field of
     * `inputReplyToMessage#3bd4b7c2`, one level DOWN. So the old shape
     * (a top-level `reply_to_msg_id`) was not wrong-typed, it was
     * undeclared — and that is the SILENT kind of wrong, because
     * `TLEncoder::encodeObject` walks the DECLARED fields: it finds no
     * field named `reply_to_msg_id`, never sets `flags.0` for it, and drops
     * the key without a word. `flags = 0`, `reply_to` absent, no exception,
     * no log. A notification aimed at forum topic 3 arrived in General and
     * every layer above reported success.
     *
     * WHY `inputReplyToMessage` AND NOT A SIBLING VARIANT. `InputReplyTo`
     * has four variants in the shipped schema:
     *  - `inputReplyToMessage#3bd4b7c2 flags:# reply_to_msg_id:int …` — the
     *    ONLY one whose required field is a bare message id, which is what a
     *    Bot-API `message_thread_id` is. For a forum supergroup the topic id
     *    IS the id of the topic's root service message, so replying to that
     *    id is what puts the message in the topic.
     *  - `inputReplyToMonoForum#69d66c45 monoforum_peer_id:InputPeer` — a
     *    monoforum, addressed by peer; no thread id can populate it.
     *  - `inputReplyToStory#5881323a peer:InputPeer story_id:int` — a story
     *    reply; a thread id is not a story id.
     *  - `inputReplyToEphemeralMessage#4119b95e id:int` — an ephemeral-chat
     *    service id, not a topic.
     * `top_msg_id` is deliberately left unset: it names a specific comment
     * inside a topic, and the contract here is "post in the topic", which is
     * the root reply.
     *
     * @return array{_: string, reply_to_msg_id: int}|null
     */
    private function replyTo(mixed $thread): ?array
    {
        if (! is_int($thread) || $thread <= 0) {
            return null;
        }

        return ['_' => 'inputReplyToMessage', 'reply_to_msg_id' => $thread];
    }

    /**
     * The peer for one send: a ready InputPeer array is used as-is,
     * anything else goes through the injected resolver. Bare ids and
     * `@usernames` cannot be turned into a peer here — MTProto needs an
     * `access_hash` that only a prior API call can produce — so with no
     * resolver this refuses loudly.
     */
    private function peer(mixed $chat): array
    {
        if (is_array($chat) && $chat !== []) {
            return $chat;
        }

        if ($this->peerResolver === null) {
            throw new NotifyException(sprintf(
                'teleframe.notify: cannot address the MTProto peer %s — a topic row carries a Bot-API chat (id or @username) and MTProto needs an InputPeer with an access_hash. Inject a peer resolver, or put a ready InputPeer array on the topic row. Refusing rather than sending to a guessed peer.',
                $chat === null ? '(none)' : (is_scalar($chat) ? (string) $chat : get_debug_type($chat)),
            ));
        }

        $peer = ($this->peerResolver)($chat);

        if (! is_array($peer) || $peer === [] || ! isset($peer['_'])) {
            throw new NotifyException(sprintf(
                'teleframe.notify: the MTProto peer resolver answered %s for chat %s, not an InputPeer (a "_" constructor key is required) — refusing the send.',
                is_array($peer) ? 'an unnamed array' : get_debug_type($peer),
                is_scalar($chat) ? (string) $chat : get_debug_type($chat),
            ));
        }

        /** @var array<string, mixed> $peer */
        return $peer;
    }

    /**
     * The `random_id` for one send — MTProto's DEDUP KEY, and therefore
     * the one field that makes a retry idempotent.
     *
     * It used to be `random_int(...)` regenerated inside sendMessage()/
     * sendDocument(), so every attempt the rate-limit policy made re-dialled
     * with a FRESH id. A retry after an ambiguous failure (delivered, reply
     * lost — a dropped socket, a rate limit honoured mid-send) therefore
     * POSTED THE SAME MESSAGE TWICE, silently: Telegram had no way to
     * recognise the second copy as a duplicate.
     *
     * WHY IT IS MEMOISED PER WIRE SHAPE, NOT DERIVED FROM THE JOB ID.
     * This transport never sees a job id — it is handed Bot-API params by
     * `NotificationSender` — so the attempt-set can only be identified by
     * the shape of the send itself. Two consequences follow, and both are
     * the safe direction:
     *
     *  - a RETRY (same peer, text, thread, media — RateLimitPolicy re-issues
     *    byte-identical `$params`) reuses the id, so the server dedups the
     *    second attempt into the first instead of posting a duplicate;
     *  - two DIFFERENT sends differ in the fingerprint, so they can never
     *    collide. The only way an id is reused by a distinct send is a
     *    byte-identical one arriving within RANDOM_ID_WINDOW of a FAILED
     *    attempt (successes clear the memo, see dial()) — which is the
     *    dedup window a retry needs, bounded so a later human re-send is
     *    never swallowed.
     */
    private function randomId(array $params): int
    {
        $fingerprint = md5(serialize($params));
        $now = ($this->now ?? static fn (): int => time())();

        $memo = $this->randomIds[$fingerprint] ?? null;
        if ($memo !== null && ($now - $memo['at']) <= self::RANDOM_ID_WINDOW) {
            $this->randomIds[$fingerprint] = ['id' => $memo['id'], 'at' => $now];

            return $memo['id'];
        }

        $id = random_int(1, PHP_INT_MAX);
        $this->randomIds[$fingerprint] = ['id' => $id, 'at' => $now];

        return $id;
    }

    /**
     * One MTProto call through the resolved scope, whose usability
     * `call()` already checked once up front. Transport failures are logged
     * at error and ALWAYS rethrown — logging never swallows (AGENTS.md),
     * and `RateLimitPolicy` upstream is what turns the rate-limit ones into
     * honoured waits.
     *
     * A DELIVERED send releases its memoised `random_id` (see randomId()):
     * the send succeeded, so the next byte-identical send is a NEW message
     * and must not inherit the previous one's dedup key.
     *
     * @param  array<string, mixed>  $params  the Bot-API params the call came in with (the retry fingerprint)
     * @param  array<string, mixed>  $wire  the translated MTProto request
     * @return array<string, mixed>
     */
    private function dial(object $scope, string $method, array $params, array $wire): array
    {
        try {
            $result = $scope->call($method, $wire);
            unset($this->randomIds[md5(serialize($params))]);

            return is_array($result) ? $result : [];
        } catch (Throwable $e) {
            $this->logger?->error('teleframe.notify: MTProto user-scope send failed', [
                'method' => $method,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * The InputPeer a plain int chat id most often is: a user. Offered as
     * a documented default for a host's `peerResolver` so the shape it must
     * return is unambiguous (and unreachable without the access_hash, which
     * is why this is a helper and not a default).
     */
    public static function userPeer(int $userId, int|string $accessHash = 0): array
    {
        return InputPeer::user($userId, $accessHash);
    }
}
