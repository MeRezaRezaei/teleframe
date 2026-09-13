<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfDialogsDraftRichMessageBlocksRowsCell extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_dialogs_draft_rich_message_blocks_rows_cells';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_id' => 'integer',
        'header' => 'boolean',
        'align_center' => 'boolean',
        'align_right' => 'boolean',
        'valign_middle' => 'boolean',
        'valign_bottom' => 'boolean',
        'colspan' => 'integer',
        'rowspan' => 'integer',
    ];
}
