<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for messages.checkedHistoryImportPeer of messages.CheckedHistoryImportPeer (crc32 a24de717). */
final class TlMessagesCheckedHistoryImportPeerCheckedHistoryImportPeer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_checked_history_import_peer_check_abbf04f3a8aa';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'confirm_text' => 'string',
    ];
}
