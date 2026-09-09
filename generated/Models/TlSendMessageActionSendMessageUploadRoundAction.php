<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for sendMessageUploadRoundAction of SendMessageAction (crc32 243e1c66). */
final class TlSendMessageActionSendMessageUploadRoundAction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_send_message_action_send_message_upload_round_action';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'progress' => 'int',
    ];
}
