<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.createBusinessChatLink (crc32 8851e68e), returns BusinessChatLink. */
final class TlAccountCreateBusinessChatLinkData extends Data
{
    public const METHOD = 'account.createBusinessChatLink';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $link,
    ) {
    }
}
