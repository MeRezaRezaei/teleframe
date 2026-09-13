<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.importBotAuthorization (crc32 67a3ff2c), returns auth.Authorization. */
final class TlAuthImportBotAuthorizationData extends Data
{
    public const METHOD = 'auth.importBotAuthorization';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public int $apiId,
    public string $apiHash,
    public string $botAuthToken,
    ) {
    }
}
