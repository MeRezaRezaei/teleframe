<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param msg_ids (table tl_msgs_all_info_msgs_all_info__msg_ids). */
final class TlMsgsAllInfoMsgsAllInfoMsg_ids extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_msgs_all_info_msgs_all_info__msg_ids';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
