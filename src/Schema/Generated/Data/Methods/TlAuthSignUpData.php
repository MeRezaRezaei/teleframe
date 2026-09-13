<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.signUp (crc32 aac7b717), returns auth.Authorization. */
final class TlAuthSignUpData extends Data
{
    public const METHOD = 'auth.signUp';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $noJoinedNotifications,
    public string $phoneNumber,
    public string $phoneCodeHash,
    public string $firstName,
    public string $lastName,
    ) {
    }
}
