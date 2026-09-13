<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getRecentStickers (crc32 9da9403b), returns messages.RecentStickers. */
final class TlMessagesGetRecentStickersData extends Data
{
    public const METHOD = 'messages.getRecentStickers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $attached,
    public int $hash,
    ) {
    }
}
