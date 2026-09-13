<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for attachMenuBotIcon of AttachMenuBotIcon.
 */
final class AttachMenuBotIconData extends TlAttachMenuBotIconAbstractData
{
    public function __construct(
    public int $flags,
    public string $name,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $icon,
    public ?array $colors,
    ) {
    }
}
