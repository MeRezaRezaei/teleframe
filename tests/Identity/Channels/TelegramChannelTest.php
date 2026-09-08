<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity\Channels;

use Illuminate\Notifications\Notification;
use MeRezaRezaei\Teleframe\Identity\Bindings;
use MeRezaRezaei\Teleframe\Identity\Channels\TelegramChannel;
use MeRezaRezaei\Teleframe\Identity\Channels\TelegramMessage;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestContactableUser;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestIntRouteUser;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestNullRouteUser;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestPlainUser;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestTelegramNotification;
use MeRezaRezaei\Teleframe\Tests\Identity\TestCase;

/**
 * Q9 channel: route resolution (trait override / integer / null-skip /
 * binding fallback), payload extraction (TelegramMessage / string / array),
 * and the default-sender paths (never a network call here — the deliverer is
 * injected except for the two silent no-op cases).
 */
class TelegramChannelTest extends TestCase
{
    public function testSendUsesRouteFromNotifiable(): void
    {
        $captured = null;
        $channel = new TelegramChannel(function (array $route, string $text, array $options) use (&$captured): array {
            $captured = [$route, $text, $options];

            return [];
        });

        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);

        $channel->send($user, new TestTelegramNotification());

        self::assertNotNull($captured);
        self::assertSame(['tg_id' => 42, 'account' => 5], $captured[0]);
        self::assertSame('Hello from channel', $captured[1]);
        self::assertSame([], $captured[2]);
    }

    public function testIntegerRouteSupported(): void
    {
        $captured = null;
        $channel = new TelegramChannel(function (array $route, string $text, array $options) use (&$captured): array {
            $captured = $route;

            return [];
        });

        $user = TestIntRouteUser::create(['name' => 'I']);
        $channel->send($user, new TestTelegramNotification());

        self::assertSame(['tg_id' => 555, 'account' => null], $captured);
    }

    public function testNullRouteSkips(): void
    {
        $sent = 0;
        $channel = new TelegramChannel(function () use (&$sent): array {
            ++$sent;

            return [];
        });

        $user = TestNullRouteUser::create(['name' => 'N']);
        $channel->send($user, new TestTelegramNotification());

        self::assertSame(0, $sent);
    }

    public function testBindingFallbackRouteWhenNoRouteMethod(): void
    {
        $captured = null;
        $channel = new TelegramChannel(function (array $route, string $text, array $options) use (&$captured): array {
            $captured = $route;

            return [];
        });

        $user = TestPlainUser::create(['name' => 'P']);
        Bindings::bindLaravelUser($user, 111, 2);

        $channel->send($user, new TestTelegramNotification());

        self::assertSame(['tg_id' => 111, 'account' => 2], $captured);
    }

    public function testStringPayloadAndOptions(): void
    {
        $captured = null;
        $channel = new TelegramChannel(function (array $route, string $text, array $options) use (&$captured): array {
            $captured = [$route, $text, $options];

            return [];
        });

        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);

        $notification = new class extends Notification {
            public function toTelegram(object $notifiable): array
            {
                return ['text' => 'T', 'parse_mode' => 'HTML'];
            }
        };

        $channel->send($user, $notification);

        self::assertSame('T', $captured[1]);
        self::assertSame(['parse_mode' => 'HTML'], $captured[2]);
    }

    public function testPayloadFromStringNotification(): void
    {
        $captured = null;
        $channel = new TelegramChannel(function (array $route, string $text, array $options) use (&$captured): array {
            $captured = $text;

            return [];
        });

        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);

        $notification = new class extends Notification {
            public function toTelegram(object $notifiable): string
            {
                return 'plain text';
            }
        };

        $channel->send($user, $notification);

        self::assertSame('plain text', $captured);
    }

    public function testEmptyPayloadSkips(): void
    {
        $sent = 0;
        $channel = new TelegramChannel(function () use (&$sent): array {
            ++$sent;

            return [];
        });

        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);

        $notification = new class extends Notification {
            public function toTelegram(object $notifiable): array
            {
                return [];
            }
        };

        $channel->send($user, $notification);

        self::assertSame(0, $sent);
    }

    public function testNoToTelegramSkips(): void
    {
        $sent = 0;
        $channel = new TelegramChannel(function () use (&$sent): array {
            ++$sent;

            return [];
        });

        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);

        $channel->send($user, new class extends Notification {
        });

        self::assertSame(0, $sent);
    }

    public function testNoopWhenNoSenderConfigured(): void
    {
        config(['teleframe.bot_token' => null]);
        putenv('TELEGRAM_BOT_TOKEN');

        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);

        $channel = new TelegramChannel();
        $channel->send($user, new TestTelegramNotification());

        self::assertTrue(true);
    }

    public function testNoopForNumericDefaultSender(): void
    {
        // Numeric default sender = an MTProto account id; no in-tree deliverer.
        config(['teleframe.identity.default_sender' => '501558149']);
        config(['teleframe.bot_token' => null]);
        putenv('TELEGRAM_BOT_TOKEN');

        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);

        $channel = new TelegramChannel();
        $channel->send($user, new TestTelegramNotification());

        self::assertTrue(true);
    }

    public function testMessageValueObject(): void
    {
        $message = TelegramMessage::text('payload');

        self::assertSame('payload', $message->body());
        self::assertSame(['text' => 'payload'], $message->toArray());
    }
}