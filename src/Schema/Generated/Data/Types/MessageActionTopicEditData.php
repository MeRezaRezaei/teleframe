<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionTopicEdit of MessageAction.
 */
final class MessageActionTopicEditData extends TlMessageActionAbstractData
{
    public function __construct(
    public int $flags,
    public ?string $title,
    public ?int $iconEmojiId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $closed,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $hidden,
    ) {
    }
}
