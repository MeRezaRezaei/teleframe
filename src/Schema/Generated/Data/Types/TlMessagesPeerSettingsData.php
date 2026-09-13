<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.peerSettings of messages.PeerSettings.
 */
final class TlMessagesPeerSettingsData extends TlMessagesPeerSettingsAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerSettingsAbstractData $settings,
    public array $chats,
    public array $users,
    ) {
    }
}
