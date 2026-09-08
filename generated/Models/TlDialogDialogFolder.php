<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for dialogFolder of Dialog (crc32 71bd134c). */
final class TlDialogDialogFolder extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_dialog_dialog_folder';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'pinned' => 'bool',
        'folder' => 'string',
        'peer' => 'string',
        'top_message' => 'int',
        'unread_muted_peers_count' => 'int',
        'unread_unmuted_peers_count' => 'int',
        'unread_muted_messages_count' => 'int',
        'unread_unmuted_messages_count' => 'int',
    ];
}
