<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Stage;

use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\TelegramContext;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Message\FilesystemCache;
use MeRezaRezaei\Teleframe\Stage\StageMiddleware;
use MeRezaRezaei\Teleframe\Stage\StageRegistry;
use MeRezaRezaei\Teleframe\Stage\StageSet;
use MeRezaRezaei\Teleframe\Stage\StageState;
use MeRezaRezaei\Teleframe\Tests\Stage\Fixtures\Forms\SignupFormRequest;
use MeRezaRezaei\Teleframe\Tests\Stage\Support\StageContainer;
use MeRezaRezaei\Teleframe\Tests\Stage\Support\StageFlowTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;

/**
 * StageMiddleware (Phase 5e): consumes flow answers, prompts ahead,
 * template-shaped failures ({text, entities}), flags the container, and
 * submits a completed flow — closure path OR the Q20 in-process
 * Request::create to the same route the web form posts to.
 */
final class StageMiddlewareTest extends StageFlowTestCase
{
    /** @var list<string> */
    private array $general = [];

    /** @var list<array{text: string, entities: list<array<string, mixed>>}> */
    private array $sent = [];

    private StageContainer $container;
    private StageRegistry $stageRegistry;
    private StageState $state;
    private StageSet $signup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signup = StageSet::define('updateNewMessage')
            ->submit('/forms/signup', SignupFormRequest::class)
            ->stage('email')->field('email')->rule('email')->prompt('ask_email')->error('email_bad')
            ->final('confirm')->field('tos')->rule('accepted')->prompt('ask_tos')->error('tos_bad')
            ->compile();

        $this->stageRegistry = (new StageRegistry())->on($this->signup)->compile();
        $this->container = new StageContainer();
        $this->state = new StageState(
            new FilesystemCache($this->root . '/state'),
            $this->stageRegistry,
        );
    }

    /** @return array{0: UpdateDispatcher, 1: StageMiddleware} */
    private function dispatcher(?callable $resolver = null, ?StageRegistry $registry = null): array
    {
        $handlerRegistry = new HandlerRegistry();
        $handlerRegistry->onMessage(function (Update $update): void {
            $this->general[] = $update->constructor();
        });

        $stage = new StageMiddleware(
            $registry ?? $this->stageRegistry,
            $this->state,
            $this->container,
            $this->messages,
            $resolver,
            $this->validator,
            function (array $plan): void {
                $this->sent[] = $plan;
            },
        );

        $dispatcher = new UpdateDispatcher(
            $handlerRegistry,
            new Pipeline(),
            $this->container,
            new ArrayCache(),
            [$stage],
        );

        return [$dispatcher, $stage];
    }

    public function test_no_active_state_passes_to_general_handler(): void
    {
        [$dispatcher] = $this->dispatcher();

        $dispatcher->dispatch($this->text('hello from the void'));

        self::assertSame(['updateNewMessage'], $this->general);
        self::assertCount(0, $this->sent);
        self::assertFalse($this->state->has('100'));
    }

    public function test_active_state_without_match_passes_through(): void
    {
        [$dispatcher] = $this->dispatcher();
        $this->state->start($this->signup, '100');

        $dispatcher->dispatch(Update::fromBus(['_' => 'callback_query', 'callback_query' => ['id' => 'cq-1']], 100));

        self::assertSame(['callback_query'], $this->general);
        self::assertCount(0, $this->sent);
        self::assertTrue($this->state->has('100'));
    }

    public function test_consumes_answer_advances_and_prompts_the_next_stage(): void
    {
        [$dispatcher] = $this->dispatcher();
        $this->state->start($this->signup, '100');

        $dispatcher->dispatch($this->text('ada@example.com'));

        self::assertSame([], $this->general);
        self::assertSame('confirm', $this->state->current('100')['currentStage']);
        self::assertSame(['email' => 'ada@example.com'], $this->state->current('100')['data']);
        self::assertCount(1, $this->sent);
        self::assertSame('Reply “yes” to accept the terms.', $this->sent[0]['text']);
        self::assertSame([], $this->sent[0]['entities']);
    }

    public function test_failed_capture_sends_template_shaped_error_without_throwing(): void
    {
        [$dispatcher, $stage] = $this->dispatcher();
        $this->state->start($this->signup, '100');

        $dispatcher->dispatch($this->text('not-an-email'));

        self::assertSame([], $this->general);
        self::assertSame([], $this->sent[0]['entities']);
        self::assertStringContainsString('invalid', $this->sent[0]['text']);
        self::assertSame([], $this->state->current('100')['data']);
        self::assertTrue($this->state->has('100'));
        self::assertCount(0, $stage->outbox());
    }

    public function test_completed_flow_calls_registered_submit_closure_states_finished(): void
    {
        /** @var list<array<string, mixed>> $submitted */
        $submitted = [];
        $closure = static function (array $data) use (&$submitted): void {
            $submitted[] = $data;
        };
        $registry = (new StageRegistry())->on($this->signup, $closure)->compile();
        [$dispatcher] = $this->dispatcher(null, $registry);
        $this->state->start($this->signup, '100');

        $dispatcher->dispatch($this->text('ada@example.com'));
        $dispatcher->dispatch($this->text('yes'));

        self::assertSame(
            [['email' => 'ada@example.com', 'tos' => 'yes']],
            $submitted,
        );
        self::assertFalse($this->state->has('100'));
        self::assertSame([], $this->general);
    }

    public function test_completed_flow_sub_dispatches_in_process_request_create_with_flagged_container(): void
    {
        $captured = null;
        $flagInside = null;
        $contextInside = null;

        $resolver = function (Request $request) use (&$captured, &$flagInside, &$contextInside): void {
            $captured = $request;
            $flagInside = $this->container->has(StageMiddleware::FLAG)
                ? $this->container->get(StageMiddleware::FLAG)
                : null;
            $contextInside = $this->container->has(TelegramContext::class)
                ? $this->container->get(TelegramContext::class)
                : null;
        };

        [$dispatcher, $stage] = $this->dispatcher($resolver);
        $this->state->start($this->signup, '100');

        $dispatcher->dispatch($this->text('ada@example.com'));
        $dispatcher->dispatch($this->text('yes'));

        self::assertNotNull($captured);
        self::assertSame('POST', $captured->getMethod());
        self::assertSame('/forms/signup', $captured->getPathInfo());
        self::assertSame('ada@example.com', $captured->input('email'));
        self::assertSame('yes', $captured->input('tos'));
        self::assertSame(true, $flagInside);
        self::assertNotNull($contextInside);
        self::assertTrue($contextInside->inStageFlow());
        self::assertFalse($this->state->has('100'));
        self::assertSame([], $this->general);
        self::assertCount(0, $stage->outbox());
        self::assertFalse($this->container->has(StageMiddleware::FLAG));
        self::assertFalse($this->container->has(TelegramContext::class));
    }

    public function test_completed_flow_with_invalid_form_request_sends_submit_error_keeps_state(): void
    {
        // tos has NO per-field rule, so 'no' passes capture and only the
        // FormRequest's own `accepted` rule rejects it at submit time —
        // exercising the Q20 submit-error gate, not the stage error template.
        $permissive = StageSet::define('updateNewMessage')
            ->submit('/forms/signup', SignupFormRequest::class)
            ->stage('email')->field('email')->rule('email')->prompt('ask_email')
            ->final('confirm')->field('tos')->prompt('ask_tos')
            ->compile();
        $registry = (new StageRegistry())->on($permissive)->compile();

        [$dispatcher] = $this->dispatcher(function (): void {
            self::fail('Resolver must not fire when the FormRequest rules reject the data.');
        }, $registry);
        $this->state->start($permissive, '100');

        $dispatcher->dispatch($this->text('ada@example.com'));
        $dispatcher->dispatch($this->text('no'));

        self::assertCount(2, $this->sent);
        self::assertSame('Reply “yes” to accept the terms.', $this->sent[0]['text']);
        self::assertSame('The submitted data was invalid. Please start over.', $this->sent[1]['text']);
        self::assertSame([], $this->sent[1]['entities']);
        self::assertTrue($this->state->has('100'));
        self::assertSame(['email' => 'ada@example.com', 'tos' => 'no'], $this->state->current('100')['data']);
    }

    public function test_completed_flow_without_submit_and_resolver_sends_error_and_finishes(): void
    {
        $noSubmitSet = StageSet::define('updateNewMessage')
            ->stage('email')->field('email')->rule('email')
            ->final('confirm')->field('tos')->rule('accepted')
            ->compile();
        $registry = (new StageRegistry())->on($noSubmitSet)->compile();
        [$dispatcher] = $this->dispatcher(null, $registry);
        $this->state->start($noSubmitSet, '100');

        $dispatcher->dispatch($this->text('ada@example.com'));
        $dispatcher->dispatch($this->text('yes'));

        self::assertSame('The submitted data was invalid. Please start over.', $this->sent[0]['text']);
        self::assertSame([], $this->sent[0]['entities']);
        self::assertFalse($this->state->has('100'));
        self::assertSame([], $this->general);
    }

    public function test_unknown_stage_set_in_state_passes_through_defensively(): void
    {
        $foreign = StageSet::define('updateNewMessage', ['x' => ['steps' => ['x']]])->compile();
        $this->state->start($foreign, '100');

        $otherRegistry = (new StageRegistry())
            ->on(StageSet::define('other', ['o' => ['steps' => ['o']]]))
            ->compile();

        [$dispatcher] = $this->dispatcher(null, $otherRegistry);

        $dispatcher->dispatch($this->text('anything'));

        self::assertSame(['updateNewMessage'], $this->general);
        self::assertTrue($this->state->has('100'));
    }

    private function text(string $text): Update
    {
        return Update::fromBus(['_' => 'updateNewMessage', 'message' => ['text' => $text]], 100);
    }
}