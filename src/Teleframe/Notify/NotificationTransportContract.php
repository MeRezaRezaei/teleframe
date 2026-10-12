<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * NotificationTransportContract — the ONE seam where the notification core
 * stops caring WHO is talking to Telegram.
 *
 * The verbatim asked for exactly this: *"the process of sending won't
 * change and most of the time you can see the correct way of it inside
 * telegram codes so this is the first thing and it's too important"*, and
 * *"it can happen inside a channel using a bot or inside bot session or
 * inside a supergroup managing by the bot"*. `NotificationSender` speaks
 * this contract and nothing else: the plan build, the capability gate, the
 * brief+attachment split and the rate-limit policy are byte-identical
 * whichever transport carries the bytes.
 *
 * The method names on this contract are the Bot API names the notify core
 * already emits (`sendMessage`, `sendDocument`) and the parameters are the
 * Bot API parameters. That is deliberate: it keeps ONE send process and
 * pushes the per-transport translation to the transport, which is the only
 * place that knows the difference between `chat_id`/`message_thread_id`
 * and MTProto's `peer`/`reply_to_msg_id`. An MTProto transport that
 * received an already-translated payload would be a second send process
 * wearing one class's name.
 *
 * Implementors:
 *  - `BotApiTransport`     — Bot API over the engine's `BotClient` (the shipped default).
 *  - `UserScopeTransport`  — MTProto as a USER account over a lazily
 *                            resolved `UserAccountScope`.
 *
 * A host with a third transport implements this and binds it; the
 * `NotifyServiceProvider` autowires `NotificationSender`, whose transport
 * parameter is nullable and unbound by default, so a host binding
 * `NotificationTransportContract::class` in the container is picked up
 * without any provider edit.
 */
interface NotificationTransportContract
{
    /**
     * Dispatch one Bot-API-shaped call. Implementations translate as needed
     * and return the transport's own reply shape.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws NotifyException when the transport is unconfigured or the call is not one it carries
     * @throws \Throwable from the underlying client, always rethrown (never swallowed)
     */
    public function call(string $method, array $params = []): array;

    /**
     * A short, log-safe label for the transport ("bot-api",
     * "mtproto:user-scope", …). Never a session string, a token, a chat
     * id or a peer — this string ends up in PSR-3 contexts and receipts.
     */
    public function describe(): string;
}
