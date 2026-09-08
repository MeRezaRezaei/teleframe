<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateReadMonoForumOutbox (updateReadMonoForumOutbox). */
final class TlUpdateUpdateReadMonoForumOutboxFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateReadMonoForumOutbox> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateReadMonoForumOutbox::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'channel_id' => 1001,
            'saved_peer_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'read_max_id' => 3,
        ];
    }
}
