<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler\Keyboard;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Keyboard\KeyboardRegistry;
use MeRezaRezaei\Teleframe\Handler\Keyboard\Menu;
use MeRezaRezaei\Teleframe\Handler\Keyboard\MenuRouter;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use PHPUnit\Framework\TestCase;

/**
 * The callback bridge mechanics: no-op surfaces, the pre-send chat-bound
 * confirmation pass, and the expiry answer exactly as a BotClient
 * answerCallbackQuery closure would receive it.
 */
final class MenuRouterTest extends TestCase
{
    private const SECRET = 'test-hmac-secret';

    private HandlerRegistry $registry;
    private KeyboardRegistry $keyboards;
    private MenuRouter $router;

    /** @var list<array{0: string, 1: string}> */
    private array $answered = [];

    protected function setUp(): void
    {
        $this->registry = new HandlerRegistry();
        $container = new ArrayContainer();
        $sends = new ArrayCache();
        $dispatcher = new UpdateDispatcher($this->registry, new Pipeline(), $container, $sends);
        $this->keyboards = new KeyboardRegistry();
        $this->router = new MenuRouter(
            $dispatcher,
            $this->keyboards,
            self::SECRET,
            function (string $id, string $text): void {
                $this->answered[] = [$id, $text];
            },
        );
        $container->set(MenuRouter::class, $this->router);
    }

    public function test_non_callback_update_is_a_noop(): void
    {
        $ran = false;
        $this->registry->onMessage(function () use (&$ran): void {
            $ran = true;
        });

        $result = $this->router->dispatch(Update::fromBus(['_' => 'updateNewMessage', 'text' => 'hi'], 1));

        self::assertNull($result);
        self::assertFalse($ran);
        self::assertSame([], $this->answered);
    }

    public function test_callback_without_data_is_a_noop(): void
    {
        $update = Update::fromBus([
            '_' => 'callback_query',
            'callback_query' => ['id' => 'cq-1', 'message' => ['message_id' => 7, 'chat' => ['id' => 421]]],
        ], 1);

        self::assertNull($this->router->dispatch($update));
        self::assertSame([], $this->answered);
    }

    public function test_expiry_answer_carries_the_query_id_and_text(): void
    {
        $menu = new Menu([[Menu::button('Old', 'menu:old')]], version: 1);
        $this->keyboards->register($menu);
        $token = $menu->render(self::SECRET, 421, 7)['inline_keyboard'][0][0]['callback_data'];

        // Rotation unseats version 1 before the tap arrives.
        $this->keyboards->register(new Menu($menu->rows, version: 2));

        $this->router->dispatch($this->callbackUpdate($token, 7, 421));

        self::assertSame([['cq-1', MenuRouter::EXPIRED_TEXT]], $this->answered);
    }

    public function test_pre_send_chat_bound_token_is_confirmed(): void
    {
        $menu = new Menu([[Menu::button('Settings', 'menu:settings', 'dark')]]);
        $this->keyboards->register($menu);
        $seen = [];
        $this->registry->on('menu:settings %s', function (Update $u, string $arg) use (&$seen): void {
            $seen[] = [$u->constructor(), $arg];
        });

        $token = $menu->render(self::SECRET, 421)['inline_keyboard'][0][0]['callback_data'];

        $this->router->dispatch($this->callbackUpdate($token, 7, 421));

        self::assertSame([['menu:settings dark', 'dark']], $seen);
        self::assertSame([], $this->answered);
    }

    public function test_message_bound_token_stays_strict(): void
    {
        $menu = new Menu([[Menu::button('Settings', 'menu:settings', 'dark')]]);
        $this->keyboards->register($menu);
        $ran = false;
        $this->registry->on('menu:settings %s', function () use (&$ran): void {
            $ran = true;
        });

        $token = $menu->render(self::SECRET, 421, 7)['inline_keyboard'][0][0]['callback_data'];

        // Same chat, different message: a message-bound token must not fire.
        $this->router->dispatch($this->callbackUpdate($token, 8, 421));

        self::assertFalse($ran);
        self::assertSame([['cq-1', MenuRouter::EXPIRED_TEXT]], $this->answered);
    }

    /** @return array<string, mixed> */
    private function callbackUpdate(string $data, int $msgId, int $chatId): Update
    {
        return Update::fromBus([
            '_' => 'callback_query',
            'callback_query' => [
                'id' => 'cq-1',
                'data' => $data,
                'message' => ['message_id' => $msgId, 'chat' => ['id' => $chatId]],
            ],
        ], 1);
    }
}