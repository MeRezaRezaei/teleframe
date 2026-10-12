<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler\Keyboard;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Keyboard\CallbackData;
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
 * The injection suite (Phase 5c gate): forged signatures, rotated/old menus,
 * and replay-with-rebound all stay out of the handler table (expiry answer =
 * the 403-equivalent), while a legitimately signed, bound, still-live token
 * routes exactly like a message update.
 */
final class InjectionTest extends TestCase
{
    private const SECRET = 'test-hmac-secret';

    private HandlerRegistry $registry;
    private KeyboardRegistry $keyboards;
    private MenuRouter $router;

    /** @var list<array{0: string, 1: string}> */
    private array $answered = [];

    /** @var list<string> */
    private array $routed = [];

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

        // The real wiring: an answerCallbackQuery-driven update lands on the
        // shared HandlerRegistry::on() table and hands off to the router.
        $this->registry->on('callback_query', fn (Update $u, MenuRouter $router) => $router->dispatch($u));

        $this->registry->on('catalog:view %s', function (string $arg): void {
            $this->routed[] = "view:$arg";
        });
        $this->registry->on('catalog:buy %s', function (string $arg): void {
            $this->routed[] = "buy:$arg";
        });
    }

    public function test_forged_signature_is_rejected(): void
    {
        $menu = $this->liveMenu();
        $token = $menu->render(self::SECRET, 421, 7)['inline_keyboard'][0][0]['callback_data'];

        self::assertNotNull(CallbackData::decode($token, 7, 421, self::SECRET));
        $forged = substr($token, 0, -1) . ($token[-1] === 'A' ? 'B' : 'A');
        self::assertNull(CallbackData::decode($forged, 7, 421, self::SECRET));

        $result = $this->router->dispatch($this->callbackUpdate($forged, 7, 421));

        self::assertNull($result);
        self::assertSame([['cq-1', MenuRouter::EXPIRED_TEXT]], $this->answered);
        self::assertSame([], $this->routed);
    }

    public function test_old_menu_rotated_version_is_rejected_as_expired(): void
    {
        $rows = [[Menu::button('View', 'catalog:view', 'sku-blue')]];
        $v1 = new Menu($rows, version: 1);
        $this->keyboards->register($v1);
        $token = $v1->render(self::SECRET, 421, 7)['inline_keyboard'][0][0]['callback_data'];

        // Q16 rotation: same content, bump the version byte -> old keyId dies.
        $this->keyboards->register(new Menu($rows, version: 2));

        $result = $this->router->dispatch($this->callbackUpdate($token, 7, 421));

        self::assertNull($result);
        self::assertSame([['cq-1', MenuRouter::EXPIRED_TEXT]], $this->answered);
        self::assertSame([], $this->routed);
    }

    public function test_replayed_token_with_rebound_message_or_chat_is_rejected(): void
    {
        $menu = $this->liveMenu();
        $token = $menu->render(self::SECRET, 421, 7)['inline_keyboard'][0][0]['callback_data'];

        self::assertNull(CallbackData::decode($token, 8, 421, self::SECRET));
        self::assertNull(CallbackData::decode($token, 7, 422, self::SECRET));
        self::assertEquals(['route' => $menu->keyId() . ':0', 'arg' => 'sku-blue'], CallbackData::decode($token, 7, 421, self::SECRET));

        // Rebound to a new message in the same chat.
        $this->router->dispatch($this->callbackUpdate($token, 8, 421));
        self::assertSame([], $this->routed);

        // Relayed into another chat with a fresh message.
        $this->router->dispatch($this->callbackUpdate($token, 9, 422));
        self::assertSame([], $this->routed);

        self::assertCount(2, $this->answered);
        self::assertSame([['cq-1', MenuRouter::EXPIRED_TEXT], ['cq-1', MenuRouter::EXPIRED_TEXT]], $this->answered);
    }

    public function test_legit_token_routes_to_the_right_handler(): void
    {
        $menu = $this->liveMenu();
        $token = $menu->render(self::SECRET, 421, 7)['inline_keyboard'][0][0]['callback_data'];

        $this->router->dispatch($this->callbackUpdate($token, 7, 421));

        self::assertSame(['view:sku-blue'], $this->routed);
        self::assertSame([], $this->answered);
    }

    public function test_a_different_button_of_the_same_menu_routes_separately(): void
    {
        $menu = $this->liveMenu();
        $buy = $menu->render(self::SECRET, 421, 7)['inline_keyboard'][0][1]['callback_data'];

        $this->router->dispatch($this->callbackUpdate($buy, 7, 421));

        self::assertSame(['buy:sku-blue'], $this->routed);
        self::assertSame([], $this->answered);
    }

    private function liveMenu(): Menu
    {
        $menu = new Menu([
            [
                Menu::button('View', 'catalog:view', 'sku-blue'),
                Menu::button('Buy', 'catalog:buy', 'sku-blue'),
            ],
        ]);
        $this->keyboards->register($menu);

        return $menu;
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