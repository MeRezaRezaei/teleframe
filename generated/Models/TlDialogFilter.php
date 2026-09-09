<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsExportedChatlistInviteExportedChatlistInvite;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterSuggestedDialogFilterSuggested;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDialogFilter;

/** Anchor model for TL type DialogFilter (spec §4.1). */
final class TlDialogFilter extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_dialog_filter';

    protected $guarded = [];

    public function filter(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateDialogFilter::class, 'filter');
    }
    public function filterChatlistsExportedChatlistInvite(): HasMany
    {
        return $this->hasMany(TlChatlistsExportedChatlistInviteExportedChatlistInvite::class, 'filter');
    }
    public function filterDialogFilterSuggested(): HasMany
    {
        return $this->hasMany(TlDialogFilterSuggestedDialogFilterSuggested::class, 'filter');
    }
}
