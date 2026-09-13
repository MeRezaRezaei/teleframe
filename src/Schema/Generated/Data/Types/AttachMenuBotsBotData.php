<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for attachMenuBotsBot of AttachMenuBotsBot.
 */
final class AttachMenuBotsBotData extends TlAttachMenuBotsBotAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlAttachMenuBotAbstractData $bot,
    public array $users,
    ) {
    }
}
