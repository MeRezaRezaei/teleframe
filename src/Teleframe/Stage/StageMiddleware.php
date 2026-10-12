<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Stage;

use Closure;
use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\Handler\TelegramContext;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Message\MessageFactory;
use MeRezaRezaei\Teleframe\Stage\Exceptions\StageFlowException;
use MeRezaRezaei\Teleframe\Stage\Exceptions\StageValidationException;
use Psr\Container\ContainerInterface;

/**
 * The stage machine's dispatch layer (Phase 5e). Sits in the extra-middleware
 * slot of the UpdateDispatcher onion — AFTER echo eliminator and replay dedup,
 * BEFORE keyboard routing and the general handler — so a consumed frame never
 * reaches a user handler.
 *
 * Dispatch contract (F5-strict):
 *
 *   1. NO active state for the account → pass through untouched.
 *   2. Active state whose stage expects a DIFFERENT constructor → pass
 *      through (a keyboard callback or an unrelated constructor is not a
 *      flow answer).
 *   3. Active state whose field expects the CURRENT constructor → the frame
 *      is a flow answer: read the value, validate, consume. Never forwarded.
 *
 * Q17/Q18/Q20: state is plain arrays in a PSR-16 store, the current update is
 * raised into the container as a FLAGGED TelegramContext (`FLAG` binding) for
 * the whole exchange, and a completed form posts through an IN-PROCESS
 * `Request::create` to the SAME route the web leg posts to — no HTTP
 * round-trip, loop-safe. Failures are message plans ({text, entities}), never
 * exceptions to the dispatcher.
 */
final class StageMiddleware
{
    /** Container binding marking an in-flight telegram-paced stage exchange. */
    public const FLAG = 'teleframe.stage.in_telegram_flow';

    /**
     * @var list<array{text: string, entities: list<array<string, mixed>>}>
     */
    private array $outbox = [];

    public function __construct(
        private readonly StageRegistry $registry,
        private readonly StageState $state,
        private readonly ContainerInterface $container,
        private readonly MessageFactory $messages,
        private readonly ?\Closure $resolver = null,
        private readonly ?StageValidator $validator = null,
        private readonly ?\Closure $reply = null,
        private readonly ?\Closure $reader = null,
    ) {
    }

    public function __invoke(Update $update, callable $next): mixed
    {
        $account = (string) $update->accountId;

        $this->enterStageFlow($update);

        try {
            $state = $this->state->current($account);
            if ($state === null) {
                return $next($update);
            }

            $set = $this->registry->setFor((string) $state['stageSet']);
            if ($set === null) {
                return $next($update);
            }

            $pending = $set->pendingField((string) $state['currentStage'], $state['data']);
            if ($pending['complete']) {
                $this->submitFlow($update, $set, $state);

                return null;
            }

            $stage = (string) $pending['stage'];
            $field = (string) $pending['field'];
            $expects = $set->expectsFor($stage, $field);

            if (! $this->registry->patternMatches($expects, $update->constructor())) {
                return $next($update);
            }

            $value = $this->readValue($update);
            try {
                $this->validator()->validate($field, $value, $set->rulesFor($stage, $field));
            } catch (StageValidationException) {
                $this->send($set->errorFor($stage) ?? 'stages.validation_failed');

                return null;
            }

            $newState = $this->state->advance($account, [$field => $value], $set);
            $nextPending = $set->pendingField((string) $newState['currentStage'], $newState['data']);

            if ($nextPending['complete']) {
                $this->submitFlow($update, $set, $newState);
            } else {
                $this->send($set->promptFor((string) $nextPending['stage']));
            }

            return null;
        } finally {
            $this->leaveStageFlow();
        }
    }

    /** Outbox of template-shaped plans when no `$reply` closure is wired. */
    public function outbox(): array
    {
        return $this->outbox;
    }

    /** The currently outstanding message plans since the last read. */
    public function drainOutbox(): array
    {
        $sent = $this->outbox;
        $this->outbox = [];

        return $sent;
    }

    private function validator(): StageValidator
    {
        return $this->validator ?? new StageValidator();
    }

    private function readValue(Update $update): string
    {
        if ($this->reader !== null) {
            return (string) ($this->reader)($update);
        }

        return (string) ($update->array['message']['text'] ?? '');
    }

    /**
     * Finish a fully-captured flow. Priority: registered submit closure →
     * Q20 Route/FormRequest leg → submitError template.
     *
     * @param array{stageSet: string, currentStage: ?string, data: array<string, mixed>, msgIds: list<int>} $state
     */
    private function submitFlow(Update $update, StageSet $set, array $state): void
    {
        $data = $state['data'];
        $account = (string) $update->accountId;

        $submit = $this->registry->submitFor($set->name());
        if ($submit !== null) {
            ($submit)($data);
            $this->state->finish($account);

            return;
        }

        if ($set->hasSubmit()) {
            $errors = StageFormRequest::stageErrors($set->submitFormRequest(), $data);
            if ($errors !== []) {
                $this->send($set->submitErrorTemplate());

                return;
            }

            $this->dispatchFormRequest($set, $data);
            $this->state->finish($account);

            return;
        }

        $this->send($set->submitErrorTemplate());
        $this->state->finish($account);
    }

    /**
     * Q20: in-process sub-dispatch — validate against the FormRequest's own
     * rules(), then `Request::create` the exact route the web form posts to
     * and resolve it through the host's controller bridge. No socket, no
     * HTTP, loop-safe.
     *
     * @param array<string, mixed> $data
     */
    private function dispatchFormRequest(StageSet $set, array $data): void
    {
        if ($this->resolver === null) {
            throw new StageFlowException(
                'Stage submit is not wired: resolve the in-process Request through $resolver (Q20).',
            );
        }

        $request = Request::create($set->submitUri(), $set->submitMethod(), $data);
        ($this->resolver)($request);
    }

    /** @param array{text: string, entities: list<array<string, mixed>>} $plan */
    private function sendPlan(array $plan): void
    {
        if ($this->reply !== null) {
            ($this->reply)($plan);

            return;
        }
        $this->outbox[] = $plan;
    }

    private function send(?string $template): void
    {
        if ($template === null || $template === '') {
            return;
        }

        $this->sendPlan($this->messages->resolve($template));
    }

    /** @var list<array{had: bool, value: mixed, hadContext: bool, context: ?TelegramContext}> */
    private array $stack = [];

    private function enterStageFlow(Update $update): void
    {
        $had = $this->container->has(StageMiddleware::FLAG);
        $prior = $had ? $this->container->get(StageMiddleware::FLAG) : null;
        $this->containerSet(StageMiddleware::FLAG, true);

        $hadContext = $this->container->has(TelegramContext::class);
        $priorContext = $hadContext ? $this->container->get(TelegramContext::class) : null;
        $this->containerSet(TelegramContext::class, new TelegramContext($update, true));

        $this->stack[] = ['had' => $had, 'value' => $prior, 'hadContext' => $hadContext, 'context' => $priorContext];
    }

    private function leaveStageFlow(): void
    {
        $hi = array_pop($this->stack);
        if ($hi === null) {
            return;
        }

        if ($hi['had']) {
            $this->containerSet(StageMiddleware::FLAG, $hi['value']);
        } else {
            $this->containerDelete(StageMiddleware::FLAG);
        }

        if ($hi['hadContext']) {
            /** @var TelegramContext|null $context */
            $context = $hi['context'];
            if ($context !== null) {
                $this->containerSet(TelegramContext::class, $context);
            }
        } else {
            $this->containerDelete(TelegramContext::class);
        }
    }

    private function containerSet(string $key, mixed $value): void
    {
        if (! method_exists($this->container, 'set')) {
            return;
        }
        $set = Closure::fromCallable([$this->container, 'set']);
        $set($key, $value);
    }

    private function containerDelete(string $key): void
    {
        if (! method_exists($this->container, 'delete')) {
            $this->containerSet($key, false);

            return;
        }
        $delete = Closure::fromCallable([$this->container, 'delete']);
        $delete($key);
    }
}