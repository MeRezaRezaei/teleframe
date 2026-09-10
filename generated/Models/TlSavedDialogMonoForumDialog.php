<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDraftMessage;

/** Constructor model for monoForumDialog of SavedDialog (crc32 64407ea7). */
final class TlSavedDialogMonoForumDialog extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_saved_dialog_mono_forum_dialog';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'unread_mark' => 'bool',
        'nopaid_messages_exception' => 'bool',
        'top_message' => 'int',
        'read_inbox_max_id' => 'int',
        'read_outbox_max_id' => 'int',
        'unread_count' => 'int',
        'unread_reactions_count' => 'int',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(TlDraftMessage::class, 'draft');
    }
}
