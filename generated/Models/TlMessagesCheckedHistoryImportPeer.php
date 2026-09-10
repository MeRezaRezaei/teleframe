<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type messages.CheckedHistoryImportPeer (spec §4.1). */
final class TlMessagesCheckedHistoryImportPeer extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_checked_history_import_peer_check_abbf04f3a8aa';

    protected $guarded = [];
}
