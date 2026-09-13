<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateProfile (crc32 78515775), returns User. */
final class TlAccountUpdateProfileData extends Data
{
    public const METHOD = 'account.updateProfile';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?string $firstName,
    public ?string $lastName,
    public ?string $about,
    ) {
    }
}
