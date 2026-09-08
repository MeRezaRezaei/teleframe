<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerDialogsPeerDialogsDialogs;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerDialogsPeerDialogsMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerDialogsPeerDialogsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerDialogsPeerDialogsUsers;

/** Constructor model for messages.peerDialogs of messages.PeerDialogs (crc32 3371c354). */
final class TlMessagesPeerDialogsPeerDialogs extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_peer_dialogs_peer_dialogs';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'state' => 'string',
    ];

    public function dialogs(): HasMany
    {
        return $this->tlChild(TlMessagesPeerDialogsPeerDialogsDialogs::class);
    }
    public function messages(): HasMany
    {
        return $this->tlChild(TlMessagesPeerDialogsPeerDialogsMessages::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesPeerDialogsPeerDialogsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesPeerDialogsPeerDialogsUsers::class);
    }
}
