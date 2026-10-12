<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for attachMenuBotIconColor of AttachMenuBotIconColor.
 */
final class AttachMenuBotIconColorData extends TlAttachMenuBotIconColorAbstractData
{
    public function __construct(
    public string $name,
    public int $color,
    ) {
    }
}
