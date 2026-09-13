<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.reorderPinnedForumTopics (crc32 0e7841f0), returns Updates. */
final class TlMessagesReorderPinnedForumTopicsData extends Data
{
    public const METHOD = 'messages.reorderPinnedForumTopics';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $force,
    public mixed $peer,
    public array $order,
    ) {
    }
}
