<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.getGroupCallStars (crc32 6f636302), returns phone.GroupCallStars. */
final class TlPhoneGetGroupCallStarsData extends Data
{
    public const METHOD = 'phone.getGroupCallStars';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    ) {
    }
}
