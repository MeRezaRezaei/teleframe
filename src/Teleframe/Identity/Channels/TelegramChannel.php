<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Identity\Channels;

use Illuminate\Notifications\Notification;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Identity\IdentityConfig;
use MeRezaRezaei\Teleframe\Identity\TlUserBinding;

/**
 * Q9 telegram notification channel.
 *
 * Sender resolution (ruled): Laravel-native `routeNotificationForTelegram()`
 * override when the notifiable defines it (an explicit null route means "do
 * not deliver", matching Laravel's own channel semantics), else the
 * configured default sender fallback (`teleframe.identity.default_sender` /
 * `TELEFRAME_IDENTITY_DEFAULT_SENDER`) with the recipient taken from the
 * notifiable's binding.
 *
 * Delivery engine (ruled — see design doc §Q9): `BotClient::sendMessage()`.
 * Inspected candidates: `Laravel\Services\TeleframeClient` is the account-
 * bound MTProto entry point, but it takes a method request — there is no
 * direct "deliver to this tg_id" helper — and `Teleframe::send()` is the Q2
 * echo-elimination registry write, not a delivery. BotClient is the single
 * real in-tree send surface today; the route's `account` value is carried
 * through to the sender so a host can inject its own MTProto-aware sender
 * (a callable) without changing this channel.
 *
 * A null result never throws (Laravel channel convention: unrouteable or
 * unsendable notifications are silently skipped). Errors upstream (FloodWait,
 * HTTP failures) propagate as the engine defines them.
 */
final class TelegramChannel
{
    /**
     * @param (callable(array{account?: int|null, tg_id: int}, string, array<string, mixed>): mixed)|null $sender
     */
    public function __construct(
        private readonly mixed $sender = null,
    ) {
    }

    /**
     * @param object $notifiable
     */
    public function send($notifiable, Notification $notification): void
    {
        $payload = $this->payloadFrom($notification, $notifiable);
        if ($payload === null) {
            return;
        }

        $route = $this->routeFor($notifiable);
        if ($route === null) {
            return;
        }

        $sender = $this->sender ?? $this->defaultSenderFor();
        if ($sender === null) {
            return;
        }

        $sender($route, $payload['text'], $payload['options']);
    }

    /**
     * Q9 route resolution.
     *
     * @return array{account?: int|null, tg_id: int}|null
     */
    private function routeFor(object $notifiable): ?array
    {
        if (method_exists($notifiable, 'routeNotificationForTelegram')) {
            $route = $notifiable->routeNotificationForTelegram();
            if ($route === null || $route === [] || $route === false || $route === '') {
                return null;
            }
            if (is_int($route) || (is_string($route) && ctype_digit($route))) {
                return ['tg_id' => (int) $route, 'account' => null];
            }
            if (is_array($route) && isset($route['tg_id'])) {
                $account = $route['account'] ?? $route['account_or_default'] ?? null;

                return [
                    'tg_id' => (int) $route['tg_id'],
                    'account' => $account === null ? null : (int) $account,
                ];
            }

            return null;
        }

        // routeNotificationForTelegram not defined on the notifiable: default
        // sender fallback, recipient = the notifiable's binding.
        $binding = $this->bindingFor($notifiable);
        if ($binding === null) {
            return null;
        }

        return [
            'tg_id' => (int) $binding->tl_user_id,
            'account' => $binding->account_id,
        ];
    }

    /**
     * Q8 reverse-lookup: the notifiable's binding (its telegram identity).
     * Prefers a tenant-scoped row, then any orphan (no-account) row.
     */
    private function bindingFor(object $notifiable): ?TlUserBinding
    {
        if (! method_exists($notifiable, 'getKey')) {
            return null;
        }
        $userId = $notifiable->getKey();
        if ($userId === null) {
            return null;
        }

        $query = TlUserBinding::query()
            ->where('user_type', $notifiable::class)
            ->where('user_id', (int) $userId);

        $tenantRow = (clone $query)->where('account_id', '!=', null)->orderBy('account_id')->first();

        return $tenantRow ?? $query->where('account_id', null)->first();
    }

    /**
     * Extract the {text, options} send payload from the notification.
     *
     * @return array{text: string, options: array<string, mixed>}|null
     */
    private function payloadFrom(Notification $notification, object $notifiable): ?array
    {
        if (! method_exists($notification, 'toTelegram')) {
            return null;
        }

        $payload = $notification->toTelegram($notifiable);
        if ($payload === null) {
            return null;
        }

        if ($payload instanceof TelegramMessage) {
            $fields = $payload->toArray();
        } elseif (is_string($payload)) {
            $fields = ['text' => $payload];
        } elseif (is_array($payload)) {
            $fields = $payload;
        } else {
            return null;
        }

        $text = (string) ($fields['text'] ?? '');
        if ($text === '') {
            return null;
        }
        unset($fields['text']);

        return ['text' => $text, 'options' => $fields];
    }

    /**
     * Q9 configured default sender -> engine deliverer.
     *
     * @return (callable(array{account?: int|null, tg_id: int}, string, array<string, mixed>): mixed)|null
     */
    private function defaultSenderFor(): ?callable
    {
        $token = IdentityConfig::defaultSender();

        // Numeric default sender = an MTProto account id. No in-tree MTProto
        // deliverer maps an account id to a send, so the default deliverer
        // no-ops (never throws) and a host injects its own sender callable.
        if ($token !== null && ctype_digit($token)) {
            return null;
        }

        $token ??= IdentityConfig::botToken();
        if ($token === null) {
            return null;
        }

        return static function (array $route, string $text, array $options) use ($token): array {
            return (new BotClient($token))->sendMessage($route['tg_id'], $text, $options);
        };
    }
}