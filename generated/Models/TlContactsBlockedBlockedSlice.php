<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsBlockedBlockedSliceBlocked;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsBlockedBlockedSliceChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsBlockedBlockedSliceUsers;

/** Constructor model for contacts.blockedSlice of contacts.Blocked (crc32 e1664194). */
final class TlContactsBlockedBlockedSlice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_contacts_blocked_blocked_slice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
    ];

    public function blocked(): HasMany
    {
        return $this->tlChild(TlContactsBlockedBlockedSliceBlocked::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlContactsBlockedBlockedSliceChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlContactsBlockedBlockedSliceUsers::class);
    }
}
