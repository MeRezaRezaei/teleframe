<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for dialogFilterSuggested of DialogFilterSuggested.
 */
final class DialogFilterSuggestedData extends TlDialogFilterSuggestedAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDialogFilterAbstractData $filter,
    public string $description,
    ) {
    }
}
