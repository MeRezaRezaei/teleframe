<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateBotMenuButton of Update.
 */
final class UpdateBotMenuButtonData extends TlUpdateAbstractData
{
    public function __construct(
    public int $botId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotMenuButtonAbstractData $button,
    ) {
    }
}
