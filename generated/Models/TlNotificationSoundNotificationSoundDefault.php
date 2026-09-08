<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for notificationSoundDefault of NotificationSound (crc32 97e8bebe). */
final class TlNotificationSoundNotificationSoundDefault extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_notification_sound_notification_sound_default';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
