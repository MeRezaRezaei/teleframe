<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Stage;

use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Keyboard\KeyboardRegistry;
use MeRezaRezaei\Teleframe\Handler\Keyboard\Menu;
use MeRezaRezaei\Teleframe\Handler\Keyboard\MenuRouter;
use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Message\FilesystemCache;
use MeRezaRezaei\Teleframe\Stage\StageMiddleware;
use MeRezaRezaei\Teleframe\Stage\StageRegistry;
use MeRezaRezaei\Teleframe\Stage\StageSet;
use MeRezaRezaei\Teleframe\Stage\StageState;
use MeRezaRezaei\Teleframe\Testing\FakeDispatcher;
use MeRezaRezaei\Teleframe\Tests\Stage\Support\StageContainer;
use MeRezaRezaei\Teleframe\Tests\Stage\Support\StageFlowTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;

/**
 * F5 dispatch precedence (Phase 5e): echo eliminator FIRST, the active stage
 * SECOND (before the keyboard object and the general handler), keyboard
 * object THIRD, general handler LAST.
 *
 * Two proofs, one per surface:
 *  - FakeDispatcher (the D2 fake, whose recorder layer is hardwired) proves
 *    echo → keyboard → general with NO stage active;
 *  - a real UpdateDispatcher hosting [$stageMiddleware, $recorder] proves the
 *    stage sits between echo and the keyboard/general layers: consumed flow
 *    frames NEVER reach a user handler, while a callback during the flow
 *    still reaches the keyboard.
 */
final class PrecedenceTest extends StageFlowTestCase
{
    private const SECRET = 'test-hmac-secret';

    /** @var list<string> */
    private array $routed = [];

    /** @var list<string> */
    private array $general = [];

    /** @var list<Update> */
    private array $recorded = [];

    /** @var list<array<string, mixed>> */
    private array $submitted = [];

    /** @var list<array{text: string, entities: list<array<string, mixed>>}> */
    private array $stageSent = [];

    private KeyboardRegistry $keyboards;
    private Menu $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->menu = new Menu([
            [Menu::button('Confirm', 'confirm', 'yes')],
        ]);
        $this->keyboards = new KeyboardRegistry();
        $this->keyboards->register($this->menu);
    }

    private function baseRegistry(HandlerRegistry $handlers): void
    {
        $handlers->on('callback_query', function (Update $u, MenuRouter $router): void {
            $router->dispatch($u);
        });
        $handlers->on('confirm %s', function (string $arg): void {
            $this->routed[] = 'confirm:' . $arg;
        });
        $handlers->onMessage(function (Update $update): void {
            $this->general[] = $update->constructor();
        });
    }

    private function token(int $msgId = 7, int $chatId = 100): string
    {
        return $this->menu->render(self::SECRET, $chatId, $msgId)['inline_keyboard'][0][0]['callback_data'];
    }

    private function callbackUpdate(string $data): Update
    {
        return Update::fromBus([
            '_' => 'callback_query',
            'callback_query' => [
                'id' => 'cq-1',
                'data' => $data,
                'message' => ['message_id' => 7, 'chat' => ['id' => 100]],
            ],
        ], 100, 3);
    }

    public function test_fake_dispatcher_echoes_first_then_keyboard_before_general(): void
    {
        $handlers = new HandlerRegistry();
        $this->baseRegistry($handlers);

        $container = new StageContainer();
        $sends = new ArrayCache();
        $router = new MenuRouter(
            new UpdateDispatcher($handlers, new Pipeline(), $container, $sends),
            $this->keyboards,
            self::SECRET,
            null,
        );
        $container->set(MenuRouter::class, $router);
        $eliminator = new EchoEliminator($sends, new HandlerMatcher($handlers));
        $eliminator->remember(100, ['random_id' => 'r-echo', 'msg_id' => '9', 'sent_at' => 1]);

        $fake = new FakeDispatcher(
            [
                ['update' => ['_' => 'updateNewMessage', 'message' => ['text' => 'plain hello']], 'account_id' => 100, 'ts' => 1],
                ['update' => ['_' => 'callback_query', 'callback_query' => ['id' => 'cq-1', 'data' => $this->token(), 'message' => ['message_id' => 7, 'chat' => ['id' => 100]]]], 'account_id' => 100, 'ts' => 2],
                ['update' => ['_' => 'updateNewMessage', 'message' => ['text' => 'echoed reply'], 'random_id' => 'r-echo'], 'account_id' => 100, 'ts' => 4],
            ],
            $handlers,
            $container,
            $sends,
        );

        $fake->run();

        $dispatched = array_map(static fn (Update $u): string => $u->constructor(), $fake->dispatched);
        self::assertSame(['updateNewMessage', 'callback_query'], $dispatched);
        self::assertSame(['confirm:yes'], $this->routed);
        self::assertSame(['updateNewMessage'], $this->general);
    }

    public function test_active_stage_sits_between_echo_and_keyboard_and_general(): void
    {
        $signup = StageSet::define('updateNewMessage')
            ->stage('email')->field('email')->rule('email')->prompt('ask_email')
            ->final('confirm')->field('tos')->rule('accepted')->prompt('ask_tos')
            ->compile();

        $stageRegistry = (new StageRegistry())->on($signup, function (array $data): void {
            $this->submitted[] = $data;
        })->compile();

        $container = new StageContainer();
        $sends = new ArrayCache();
        $state = new StageState(new FilesystemCache($this->root . '/state'), $stageRegistry);

        $handlers = new HandlerRegistry();
        $this->baseRegistry($handlers);

        $stage = new StageMiddleware(
            $stageRegistry,
            $state,
            $container,
            $this->messages,
            null,
            $this->validator,
            function (array $plan): void {
                $this->stageSent[] = $plan;
            },
        );

        $real = new UpdateDispatcher(
            $handlers,
            new Pipeline(),
            $container,
            $sends,
            [$stage, function (Update $u, callable $next): mixed {
                $this->recorded[] = $u;

                return $next($u);
            }],
        );
        $container->set(MenuRouter::class, new MenuRouter($real, $this->keyboards, self::SECRET, null));

        $eliminator = new EchoEliminator($sends, new HandlerMatcher($handlers));
        $eliminator->remember(100, ['random_id' => 'r-echo', 'msg_id' => '9', 'sent_at' => 1]);
        $state->start($signup, '100');

        // 1) echo of our own reply — dropped before even the stage sees it
        $real->dispatch(Update::fromBus(
            ['_' => 'updateNewMessage', 'message' => ['text' => 'echoed'], 'random_id' => 'r-echo'],
            100,
            1,
        ));
        self::assertCount(0, $this->recorded);
        self::assertCount(0, $this->stageSent);
        self::assertTrue($state->has('100'));

        // 2) flow answer — consumed by the stage, never forwarded
        $real->dispatch(Update::fromBus(
            ['_' => 'updateNewMessage', 'message' => ['text' => 'ada@example.com']],
            100,
            2,
        ));
        self::assertSame([], $this->recorded);
        self::assertTrue($state->has('100'));
        self::assertSame(['email' => 'ada@example.com'], $state->current('100')['data']);
        self::assertSame(['Reply “yes” to accept the terms.'], array_column($this->stageSent, 'text'));

        // 3) keyboard callback while the flow waits — the idle stage lets it
        //    through, the keyboard object re-dispatches through the onion
        $real->dispatch($this->callbackUpdate($this->token()));
        self::assertSame(['confirm:yes'], $this->routed);
        self::assertCount(2, $this->recorded);
        self::assertSame('callback_query', $this->recorded[0]->constructor());
        self::assertSame('confirm yes', $this->recorded[1]->constructor());

        // 4) last flow answer — stage consumes and finishes via the closure
        $real->dispatch(Update::fromBus(
            ['_' => 'updateNewMessage', 'message' => ['text' => 'yes']],
            100,
            4,
        ));
        self::assertSame([['email' => 'ada@example.com', 'tos' => 'yes']], $this->submitted);
        self::assertFalse($state->has('100'));
        self::assertCount(2, $this->recorded);

        // the general handler NEVER saw a frame: every one was echo-dropped,
        // stage-consumed, or keyboard-routed.
        self::assertSame([], $this->general);
    }
}