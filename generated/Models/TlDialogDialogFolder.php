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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlFolder;

/** Constructor model for dialogFolder of Dialog (crc32 71bd134c). */
final class TlDialogDialogFolder extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_dialog_dialog_folder';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'pinned' => 'bool',
        'top_message' => 'int',
        'unread_muted_peers_count' => 'int',
        'unread_unmuted_peers_count' => 'int',
        'unread_muted_messages_count' => 'int',
        'unread_unmuted_messages_count' => 'int',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(TlFolder::class, 'folder');
    }
}
