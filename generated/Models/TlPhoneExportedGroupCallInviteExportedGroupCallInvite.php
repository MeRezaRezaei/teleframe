<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for phone.exportedGroupCallInvite of phone.ExportedGroupCallInvite (crc32 204bd158). */
final class TlPhoneExportedGroupCallInviteExportedGroupCallInvite extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_phone_exported_group_call_invite_exported__9f796a593d9b';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'link' => 'string',
    ];
}
