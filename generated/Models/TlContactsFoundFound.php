<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsFoundFoundChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsFoundFoundMy_results;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsFoundFoundResults;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsFoundFoundUsers;

/** Constructor model for contacts.found of contacts.Found (crc32 b3134d9d). */
final class TlContactsFoundFound extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_contacts_found_found';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function myResults(): HasMany
    {
        return $this->tlChild(TlContactsFoundFoundMy_results::class);
    }
    public function results(): HasMany
    {
        return $this->tlChild(TlContactsFoundFoundResults::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlContactsFoundFoundChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlContactsFoundFoundUsers::class);
    }
}
