<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceNew_messages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceNew_encrypted_messages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceOther_updates;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceUsers;

/** Constructor model for updates.difference of updates.Difference (crc32 00f49ca0). */
final class TlUpdatesDifferenceDifference extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_updates_difference_difference';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'state' => 'string',
    ];

    public function newMessages(): HasMany
    {
        return $this->tlChild(TlUpdatesDifferenceDifferenceNew_messages::class);
    }
    public function newEncryptedMessages(): HasMany
    {
        return $this->tlChild(TlUpdatesDifferenceDifferenceNew_encrypted_messages::class);
    }
    public function otherUpdates(): HasMany
    {
        return $this->tlChild(TlUpdatesDifferenceDifferenceOther_updates::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlUpdatesDifferenceDifferenceChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlUpdatesDifferenceDifferenceUsers::class);
    }
}
