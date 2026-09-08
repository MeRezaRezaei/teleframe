<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionInviteToGroupCallUsers;

/** Constructor model for messageActionInviteToGroupCall of MessageAction (crc32 502f92f7). */
final class TlMessageActionMessageActionInviteToGroupCall extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_action_message_action_invite_to_group_call';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'call' => 'string',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionInviteToGroupCallUsers::class);
    }
}
