<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for emojiStatus of EmojiStatus.
 */
final class EmojiStatusData extends TlEmojiStatusAbstractData
{
    public function __construct(
    public int $flags,
    public int $documentId,
    public ?int $until,
    ) {
    }
}
