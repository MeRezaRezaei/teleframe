<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionUpdatePinned (channelAdminLogEventActionUpdatePinned). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionUpdatePinnedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionUpdatePinned> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionUpdatePinned::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'message' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
