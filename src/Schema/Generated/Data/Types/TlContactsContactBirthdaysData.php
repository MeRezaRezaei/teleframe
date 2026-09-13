<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for contacts.contactBirthdays of contacts.ContactBirthdays.
 */
final class TlContactsContactBirthdaysData extends TlContactsContactBirthdaysAbstractData
{
    public function __construct(
    public array $contacts,
    public array $users,
    ) {
    }
}
