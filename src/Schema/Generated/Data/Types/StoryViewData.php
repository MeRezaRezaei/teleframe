<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for storyView of StoryView.
 */
final class StoryViewData extends TlStoryViewAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'blocked' => ['flags', 0],
        'blockedMyStoriesFrom' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $blocked,
    public ?bool $blockedMyStoriesFrom,
    public int $userId,
    public int $date,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReactionAbstractData $reaction,
    ) {
    }
}
