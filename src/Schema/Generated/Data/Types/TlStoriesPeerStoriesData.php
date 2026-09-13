<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stories.peerStories of stories.PeerStories.
 */
final class TlStoriesPeerStoriesData extends TlStoriesPeerStoriesAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerStoriesAbstractData $stories,
    public array $chats,
    public array $users,
    ) {
    }
}
