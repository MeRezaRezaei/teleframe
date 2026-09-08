<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.updateUserEmojiStatus (crc32 ed9f30c5), returns Bool. */
final class TlBotsUpdateUserEmojiStatusData extends Data
{
    public const METHOD = 'bots.updateUserEmojiStatus';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $userId,
    public mixed $emojiStatus,
    ) {
    }
}
