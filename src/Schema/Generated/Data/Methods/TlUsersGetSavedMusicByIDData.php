<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method users.getSavedMusicByID (crc32 7573a4e9), returns users.SavedMusic. */
final class TlUsersGetSavedMusicByIDData extends Data
{
    public const METHOD = 'users.getSavedMusicByID';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $id,
    public array $documents,
    ) {
    }
}
