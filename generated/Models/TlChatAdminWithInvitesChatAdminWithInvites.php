<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for chatAdminWithInvites of ChatAdminWithInvites (crc32 f2ecef23). */
final class TlChatAdminWithInvitesChatAdminWithInvites extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_chat_admin_with_invites_chat_admin_with_invites';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'admin_id' => 'int',
        'invites_count' => 'int',
        'revoked_invites_count' => 'int',
    ];
}
