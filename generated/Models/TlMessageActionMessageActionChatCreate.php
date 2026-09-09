<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionChatCreateUsers;

/** Constructor model for messageActionChatCreate of MessageAction (crc32 bd47cbad). */
final class TlMessageActionMessageActionChatCreate extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_chat_create';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'title' => 'string',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionChatCreateUsers::class);
    }
}
