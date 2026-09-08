<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getSavedMusicIds (crc32 e09d5faf), returns account.SavedMusicIds. */
final class TlAccountGetSavedMusicIdsData extends Data
{
    public const METHOD = 'account.getSavedMusicIds';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
