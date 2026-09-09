<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for msg_detailed_info of MsgDetailedInfo (crc32 276d3ec6). */
final class TlMsgDetailedInfoMsgDetailedInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_msg_detailed_info_msg_detailed_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'msg_id' => 'int',
        'answer_msg_id' => 'int',
        'bytes' => 'int',
        'status' => 'int',
    ];
}
