<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for bad_msg_notification of BadMsgNotification (crc32 a7eff811). */
final class TlBadMsgNotificationBadMsgNotification extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_bad_msg_notification_bad_msg_notification';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'bad_msg_id' => 'int',
        'bad_msg_seqno' => 'int',
        'error_code' => 'int',
    ];
}
