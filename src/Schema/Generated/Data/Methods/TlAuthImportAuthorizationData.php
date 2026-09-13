<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.importAuthorization (crc32 a57a7dad), returns auth.Authorization. */
final class TlAuthImportAuthorizationData extends Data
{
    public const METHOD = 'auth.importAuthorization';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $id,
    public string $bytes,
    ) {
    }
}
