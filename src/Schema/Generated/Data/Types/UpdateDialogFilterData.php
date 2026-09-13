<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateDialogFilter of Update.
 */
final class UpdateDialogFilterData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public int $id,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDialogFilterAbstractData $filter,
    ) {
    }
}
