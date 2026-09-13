<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.autoSaveSettings of account.AutoSaveSettings.
 */
final class TlAccountAutoSaveSettingsData extends TlAccountAutoSaveSettingsAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlAutoSaveSettingsAbstractData $usersSettings,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlAutoSaveSettingsAbstractData $chatsSettings,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlAutoSaveSettingsAbstractData $broadcastsSettings,
    public array $exceptions,
    public array $chats,
    public array $users,
    ) {
    }
}
