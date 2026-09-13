<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for notificationSoundLocal of NotificationSound.
 */
final class NotificationSoundLocalData extends TlNotificationSoundAbstractData
{
    public function __construct(
    public string $title,
    public string $data,
    ) {
    }
}
