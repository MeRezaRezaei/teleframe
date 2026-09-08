<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsContactsContactsContacts;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsContactsContactsUsers;

/** Constructor model for contacts.contacts of contacts.Contacts (crc32 eae87e42). */
final class TlContactsContactsContacts extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_contacts_contacts_contacts';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'saved_count' => 'int',
    ];

    public function contacts(): HasMany
    {
        return $this->tlChild(TlContactsContactsContactsContacts::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlContactsContactsContactsUsers::class);
    }
}
