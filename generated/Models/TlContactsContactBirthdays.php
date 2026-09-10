<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type contacts.ContactBirthdays (spec §4.1). */
final class TlContactsContactBirthdays extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_contacts_contact_birthdays_contact_birthdays';

    protected $guarded = [];
}
