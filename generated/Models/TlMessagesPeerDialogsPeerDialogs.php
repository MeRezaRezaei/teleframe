<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerDialogsPeerDialogsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerDialogsPeerDialogsDialogs;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerDialogsPeerDialogsMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerDialogsPeerDialogsUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesState;

/** Constructor model for messages.peerDialogs of messages.PeerDialogs (crc32 3371c354). */
final class TlMessagesPeerDialogsPeerDialogs extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_peer_dialogs_peer_dialogs';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
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

    public function state(): BelongsTo
    {
        return $this->belongsTo(TlUpdatesState::class, 'state');
    }
}
