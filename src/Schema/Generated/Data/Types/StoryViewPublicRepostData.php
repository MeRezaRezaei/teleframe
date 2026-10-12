<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for storyViewPublicRepost of StoryView.
 */
final class StoryViewPublicRepostData extends TlStoryViewAbstractData
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
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peerId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStoryItemAbstractData $story,
    ) {
    }
}
