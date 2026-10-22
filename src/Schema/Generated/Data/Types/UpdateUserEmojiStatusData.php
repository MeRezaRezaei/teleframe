<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateUserEmojiStatus of Update.
 */
final class UpdateUserEmojiStatusData extends TlUpdateAbstractData
{
    public function __construct(
    public int $userId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlEmojiStatusAbstractData $emojiStatus,
    ) {
    }
}
