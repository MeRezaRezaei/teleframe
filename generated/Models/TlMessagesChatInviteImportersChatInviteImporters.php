<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesChatInviteImportersChatInviteImportersImporters;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesChatInviteImportersChatInviteImportersUsers;

/** Constructor model for messages.chatInviteImporters of messages.ChatInviteImporters (crc32 81b6b00a). */
final class TlMessagesChatInviteImportersChatInviteImporters extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_chat_invite_importers_chat_invite_importers';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
    ];

    public function importers(): HasMany
    {
        return $this->tlChild(TlMessagesChatInviteImportersChatInviteImportersImporters::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesChatInviteImportersChatInviteImportersUsers::class);
    }
}
