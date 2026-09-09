<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for sendMessageUploadAudioAction of SendMessageAction (crc32 f351d7ab). */
final class TlSendMessageActionSendMessageUploadAudioAction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_send_message_action_send_message_upload_audio_action';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'progress' => 'int',
    ];
}
