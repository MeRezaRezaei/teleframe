<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionEditTopic (channelAdminLogEventActionEditTopic). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionEditTopicFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionEditTopic> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionEditTopic::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prev_topic' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_topic' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
