<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionSendMessage (channelAdminLogEventActionSendMessage). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionSendMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionSendMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionSendMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'message' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
