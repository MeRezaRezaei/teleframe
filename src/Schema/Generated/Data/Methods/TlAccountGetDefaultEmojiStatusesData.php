<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getDefaultEmojiStatuses (crc32 d6753386), returns account.EmojiStatuses. */
final class TlAccountGetDefaultEmojiStatusesData extends Data
{
    public const METHOD = 'account.getDefaultEmojiStatuses';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
