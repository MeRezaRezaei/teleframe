<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type phone.ExportedGroupCallInvite (spec §4.1). */
final class TlPhoneExportedGroupCallInvite extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_phone_exported_group_call_invite_exported__9f796a593d9b';

    protected $guarded = [];
}
