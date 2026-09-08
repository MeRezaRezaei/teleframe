<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateReadChannelInbox (updateReadChannelInbox). */
final class TlUpdateUpdateReadChannelInboxFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateReadChannelInbox> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateReadChannelInbox::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'folder_id' => 2,
            'channel_id' => 1003,
            'max_id' => 4,
            'still_unread_count' => 5,
            'pts' => 6,
        ];
    }
}
