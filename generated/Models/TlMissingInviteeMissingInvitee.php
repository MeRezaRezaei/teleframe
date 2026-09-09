<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for missingInvitee of MissingInvitee (crc32 628c9224). */
final class TlMissingInviteeMissingInvitee extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_missing_invitee_missing_invitee';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'premium_would_allow_invite' => 'bool',
        'premium_required_for_pm' => 'bool',
        'user_id' => 'int',
    ];
}
