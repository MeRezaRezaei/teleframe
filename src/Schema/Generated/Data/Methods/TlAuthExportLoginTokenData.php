<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.exportLoginToken (crc32 b7e085fe), returns auth.LoginToken. */
final class TlAuthExportLoginTokenData extends Data
{
    public const METHOD = 'auth.exportLoginToken';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $apiId,
    public string $apiHash,
    public array $exceptIds,
    ) {
    }
}
