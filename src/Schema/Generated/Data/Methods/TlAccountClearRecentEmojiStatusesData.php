<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.clearRecentEmojiStatuses (crc32 18201aae), returns Bool. */
final class TlAccountClearRecentEmojiStatusesData extends Data
{
    public const METHOD = 'account.clearRecentEmojiStatuses';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
