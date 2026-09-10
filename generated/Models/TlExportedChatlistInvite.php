<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsExportedChatlistInviteExportedChatlistInvite;

/** Anchor model for TL type ExportedChatlistInvite (spec §4.1). */
final class TlExportedChatlistInvite extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_exported_chatlist_invite_exported_chatlist_invite';

    protected $guarded = [];

    public function invite(): HasMany
    {
        return $this->hasMany(TlChatlistsExportedChatlistInviteExportedChatlistInvite::class, 'invite');
    }
}
