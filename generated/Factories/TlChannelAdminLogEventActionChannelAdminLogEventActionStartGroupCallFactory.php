<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionStartGroupCall (channelAdminLogEventActionStartGroupCall). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionStartGroupCallFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionStartGroupCall> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionStartGroupCall::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'call' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
