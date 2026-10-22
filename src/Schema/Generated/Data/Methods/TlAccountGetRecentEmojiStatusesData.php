<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getRecentEmojiStatuses (crc32 0f578105), returns account.EmojiStatuses. */
final class TlAccountGetRecentEmojiStatusesData extends Data
{
    public const METHOD = 'account.getRecentEmojiStatuses';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
