<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.signIn (crc32 8d52a951), returns auth.Authorization. */
final class TlAuthSignInData extends Data
{
    public const METHOD = 'auth.signIn';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public string $phoneNumber,
    public string $phoneCodeHash,
    public ?string $phoneCode,
    public mixed $emailVerification,
    ) {
    }
}
