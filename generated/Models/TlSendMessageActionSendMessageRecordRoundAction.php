<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for sendMessageRecordRoundAction of SendMessageAction (crc32 88f27fbc). */
final class TlSendMessageActionSendMessageRecordRoundAction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_send_message_action_send_message_record_round_action';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
