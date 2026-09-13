<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.importWebTokenAuthorization (crc32 2db873a9), returns auth.Authorization. */
final class TlAuthImportWebTokenAuthorizationData extends Data
{
    public const METHOD = 'auth.importWebTokenAuthorization';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $apiId,
    public string $apiHash,
    public string $webAuthToken,
    ) {
    }
}
