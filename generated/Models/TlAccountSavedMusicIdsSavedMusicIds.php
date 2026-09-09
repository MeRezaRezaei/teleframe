<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountSavedMusicIdsSavedMusicIdsIds;

/** Constructor model for account.savedMusicIds of account.SavedMusicIds (crc32 998d6636). */
final class TlAccountSavedMusicIdsSavedMusicIds extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_saved_music_ids_saved_music_ids';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function ids(): HasMany
    {
        return $this->tlChild(TlAccountSavedMusicIdsSavedMusicIdsIds::class);
    }
}
