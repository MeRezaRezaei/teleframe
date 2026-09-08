<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.savedGifs of messages.SavedGifs.
 */
final class TlMessagesSavedGifsData extends TlMessagesSavedGifsAbstractData
{
    public function __construct(
    public int $hash,
    public array $gifs,
    ) {
    }
}
