<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionChangeStickerSet (channelAdminLogEventActionChangeStickerSet). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionChangeStickerSetFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangeStickerSet> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangeStickerSet::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prev_stickerset' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_stickerset' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
