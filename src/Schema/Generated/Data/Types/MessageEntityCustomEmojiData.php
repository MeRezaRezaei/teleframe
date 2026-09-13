<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageEntityCustomEmoji of MessageEntity.
 */
final class MessageEntityCustomEmojiData extends TlMessageEntityAbstractData
{
    public function __construct(
    public int $offset,
    public int $length,
    public int $documentId,
    ) {
    }
}
