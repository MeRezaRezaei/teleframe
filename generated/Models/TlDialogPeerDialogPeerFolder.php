<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for dialogPeerFolder of DialogPeer (crc32 514519e2). */
final class TlDialogPeerDialogPeerFolder extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_dialog_peer_dialog_peer_folder';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'folder_id' => 'int',
    ];
}
