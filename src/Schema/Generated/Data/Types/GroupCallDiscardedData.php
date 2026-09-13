<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for groupCallDiscarded of GroupCall.
 */
final class GroupCallDiscardedData extends TlGroupCallAbstractData
{
    public function __construct(
    public int $id,
    public int $accessHash,
    public int $duration,
    ) {
    }
}
