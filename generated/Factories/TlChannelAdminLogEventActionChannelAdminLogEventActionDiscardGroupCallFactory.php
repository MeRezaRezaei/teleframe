<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionDiscardGroupCall (channelAdminLogEventActionDiscardGroupCall). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionDiscardGroupCallFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionDiscardGroupCall> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionDiscardGroupCall::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'call' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
