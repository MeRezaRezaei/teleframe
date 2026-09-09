<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for msgs_state_info of MsgsStateInfo (crc32 04deb57d). */
final class TlMsgsStateInfoMsgsStateInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_msgs_state_info_msgs_state_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'req_msg_id' => 'int',
        'info' => 'string',
    ];
}
