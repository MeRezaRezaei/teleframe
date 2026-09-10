<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsBlockedBlockedBlocked;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsBlockedBlockedChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsBlockedBlockedUsers;

/** Constructor model for contacts.blocked of contacts.Blocked (crc32 0ade1591). */
final class TlContactsBlockedBlocked extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_contacts_blocked_blocked';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function blocked(): HasMany
    {
        return $this->tlChild(TlContactsBlockedBlockedBlocked::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlContactsBlockedBlockedChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlContactsBlockedBlockedUsers::class);
    }
}
