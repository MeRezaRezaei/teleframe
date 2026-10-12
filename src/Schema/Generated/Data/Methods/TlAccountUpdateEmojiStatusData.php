<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateEmojiStatus (crc32 fbd3de6b), returns Bool. */
final class TlAccountUpdateEmojiStatusData extends Data
{
    public const METHOD = 'account.updateEmojiStatus';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $emojiStatus,
    ) {
    }
}
