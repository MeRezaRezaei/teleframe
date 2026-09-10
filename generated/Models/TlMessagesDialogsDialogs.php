<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDialogsDialogsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDialogsDialogsDialogs;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDialogsDialogsMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDialogsDialogsUsers;

/** Constructor model for messages.dialogs of messages.Dialogs (crc32 15ba6c40). */
final class TlMessagesDialogsDialogs extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_dialogs_dialogs';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function dialogs(): HasMany
    {
        return $this->tlChild(TlMessagesDialogsDialogsDialogs::class);
    }
    public function messages(): HasMany
    {
        return $this->tlChild(TlMessagesDialogsDialogsMessages::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesDialogsDialogsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesDialogsDialogsUsers::class);
    }
}
