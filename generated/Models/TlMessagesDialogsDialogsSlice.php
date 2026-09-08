<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDialogsDialogsSliceDialogs;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDialogsDialogsSliceMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDialogsDialogsSliceChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDialogsDialogsSliceUsers;

/** Constructor model for messages.dialogsSlice of messages.Dialogs (crc32 71e094f3). */
final class TlMessagesDialogsDialogsSlice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_dialogs_dialogs_slice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
    ];

    public function dialogs(): HasMany
    {
        return $this->tlChild(TlMessagesDialogsDialogsSliceDialogs::class);
    }
    public function messages(): HasMany
    {
        return $this->tlChild(TlMessagesDialogsDialogsSliceMessages::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesDialogsDialogsSliceChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesDialogsDialogsSliceUsers::class);
    }
}
