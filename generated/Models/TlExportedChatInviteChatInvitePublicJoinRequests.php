<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for chatInvitePublicJoinRequests of ExportedChatInvite (crc32 ed107ab7). */
final class TlExportedChatInviteChatInvitePublicJoinRequests extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_exported_chat_invite_chat_invite_public_join_requests';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
