<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogDialogFolder;

/** Anchor model for TL type Folder (spec §4.1). */
final class TlFolder extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_folder_folder';

    protected $guarded = [];

    public function folder(): HasMany
    {
        return $this->hasMany(TlDialogDialogFolder::class, 'folder');
    }
}
