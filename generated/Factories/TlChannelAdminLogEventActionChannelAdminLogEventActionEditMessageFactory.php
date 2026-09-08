<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionEditMessage (channelAdminLogEventActionEditMessage). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionEditMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionEditMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionEditMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prev_message' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_message' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
