<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdatePinnedForumTopic (updatePinnedForumTopic). */
final class TlUpdateUpdatePinnedForumTopicFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePinnedForumTopic> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePinnedForumTopic::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'pinned' => true,
            'peer' => 1003,
            'topic_id' => 4,
        ];
    }
}
