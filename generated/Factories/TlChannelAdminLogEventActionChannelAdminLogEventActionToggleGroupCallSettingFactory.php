<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionToggleGroupCallSetting (channelAdminLogEventActionToggleGroupCallSetting). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionToggleGroupCallSettingFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleGroupCallSetting> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleGroupCallSetting::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'join_muted' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
