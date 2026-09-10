<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStoryItemStoryItem (storyItem). */
final class TlStoryItemStoryItemFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItem> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'pinned' => true,
            'public' => true,
            'close_friends' => true,
            'min' => true,
            'noforwards' => true,
            'edited' => true,
            'contacts' => true,
            'selected_contacts' => true,
            'out' => true,
            'tl_id' => 11,
            'date' => 12,
            'from_id' => 1013,
            'fwd_from' => 1014,
            'expire_date' => 15,
            'caption' => 'caption-16',
            'media' => 1017,
            'views' => 1018,
            'sent_reaction' => 1019,
            'music' => 1020,
        ];
    }
}
