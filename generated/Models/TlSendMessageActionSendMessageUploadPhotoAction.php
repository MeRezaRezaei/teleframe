<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for sendMessageUploadPhotoAction of SendMessageAction (crc32 d1d34a26). */
final class TlSendMessageActionSendMessageUploadPhotoAction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_send_message_action_send_message_upload_photo_action';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'progress' => 'int',
    ];
}
