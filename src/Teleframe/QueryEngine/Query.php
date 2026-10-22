<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\QueryEngine;

final class Query
{
    public function __construct(
        public string $type,
        public array $params = [],
        public ?string $correlationId = null,
    ) {}

    public static function channelEvent(int $channelId, string $eventType = 'new_post'): self
    {
        return new self(
            type: 'channel_event',
            params: [
                'channel_id' => $channelId,
                'event_type' => $eventType,
            ],
        );
    }

    public static function businessMessage(int $connectionId, string $eventType = 'new_message'): self
    {
        return new self(
            type: 'business_message',
            params: [
                'connection_id' => $connectionId,
                'event_type' => $eventType,
            ],
        );
    }

    public static function userMessage(int $userId, int $chatId, string $eventType = 'new_message'): self
    {
        return new self(
            type: 'user_message',
            params: [
                'user_id' => $userId,
                'chat_id' => $chatId,
                'event_type' => $eventType,
            ],
        );
    }

    public static function callbackQuery(int $userId, string $data): self
    {
        return new self(
            type: 'callback_query',
            params: [
                'user_id' => $userId,
                'data' => $data,
            ],
        );
    }
}
