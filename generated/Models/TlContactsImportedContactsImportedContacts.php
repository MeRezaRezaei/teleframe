<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsImportedContactsImportedContactsImported;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsImportedContactsImportedContactsPopular_invites;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsImportedContactsImportedContactsRetry_contacts;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsImportedContactsImportedContactsUsers;

/** Constructor model for contacts.importedContacts of contacts.ImportedContacts (crc32 77d01c3b). */
final class TlContactsImportedContactsImportedContacts extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_contacts_imported_contacts_imported_contacts';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function imported(): HasMany
    {
        return $this->tlChild(TlContactsImportedContactsImportedContactsImported::class);
    }
    public function popularInvites(): HasMany
    {
        return $this->tlChild(TlContactsImportedContactsImportedContactsPopular_invites::class);
    }
    public function retryContacts(): HasMany
    {
        return $this->tlChild(TlContactsImportedContactsImportedContactsRetry_contacts::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlContactsImportedContactsImportedContactsUsers::class);
    }
}
