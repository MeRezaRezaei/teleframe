<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity\Support;

use Illuminate\Notifications\Notification;
use MeRezaRezaei\Teleframe\Identity\Channels\TelegramMessage;

/**
 * Minimal notification exercising the TelegramChannel payload contract.
 */
class TestTelegramNotification extends Notification
{
    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::text('Hello from channel');
    }
}