<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceSliceChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceSliceNew_encrypted_messages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceSliceNew_messages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceSliceOther_updates;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceSliceUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesState;

/** Constructor model for updates.differenceSlice of updates.Difference (crc32 a8fb1981). */
final class TlUpdatesDifferenceDifferenceSlice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_updates_difference_difference_slice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function newMessages(): HasMany
    {
        return $this->tlChild(TlUpdatesDifferenceDifferenceSliceNew_messages::class);
    }
    public function newEncryptedMessages(): HasMany
    {
        return $this->tlChild(TlUpdatesDifferenceDifferenceSliceNew_encrypted_messages::class);
    }
    public function otherUpdates(): HasMany
    {
        return $this->tlChild(TlUpdatesDifferenceDifferenceSliceOther_updates::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlUpdatesDifferenceDifferenceSliceChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlUpdatesDifferenceDifferenceSliceUsers::class);
    }

    public function intermediateState(): BelongsTo
    {
        return $this->belongsTo(TlUpdatesState::class, 'intermediate_state');
    }
}
