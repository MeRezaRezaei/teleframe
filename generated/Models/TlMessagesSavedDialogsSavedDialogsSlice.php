<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSavedDialogsSavedDialogsSliceChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSavedDialogsSavedDialogsSliceDialogs;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSavedDialogsSavedDialogsSliceMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSavedDialogsSavedDialogsSliceUsers;

/** Constructor model for messages.savedDialogsSlice of messages.SavedDialogs (crc32 44ba9dd9). */
final class TlMessagesSavedDialogsSavedDialogsSlice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_saved_dialogs_saved_dialogs_slice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
    ];

    public function dialogs(): HasMany
    {
        return $this->tlChild(TlMessagesSavedDialogsSavedDialogsSliceDialogs::class);
    }
    public function messages(): HasMany
    {
        return $this->tlChild(TlMessagesSavedDialogsSavedDialogsSliceMessages::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesSavedDialogsSavedDialogsSliceChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesSavedDialogsSavedDialogsSliceUsers::class);
    }
}
