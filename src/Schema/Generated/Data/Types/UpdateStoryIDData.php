<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateStoryID of Update.
 */
final class UpdateStoryIDData extends TlUpdateAbstractData
{
    public function __construct(
    public int $id,
    public int $randomId,
    ) {
    }
}
