<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionToggleNoForwards (channelAdminLogEventActionToggleNoForwards). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionToggleNoForwardsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleNoForwards> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleNoForwards::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'new_value' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
