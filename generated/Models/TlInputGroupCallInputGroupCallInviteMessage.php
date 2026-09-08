<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputGroupCallInviteMessage of InputGroupCall (crc32 8c10603f). */
final class TlInputGroupCallInputGroupCallInviteMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_group_call_input_group_call_invite_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'msg_id' => 'int',
    ];
}
