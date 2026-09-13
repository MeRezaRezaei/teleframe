<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stories.stories of stories.Stories.
 */
final class TlStoriesStoriesData extends TlStoriesStoriesAbstractData
{
    public function __construct(
    public int $flags,
    public int $count,
    public array $stories,
    public ?array $pinnedToTop,
    public array $chats,
    public array $users,
    ) {
    }
}
