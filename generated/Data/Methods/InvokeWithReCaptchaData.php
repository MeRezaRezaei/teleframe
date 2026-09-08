<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method invokeWithReCaptcha (crc32 adbb0f94), returns X. */
final class InvokeWithReCaptchaData extends Data
{
    public const METHOD = 'invokeWithReCaptcha';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed ${X,
    public string $token,
    public mixed $query,
    ) {
    }
}
