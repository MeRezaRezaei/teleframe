<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for chatInviteImporter of ChatInviteImporter (crc32 8c5adfd9). */
final class TlChatInviteImporterChatInviteImporter extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_chat_invite_importer_chat_invite_importer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'requested' => 'bool',
        'via_chatlist' => 'bool',
        'user_id' => 'int',
        'date' => 'int',
        'about' => 'string',
        'approved_by' => 'int',
    ];
}
