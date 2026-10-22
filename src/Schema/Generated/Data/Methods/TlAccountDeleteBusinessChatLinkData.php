<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.deleteBusinessChatLink (crc32 60073674), returns Bool. */
final class TlAccountDeleteBusinessChatLinkData extends Data
{
    public const METHOD = 'account.deleteBusinessChatLink';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $slug,
    ) {
    }
}
