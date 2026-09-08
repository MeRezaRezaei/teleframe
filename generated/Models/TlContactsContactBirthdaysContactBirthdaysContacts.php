<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param contacts (table tl_contacts_contact_birthdays_contact_birthdays__contacts). */
final class TlContactsContactBirthdaysContactBirthdaysContacts extends TlAnchorModel
{
    protected $table = 'tl_contacts_contact_birthdays_contact_birthdays__contacts';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
