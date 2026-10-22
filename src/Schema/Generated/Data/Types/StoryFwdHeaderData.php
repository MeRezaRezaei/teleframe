<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for storyFwdHeader of StoryFwdHeader.
 */
final class StoryFwdHeaderData extends TlStoryFwdHeaderAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'modified' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $modified,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $from,
    public ?string $fromName,
    public ?int $storyId,
    ) {
    }
}
