<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateChannelViewForumAsMessages (updateChannelViewForumAsMessages). */
final class TlUpdateUpdateChannelViewForumAsMessagesFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelViewForumAsMessages> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelViewForumAsMessages::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'channel_id' => 1001,
            'enabled' => 1002,
        ];
    }
}
