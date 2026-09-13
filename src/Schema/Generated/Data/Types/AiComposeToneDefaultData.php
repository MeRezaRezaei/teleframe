<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for aiComposeToneDefault of AiComposeTone.
 */
final class AiComposeToneDefaultData extends TlAiComposeToneAbstractData
{
    public function __construct(
    public string $tone,
    public int $emojiId,
    public string $title,
    ) {
    }
}
