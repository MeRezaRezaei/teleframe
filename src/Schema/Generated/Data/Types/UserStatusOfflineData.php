<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for userStatusOffline of UserStatus.
 */
final class UserStatusOfflineData extends TlUserStatusAbstractData
{
    public function __construct(
    public int $wasOnline,
    ) {
    }
}
