<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for storyViews of StoryViews.
 */
final class StoryViewsData extends TlStoryViewsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'hasViewers' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $hasViewers,
    public int $viewsCount,
    public ?int $forwardsCount,
    public ?array $reactions,
    public ?int $reactionsCount,
    public ?array $recentViewers,
    ) {
    }
}
