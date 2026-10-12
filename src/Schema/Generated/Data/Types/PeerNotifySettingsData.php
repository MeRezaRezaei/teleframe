<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for peerNotifySettings of PeerNotifySettings.
 */
final class PeerNotifySettingsData extends TlPeerNotifySettingsAbstractData
{
    public function __construct(
    public int $flags,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $showPreviews,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $silent,
    public ?int $muteUntil,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlNotificationSoundAbstractData $iosSound,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlNotificationSoundAbstractData $androidSound,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlNotificationSoundAbstractData $otherSound,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $storiesMuted,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $storiesHideSender,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlNotificationSoundAbstractData $storiesIosSound,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlNotificationSoundAbstractData $storiesAndroidSound,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlNotificationSoundAbstractData $storiesOtherSound,
    ) {
    }
}
