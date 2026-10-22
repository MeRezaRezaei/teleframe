<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\StateMachine;

/**
 * Telegram conversation patterns. Each models a distinct delivery/state shape.
 */
enum TelegramPattern: string
{
    case Channel = 'channel';
    case Group = 'group';
    case Private = 'private';
    case BotAssistant = 'bot_assistant';

    public function deliveryMode(): string
    {
        return match ($this) {
            self::Channel => 'one-to-many',
            self::Group => 'many-to-many',
            self::Private => 'one-to-one',
            self::BotAssistant => 'deduplicated',
        };
    }

    public function isOrdered(): bool
    {
        return $this === self::Channel;
    }

    public function isBroadcast(): bool
    {
        return $this === self::Group;
    }

    public function isStateful(): bool
    {
        return $this === self::Private;
    }

    public function isDeduplicated(): bool
    {
        return $this === self::BotAssistant;
    }

    public function isQueue(): bool
    {
        return $this === self::Channel;
    }

    public static function detect(array $context): self
    {
        $type = '';
        if (isset($context['type']) && is_string($context['type'])) {
            $type = strtolower($context['type']);
        }
        if (isset($context['chat']) && is_array($context['chat']) && isset($context['chat']['type']) && is_string($context['chat']['type'])) {
            $type = strtolower($context['chat']['type']);
        }
        if (str_contains($type, 'channel')) {
            return self::Channel;
        }
        if (str_contains($type, 'group') || str_contains($type, 'supergroup')) {
            return self::Group;
        }
        if (isset($context['from']) && is_array($context['from']) && ($context['from']['is_bot'] ?? false) === true) {
            return self::BotAssistant;
        }
        if (isset($context['is_bot_assistant']) && $context['is_bot_assistant'] === true) {
            return self::BotAssistant;
        }

        return self::Private;
    }

    public function label(): string
    {
        return match ($this) {
            self::Channel => 'Channel (notifyer/queue)',
            self::Group => 'Group (broadcast)',
            self::Private => 'Private (direct)',
            self::BotAssistant => 'BotAssistant (mirror)',
        };
    }
}
