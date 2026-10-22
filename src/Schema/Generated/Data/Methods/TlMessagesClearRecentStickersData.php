<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.clearRecentStickers (crc32 8999602d), returns Bool. */
final class TlMessagesClearRecentStickersData extends Data
{
    public const METHOD = 'messages.clearRecentStickers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $attached,
    ) {
    }
}
