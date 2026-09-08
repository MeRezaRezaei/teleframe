<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBadMsgNotificationBadMsgNotification (bad_msg_notification). */
final class TlBadMsgNotificationBadMsgNotificationFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBadMsgNotificationBadMsgNotification> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBadMsgNotificationBadMsgNotification::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'bad_msg_id' => 1001,
            'bad_msg_seqno' => 2,
            'error_code' => 3,
        ];
    }
}
