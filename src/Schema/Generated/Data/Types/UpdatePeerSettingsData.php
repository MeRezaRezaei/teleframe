<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updatePeerSettings of Update.
 */
final class UpdatePeerSettingsData extends TlUpdateAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerSettingsAbstractData $settings,
    ) {
    }
}
