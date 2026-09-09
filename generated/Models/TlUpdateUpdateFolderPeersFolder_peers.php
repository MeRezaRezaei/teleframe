<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param folder_peers (table tl_update_update_folder_peers__folder_peers). */
final class TlUpdateUpdateFolderPeersFolder_peers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_update_update_folder_peers__folder_peers';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
