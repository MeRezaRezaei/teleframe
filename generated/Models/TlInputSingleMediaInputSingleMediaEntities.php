<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param entities (table tl_input_single_media_input_single_media__entities). */
final class TlInputSingleMediaInputSingleMediaEntities extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_single_media_input_single_media__entities';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
