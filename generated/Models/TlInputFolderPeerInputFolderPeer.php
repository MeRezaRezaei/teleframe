<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputFolderPeer of InputFolderPeer (crc32 fbd2c296). */
final class TlInputFolderPeerInputFolderPeer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_folder_peer_input_folder_peer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
        'folder_id' => 'int',
    ];
}
