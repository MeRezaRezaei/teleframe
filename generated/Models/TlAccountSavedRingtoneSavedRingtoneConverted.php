<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;

/** Constructor model for account.savedRingtoneConverted of account.SavedRingtone (crc32 1f307eb7). */
final class TlAccountSavedRingtoneSavedRingtoneConverted extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_saved_ringtone_saved_ringtone_converted';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'document');
    }
}
