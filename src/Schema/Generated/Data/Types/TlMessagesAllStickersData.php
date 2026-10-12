<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.allStickers of messages.AllStickers.
 */
final class TlMessagesAllStickersData extends TlMessagesAllStickersAbstractData
{
    public function __construct(
    public int $hash,
    public array $sets,
    ) {
    }
}
