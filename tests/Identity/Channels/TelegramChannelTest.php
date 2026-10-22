<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity\Channels;

use Illuminate\Notifications\Notification;
use MeRezaRezaei\Teleframe\Identity\Bindings;
use MeRezaRezaei\Teleframe\Identity\Channels\TelegramChannel;
use MeRezaRezaei\Teleframe\Identity\Channels\TelegramMessage;
use MeRezaRezaei\Teleframe\Identity\IdentityConfig;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestContactableUser;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestIntRouteUser;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestNullRouteUser;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestPlainUser;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestTelegramNotification;
use MeRezaRezaei\Teleframe\Tests\Identity\TestCase;
use Throwable;

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

        // Control: the SAME notifiable and notification DO deliver when a
        // sender is injected. Without this, the silence asserted below would
        // also be satisfied by an earlier bail-out on payload or route, and
        // the test would pass for the wrong reason.
        $delivered = null;
        (new TelegramChannel(function (array $route, string $text, array $options) use (&$delivered): array {
            $delivered = $route;

            return [];
        }))->send($user, new TestTelegramNotification());
        self::assertSame(['tg_id' => 42, 'account' => 5], $delivered);

        // Precondition observed through the same reader the channel resolves
        // through: there is genuinely nothing configured to send with.
        self::assertNull(IdentityConfig::defaultSender());
        self::assertNull(IdentityConfig::botToken());

        $thrown = null;
        try {
            (new TelegramChannel())->send($user, new TestTelegramNotification());
        } catch (Throwable $caught) {
            $thrown = $caught;
        }

        self::assertNull($thrown, 'With no sender configured the channel must no-op, never dial or throw.');
    }

    public function testNoopForNumericDefaultSender(): void
    {
        // Numeric default sender = an MTProto account id; no in-tree deliverer.
        config(['teleframe.identity.default_sender' => '501558149']);
        config(['teleframe.bot_token' => null]);
        putenv('TELEGRAM_BOT_TOKEN');

        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);

        // Control: same input, injected sender -> delivers, so the silence
        // below is the default-sender resolution and not an early return.
        $delivered = null;
        (new TelegramChannel(function (array $route, string $text, array $options) use (&$delivered): array {
            $delivered = $route;

            return [];
        }))->send($user, new TestTelegramNotification());
        self::assertSame(['tg_id' => 42, 'account' => 5], $delivered);

        // The numeric default sender IS configured and DOES reach the reader -
        // this is what separates this case from a missing-sender no-op. (No bot
        // token is available to observe it against, so the branch itself is
        // pinned by the no-op below, not proven to be its cause.)
        self::assertSame('501558149', IdentityConfig::defaultSender());
        self::assertNull(IdentityConfig::botToken());

        $thrown = null;
        try {
            (new TelegramChannel())->send($user, new TestTelegramNotification());
        } catch (Throwable $caught) {
            $thrown = $caught;
        }

        self::assertNull($thrown, 'A numeric default sender must no-op, never dial the Bot API.');
    }

    public function testMessageValueObject(): void
    {
        $message = TelegramMessage::text('payload');

        self::assertSame('payload', $message->body());
        self::assertSame(['text' => 'payload'], $message->toArray());
    }
}