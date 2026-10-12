<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionTopicCreate of MessageAction.
 */
final class MessageActionTopicCreateData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'titleMissing' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $titleMissing,
    public string $title,
    public int $iconColor,
    public ?int $iconEmojiId,
    ) {
    }
}
