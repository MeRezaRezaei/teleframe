<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for contactBirthday of ContactBirthday.
 */
final class ContactBirthdayData extends TlContactBirthdayAbstractData
{
    public function __construct(
    public int $contactId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBirthdayAbstractData $birthday,
    ) {
    }
}
