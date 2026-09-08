<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionToggleAutotranslation (channelAdminLogEventActionToggleAutotranslation). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionToggleAutotranslationFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleAutotranslation> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleAutotranslation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'new_value' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
