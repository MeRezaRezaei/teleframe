<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilter;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedChatlistInvite;

/** Constructor model for chatlists.exportedChatlistInvite of chatlists.ExportedChatlistInvite (crc32 10e6e3a6). */
final class TlChatlistsExportedChatlistInviteExportedChatlistInvite extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_chatlists_exported_chatlist_invite_exporte_bc253d459003';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function filter(): BelongsTo
    {
        return $this->belongsTo(TlDialogFilter::class, 'filter');
    }
    public function invite(): BelongsTo
    {
        return $this->belongsTo(TlExportedChatlistInvite::class, 'invite');
    }
}
