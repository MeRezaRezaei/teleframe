<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputDialogPeerFolder of InputDialogPeer (crc32 64600527). */
final class TlInputDialogPeerInputDialogPeerFolder extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_dialog_peer_input_dialog_peer_folder';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'folder_id' => 'int',
    ];
}
