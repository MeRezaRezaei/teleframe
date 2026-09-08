<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method invokeWithGooglePlayIntegrity (crc32 1df92984), returns X. */
final class InvokeWithGooglePlayIntegrityData extends Data
{
    public const METHOD = 'invokeWithGooglePlayIntegrity';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed ${X,
    public string $nonce,
    public string $token,
    public mixed $query,
    ) {
    }
}
