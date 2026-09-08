<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for chatlists.exportedChatlistInvite of chatlists.ExportedChatlistInvite (crc32 10e6e3a6). */
final class TlChatlistsExportedChatlistInviteExportedChatlistInvite extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_chatlists_exported_chatlist_invite_exporte_bc253d459003';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'filter' => 'string',
        'invite' => 'string',
    ];
}
