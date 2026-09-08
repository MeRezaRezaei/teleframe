<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for msg_new_detailed_info of MsgDetailedInfo (crc32 809db6df). */
final class TlMsgDetailedInfoMsgNewDetailedInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_msg_detailed_info_msg_new_detailed_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'answer_msg_id' => 'int',
        'bytes' => 'int',
        'status' => 'int',
    ];
}
