<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method invokeWithApnsSecret (crc32 0dae54f8), returns X. */
final class InvokeWithApnsSecretData extends Data
{
    public const METHOD = 'invokeWithApnsSecret';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed ${X,
    public string $nonce,
    public string $secret,
    public mixed $query,
    ) {
    }
}
