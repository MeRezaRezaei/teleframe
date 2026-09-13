<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.autoDownloadSettings of account.AutoDownloadSettings.
 */
final class TlAccountAutoDownloadSettingsData extends TlAccountAutoDownloadSettingsAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlAutoDownloadSettingsAbstractData $low,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlAutoDownloadSettingsAbstractData $medium,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlAutoDownloadSettingsAbstractData $high,
    ) {
    }
}
