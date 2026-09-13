<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for contacts.contacts of contacts.Contacts.
 */
final class TlContactsContactsData extends TlContactsContactsAbstractData
{
    public function __construct(
    public array $contacts,
    public int $savedCount,
    public array $users,
    ) {
    }
}
