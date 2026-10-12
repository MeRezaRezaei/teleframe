<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for storyItem of StoryItem.
 */
final class StoryItemData extends TlStoryItemAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'pinned' => ['flags', 5],
        'public' => ['flags', 7],
        'closeFriends' => ['flags', 8],
        'min' => ['flags', 9],
        'noforwards' => ['flags', 10],
        'edited' => ['flags', 11],
        'contacts' => ['flags', 12],
        'selectedContacts' => ['flags', 13],
        'out' => ['flags', 16],
    ];

    public function __construct(
    public int $flags,
    public ?bool $pinned,
    public ?bool $public,
    public ?bool $closeFriends,
    public ?bool $min,
    public ?bool $noforwards,
    public ?bool $edited,
    public ?bool $contacts,
    public ?bool $selectedContacts,
    public ?bool $out,
    public int $id,
    public int $date,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStoryFwdHeaderAbstractData $fwdFrom,
    public int $expireDate,
    public ?string $caption,
    public ?array $entities,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageMediaAbstractData $media,
    public ?array $mediaAreas,
    public ?array $privacy,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStoryViewsAbstractData $views,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReactionAbstractData $sentReaction,
    public ?array $albums,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $music,
    ) {
    }
}
