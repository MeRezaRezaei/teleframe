<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.archivedStickers of messages.ArchivedStickers.
 */
final class TlMessagesArchivedStickersData extends TlMessagesArchivedStickersAbstractData
{
    public function __construct(
    public int $count,
    public array $sets,
    ) {
    }
}
