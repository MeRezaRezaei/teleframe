<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.setCallRating (crc32 59ead627), returns Updates. */
final class TlPhoneSetCallRatingData extends Data
{
    public const METHOD = 'phone.setCallRating';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $userInitiative,
    public mixed $peer,
    public int $rating,
    public string $comment,
    ) {
    }
}
