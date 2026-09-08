<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getForumTopicsByID (crc32 af0a4a08), returns messages.ForumTopics. */
final class TlMessagesGetForumTopicsByIDData extends Data
{
    public const METHOD = 'messages.getForumTopicsByID';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public array $topics,
    ) {
    }
}
