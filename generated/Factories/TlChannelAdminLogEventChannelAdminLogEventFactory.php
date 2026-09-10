<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventChannelAdminLogEvent (channelAdminLogEvent). */
final class TlChannelAdminLogEventChannelAdminLogEventFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventChannelAdminLogEvent> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventChannelAdminLogEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1001,
            'date' => 2,
            'user_id' => 1003,
            'action' => 1004,
        ];
    }
}
