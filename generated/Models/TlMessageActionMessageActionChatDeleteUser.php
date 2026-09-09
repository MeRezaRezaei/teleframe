<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for messageActionChatDeleteUser of MessageAction (crc32 a43f30cc). */
final class TlMessageActionMessageActionChatDeleteUser extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_chat_delete_user';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'int',
    ];
}
