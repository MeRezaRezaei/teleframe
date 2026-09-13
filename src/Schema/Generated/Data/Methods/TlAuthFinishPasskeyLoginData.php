<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.finishPasskeyLogin (crc32 9857ad07), returns auth.Authorization. */
final class TlAuthFinishPasskeyLoginData extends Data
{
    public const METHOD = 'auth.finishPasskeyLogin';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $credential,
    public ?int $fromDcId,
    public ?int $fromAuthKeyId,
    ) {
    }
}
