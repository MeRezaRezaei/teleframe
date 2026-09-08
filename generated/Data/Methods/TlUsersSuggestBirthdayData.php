<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method users.suggestBirthday (crc32 fc533372), returns Updates. */
final class TlUsersSuggestBirthdayData extends Data
{
    public const METHOD = 'users.suggestBirthday';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $id,
    public mixed $birthday,
    ) {
    }
}
