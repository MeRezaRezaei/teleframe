<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getAllSecureValues (crc32 b288bc7d), returns Vector<SecureValue>. */
final class TlAccountGetAllSecureValuesData extends Data
{
    public const METHOD = 'account.getAllSecureValues';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
