<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for savedPhoneContact of SavedContact.
 */
final class SavedPhoneContactData extends TlSavedContactAbstractData
{
    public function __construct(
    public string $phone,
    public string $firstName,
    public string $lastName,
    public int $date,
    ) {
    }
}
