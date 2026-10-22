<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getForumTopics (crc32 3ba47bff), returns messages.ForumTopics. */
final class TlMessagesGetForumTopicsData extends Data
{
    public const METHOD = 'messages.getForumTopics';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public ?string $q,
    public int $offsetDate,
    public int $offsetId,
    public int $offsetTopic,
    public int $limit,
    ) {
    }
}
