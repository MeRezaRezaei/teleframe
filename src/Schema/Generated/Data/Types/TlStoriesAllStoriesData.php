<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stories.allStories of stories.AllStories.
 */
final class TlStoriesAllStoriesData extends TlStoriesAllStoriesAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'hasMore' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $hasMore,
    public int $count,
    public string $state,
    public array $peerStories,
    public array $chats,
    public array $users,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStoriesStealthModeAbstractData $stealthMode,
    ) {
    }
}
