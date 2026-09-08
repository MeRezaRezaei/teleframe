<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.toggleSponsoredMessages (crc32 b9d9a38d), returns Bool. */
final class TlAccountToggleSponsoredMessagesData extends Data
{
    public const METHOD = 'account.toggleSponsoredMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $enabled,
    ) {
    }
}
