<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsContactBirthdaysContactBirthdaysContacts;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsContactBirthdaysContactBirthdaysUsers;

/** Constructor model for contacts.contactBirthdays of contacts.ContactBirthdays (crc32 114ff30d). */
final class TlContactsContactBirthdaysContactBirthdays extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_contacts_contact_birthdays_contact_birthdays';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function contacts(): HasMany
    {
        return $this->tlChild(TlContactsContactBirthdaysContactBirthdaysContacts::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlContactsContactBirthdaysContactBirthdaysUsers::class);
    }
}
