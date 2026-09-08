<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionToggleSignatures (channelAdminLogEventActionToggleSignatures). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionToggleSignaturesFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleSignatures> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleSignatures::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'new_value' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
