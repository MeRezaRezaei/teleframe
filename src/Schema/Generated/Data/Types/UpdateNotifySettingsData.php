<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateNotifySettings of Update.
 */
final class UpdateNotifySettingsData extends TlUpdateAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlNotifyPeerAbstractData $peer,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerNotifySettingsAbstractData $notifySettings,
    ) {
    }
}
