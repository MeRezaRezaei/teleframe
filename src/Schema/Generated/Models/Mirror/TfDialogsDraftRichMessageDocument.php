<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfDialogsDraftRichMessageDocument extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_dialogs_draft_rich_message_documents';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_id' => 'integer',
        'id' => 'integer',
        'access_hash' => 'integer',
        'date' => 'integer',
        'size' => 'integer',
        'dc_id' => 'integer',
    ];
}
