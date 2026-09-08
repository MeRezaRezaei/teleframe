<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for storyItemSkipped of StoryItem.
 */
final class StoryItemSkippedData extends TlStoryItemAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'closeFriends' => ['flags', 8],
        'live' => ['flags', 9],
    ];

    public function __construct(
    public int $flags,
    public ?bool $closeFriends,
    public ?bool $live,
    public int $id,
    public int $date,
    public int $expireDate,
    ) {
    }
}
