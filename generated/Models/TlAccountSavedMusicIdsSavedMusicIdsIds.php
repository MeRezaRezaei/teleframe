<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param ids (table tl_account_saved_music_ids_saved_music_ids__ids). */
final class TlAccountSavedMusicIdsSavedMusicIdsIds extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_account_saved_music_ids_saved_music_ids__ids';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
