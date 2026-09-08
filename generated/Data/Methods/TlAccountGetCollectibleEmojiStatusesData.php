<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getCollectibleEmojiStatuses (crc32 2e7b4543), returns account.EmojiStatuses. */
final class TlAccountGetCollectibleEmojiStatusesData extends Data
{
    public const METHOD = 'account.getCollectibleEmojiStatuses';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
