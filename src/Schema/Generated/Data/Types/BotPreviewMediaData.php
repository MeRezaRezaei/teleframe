<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for botPreviewMedia of BotPreviewMedia.
 */
final class BotPreviewMediaData extends TlBotPreviewMediaAbstractData
{
    public function __construct(
    public int $date,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageMediaAbstractData $media,
    ) {
    }
}
