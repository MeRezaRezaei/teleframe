<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.setPrivacy (crc32 c9f81ce8), returns account.PrivacyRules. */
final class TlAccountSetPrivacyData extends Data
{
    public const METHOD = 'account.setPrivacy';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $key,
    public array $rules,
    ) {
    }
}
