<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for account.savedRingtone of account.SavedRingtone (crc32 b7263f6d). */
final class TlAccountSavedRingtoneSavedRingtone extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_saved_ringtone_saved_ringtone';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
