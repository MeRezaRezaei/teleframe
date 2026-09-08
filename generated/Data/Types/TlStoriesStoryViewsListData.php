<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stories.storyViewsList of stories.StoryViewsList.
 */
final class TlStoriesStoryViewsListData extends TlStoriesStoryViewsListAbstractData
{
    public function __construct(
    public int $flags,
    public int $count,
    public int $viewsCount,
    public int $forwardsCount,
    public int $reactionsCount,
    public array $views,
    public array $chats,
    public array $users,
    public ?string $nextOffset,
    ) {
    }
}
