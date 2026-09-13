<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method users.getSavedMusic (crc32 788d7fe3), returns users.SavedMusic. */
final class TlUsersGetSavedMusicData extends Data
{
    public const METHOD = 'users.getSavedMusic';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $id,
    public int $offset,
    public int $limit,
    public int $hash,
    ) {
    }
}
