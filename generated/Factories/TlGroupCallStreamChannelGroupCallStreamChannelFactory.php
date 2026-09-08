<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlGroupCallStreamChannelGroupCallStreamChannel (groupCallStreamChannel). */
final class TlGroupCallStreamChannelGroupCallStreamChannelFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallStreamChannelGroupCallStreamChannel> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallStreamChannelGroupCallStreamChannel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'channel' => 1,
            'scale' => 2,
            'last_timestamp_ms' => 1003,
        ];
    }
}
