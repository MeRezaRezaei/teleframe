<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionChangePhoto (channelAdminLogEventActionChangePhoto). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionChangePhotoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangePhoto> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangePhoto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prev_photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_photo' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
