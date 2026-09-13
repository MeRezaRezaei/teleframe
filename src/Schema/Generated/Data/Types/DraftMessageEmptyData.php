<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for draftMessageEmpty of DraftMessage.
 */
final class DraftMessageEmptyData extends TlDraftMessageAbstractData
{
    public function __construct(
    public int $flags,
    public ?int $date,
    ) {
    }
}
