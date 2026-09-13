<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.sendReaction (crc32 7fd736b2), returns Updates. */
final class TlStoriesSendReactionData extends Data
{
    public const METHOD = 'stories.sendReaction';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $addToRecent,
    public mixed $peer,
    public int $storyId,
    public mixed $reaction,
    ) {
    }
}
