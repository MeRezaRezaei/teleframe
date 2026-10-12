<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.foundStickers of messages.FoundStickers.
 */
final class TlMessagesFoundStickersData extends TlMessagesFoundStickersAbstractData
{
    public function __construct(
    public int $flags,
    public ?int $nextOffset,
    public int $hash,
    public array $stickers,
    ) {
    }
}
