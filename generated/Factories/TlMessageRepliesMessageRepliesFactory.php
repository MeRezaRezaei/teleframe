<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageRepliesMessageReplies (messageReplies). */
final class TlMessageRepliesMessageRepliesFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageRepliesMessageReplies> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageRepliesMessageReplies::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'comments' => true,
            'replies' => 3,
            'replies_pts' => 4,
            'channel_id' => 1005,
            'max_id' => 6,
            'read_max_id' => 7,
        ];
    }
}
