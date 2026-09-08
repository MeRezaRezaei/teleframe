<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler\Keyboard;

use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;

/**
 * The callback bridge (Q15): turn an ``answerCallbackQuery``-style update
 * into an ordinary message-style dispatch. Register an entry point on the
 * shared handler table and the rest flows like a message:
 *
 *     $handlers->on('callback_query', fn (Update $u, MenuRouter $router) => $router->dispatch($u));
 *
 * Steps:
 *
 * 1) Extract the bound chat/message ids and decode the signed token — ANY
 *    codec mismatch (forged/tampered/rebound) lands on the expiry answer.
 *    A pre-send token (signed with no message id) is also confirmed by its
 *    chat binding — the narrowest scope the sender knew at render time.
 * 2) Resolve the key reference against the live Registry — an old version
 *    (Q16 rotation) resolves to null -> expiry answer.
 * 3) Re-dispatch a synthetic frame whose constructor is the uprate route
 *    plus the signed arg, so the shared ``HandlerRegistry::on()`` table
 *    routes it exactly like a message update (sscanf ``%s`` arg receipt).
 *
 * The expiry answer goes through the injectable ``$answer`` callable — wrap
 * ``BotClient::answerCallbackQuery($id, $text)`` in a closure; null disables
 * the network call (tests, dry runs).
 */
final class MenuRouter
{
    public const EXPIRED_TEXT = 'Menu expired';

    /**
     * @param \Closure(string $callbackQueryId, string $text): void|null $answer
     */
    public function __construct(
        private readonly UpdateDispatcher $dispatcher,
        private readonly KeyboardRegistry $keyboards,
        private readonly string $secret,
        private readonly ?\Closure $answer = null,
    ) {
    }

    public function dispatch(Update $update): mixed
    {
        $callback = $update->array['callback_query'] ?? null;
        if (! is_array($callback)) {
            return null;
        }

        $data = $callback['data'] ?? null;
        if (! is_string($data) || $data === '') {
            return null;
        }

        $rawMsgId = isset($callback['message']['message_id']) ? $callback['message']['message_id'] : null;
        $rawChatId = isset($callback['message']['chat']['id']) ? $callback['message']['chat']['id'] : null;
        $msgId = is_int($rawMsgId) || is_string($rawMsgId) ? $rawMsgId : null;
        $chatId = is_int($rawChatId) || is_string($rawChatId) ? $rawChatId : null;

        $decoded = $this->decode($data, $msgId, $chatId);
        if ($decoded === null) {
            return $this->expire($callback);
        }

        $keyRef = explode(':', $decoded['route'], 2);
        if (count($keyRef) !== 2) {
            return $this->expire($callback);
        }
        [$keyId, $keyIdx] = $keyRef;

        $route = $this->keyboards->routeOf($keyId, (int) $keyIdx);
        if ($route === null) {
            return $this->expire($callback);
        }

        return $this->dispatcher->dispatch($this->frame($update, $callback, $route, $decoded['arg']));
    }

    /**
     * Answer a rejected/expired query with the menu-expired notice. Public so
     * handlers on other send surfaces can reuse the exact wording/path.
     *
     * @param array<string, mixed> $callback
     */
    public function expire(array $callback): null
    {
        $id = $callback['id'] ?? null;
        if (is_string($id) && $id !== '' && $this->answer !== null) {
            ($this->answer)($id, self::EXPIRED_TEXT);
        }

        return null;
    }

    /**
     * Two-scope confirmation: a message-bound token must pass on its exact
     * message+chat; a pre-send token (bound with no message id) passes on its
     * chat binding alone. Forged/rebound tokens fail both passes.
     */
    private function decode(string $data, int|string|null $msgId, int|string|null $chatId): ?array
    {
        $decoded = CallbackData::decode($data, $msgId, $chatId, $this->secret);
        if ($decoded !== null) {
            return $decoded;
        }

        return CallbackData::decode($data, null, $chatId, $this->secret);
    }

    /**
     * A synthetic uprate whose constructor is the route (arg appended as the
     * sscanf token) — the shared HandlerRegistry::on() table then matches and
     * arg-injects it exactly like a message update.
     *
     * @param array<string, mixed> $callback
     */
    private function frame(Update $update, array $callback, string $route, string $arg): Update
    {
        $array = $update->array;
        $array['_'] = $arg === '' ? $route : $route . ' ' . $arg;
        $array['keyboard_arg'] = $arg;

        return new Update($array, $update->accountId, $update->source, $update->ts);
    }
}