<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for notificationSoundNone of NotificationSound (crc32 6f0c34df). */
final class TlNotificationSoundNotificationSoundNone extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_notification_sound_notification_sound_none';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
