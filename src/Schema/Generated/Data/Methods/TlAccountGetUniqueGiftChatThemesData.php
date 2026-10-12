<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getUniqueGiftChatThemes (crc32 e42ce9c9), returns account.ChatThemes. */
final class TlAccountGetUniqueGiftChatThemesData extends Data
{
    public const METHOD = 'account.getUniqueGiftChatThemes';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $offset,
    public int $limit,
    public int $hash,
    ) {
    }
}
