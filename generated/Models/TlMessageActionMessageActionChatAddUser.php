<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionChatAddUserUsers;

/** Constructor model for messageActionChatAddUser of MessageAction (crc32 15cefd00). */
final class TlMessageActionMessageActionChatAddUser extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_chat_add_user';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionChatAddUserUsers::class);
    }
}
