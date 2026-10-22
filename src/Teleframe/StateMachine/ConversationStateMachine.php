<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\StateMachine;

use MeRezaRezaei\Teleframe\Telegram\MultiSession\BinderRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ConversationStateMachine
{
    public const VERSION = '1.0.0';

    public const STATE_INITIAL = 'initial';

    public const STATE_ACTIVE = 'active';

    public const STATE_WAITING_INPUT = 'waiting_input';

    public const STATE_COMPLETED = 'completed';

    public const STATE_ERROR = 'error';

    /** @var array<string, array{state:string,data:array,pattern:TelegramPattern,enteredAt:int}> */
    private array $storage = [];

    /** @var array<string, callable> */
    private array $queries = [];

    /** @var array<string, list<StateTransition>> */
    private array $transitions = [];

    private LoggerInterface $logger;

    private ?BinderRegistry $binderRegistry = null;

    private TelegramPattern $defaultPattern;

    public function __construct(?LoggerInterface $logger = null, ?TelegramPattern $defaultPattern = null)
    {
        $this->logger = $logger ?? new NullLogger;
        $this->defaultPattern = $defaultPattern ?? TelegramPattern::Private;
        $this->registerDefaultTransitions();
    }

    /** @return list<string> */
    public function states(): array
    {
        return [
            self::STATE_INITIAL,
            self::STATE_ACTIVE,
            self::STATE_WAITING_INPUT,
            self::STATE_COMPLETED,
            self::STATE_ERROR,
        ];
    }

    public function getState(string $peerId): string
    {
        return $this->storage[$peerId]['state'] ?? self::STATE_INITIAL;
    }

    public function getStateData(string $peerId): array
    {
        return $this->storage[$peerId]['data'] ?? [];
    }

    public function getPattern(string $peerId): TelegramPattern
    {
        return $this->storage[$peerId]['pattern'] ?? $this->defaultPattern;
    }

    public function setPattern(string $peerId, TelegramPattern $pattern): void
    {
        if (! isset($this->storage[$peerId])) {
            $this->storage[$peerId] = [
                'state' => self::STATE_INITIAL,
                'data' => [],
                'pattern' => $pattern,
                'enteredAt' => time(),
            ];

            return;
        }
        $this->storage[$peerId]['pattern'] = $pattern;
    }

    public function detectPattern(array $context): TelegramPattern
    {
        return TelegramPattern::detect($context);
    }

    public function registerQuery(string $name, callable $query): void
    {
        $this->queries[$name] = $query;
    }

    public function hasQuery(string $name): bool
    {
        return isset($this->queries[$name]);
    }

    public function getQuery(string $name): ?callable
    {
        return $this->queries[$name] ?? null;
    }

    public function addTransition(StateTransition $transition): void
    {
        $this->transitions[$transition->from][] = $transition;
    }

    /** @return list<StateTransition> */
    public function getTransitions(string $state): array
    {
        return $this->transitions[$state] ?? [];
    }

    /** @return list<StateTransition> */
    public function getValidTransitionObjects(string $state): array
    {
        return $this->getTransitions($state);
    }

    /** @return list<string> */
    public function getValidTransitions(string $state): array
    {
        $result = [];
        foreach ($this->getTransitions($state) as $transition) {
            $result[] = $transition->to;
        }

        return array_values(array_unique($result));
    }

    public function getTransition(string $from, string $to): ?StateTransition
    {
        foreach ($this->getTransitions($from) as $transition) {
            if ($transition->to === $to) {
                return $transition;
            }
        }

        return null;
    }

    public function isValidTransition(string $from, string $to): bool
    {
        return $this->getTransition($from, $to) !== null;
    }

    /**
     * @param  mixed  $transitionOrState  string target state or StateTransition
     */
    public function transition(string $peerId, mixed $transitionOrState, array $context = []): string
    {
        $current = $this->getState($peerId);

        if ($transitionOrState instanceof StateTransition) {
            $transition = $transitionOrState;
            if ($transition->from !== $current) {
                throw new \InvalidArgumentException(
                    "Transition from state '{$transition->from}' not allowed. Current state is '{$current}'."
                );
            }
            if (! $transition->isAllowed($context, $this->queries)) {
                throw new \LogicException(
                    "Transition guard failed for transition from '{$transition->from}' to '{$transition->to}'."
                );
            }
            $target = $transition->to;
        } elseif (is_string($transitionOrState)) {
            $target = $transitionOrState;
            if (! $this->isValidTransition($current, $target)) {
                throw new \InvalidArgumentException(
                    "Transition from '{$current}' to '{$target}' is not valid."
                );
            }
            $transition = $this->getTransition($current, $target);
            if ($transition !== null && ! $transition->isAllowed($context, $this->queries)) {
                throw new \LogicException(
                    "Transition guard failed for transition from '{$current}' to '{$target}'."
                );
            }
        } else {
            throw new \InvalidArgumentException('Transition target must be a state string or StateTransition.');
        }

        $data = $this->getStateData($peerId);
        if (isset($context['binary']) && is_string($context['binary'])) {
            if (! $this->validateBinary($context['binary'])) {
                throw new \InvalidArgumentException('Invalid base64 binary payload.');
            }
            $data['binary_raw'] = $context['binary'];
            $data['binary_decoded'] = $this->decodeBinary($context['binary']);
        }
        if (isset($context['data']) && is_array($context['data'])) {
            $data = array_merge($data, $context['data']);
        }
        if ($transition instanceof StateTransition) {
            $data = $transition->applyTransform($data);
        }

        $pattern = $this->getPattern($peerId);
        $this->storage[$peerId] = [
            'state' => $target,
            'data' => $data,
            'pattern' => $pattern,
            'enteredAt' => time(),
        ];

        $this->logger->info('Conversation state transition', [
            'peer_id' => $peerId,
            'from' => $current,
            'to' => $target,
            'pattern' => $pattern->value,
        ]);

        return $target;
    }

    public function reset(string $peerId): void
    {
        unset($this->storage[$peerId]);
    }

    public function remove(string $peerId): void
    {
        $this->reset($peerId);
    }

    public function clear(string $peerId): void
    {
        $this->reset($peerId);
    }

    public function validateBinary(string $payload): bool
    {
        $trimmed = trim($payload);
        if ($trimmed === '') {
            return false;
        }
        $decoded = base64_decode($trimmed, true);
        if ($decoded === false) {
            return false;
        }

        // round-trip check
        return base64_encode($decoded) === $trimmed || base64_encode($decoded) === $payload;
    }

    public function decodeBinary(string $payload): string
    {
        $trimmed = trim($payload);
        $decoded = base64_decode($trimmed, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64 payload.');
        }

        return $decoded;
    }

    public function handleBinary(string $peerId, string $payload): string
    {
        if (! $this->validateBinary($payload)) {
            throw new \InvalidArgumentException('Invalid base64 payload.');
        }
        $decoded = $this->decodeBinary($payload);
        $data = $this->getStateData($peerId);
        $data['binary_raw'] = trim($payload);
        $data['binary_decoded'] = $decoded;
        if (isset($this->storage[$peerId])) {
            $this->storage[$peerId]['data'] = $data;
        } else {
            $this->storage[$peerId] = [
                'state' => self::STATE_INITIAL,
                'data' => $data,
                'pattern' => $this->defaultPattern,
                'enteredAt' => time(),
            ];
        }

        return $decoded;
    }

    public function bindTo(BinderRegistry $registry): void
    {
        $this->binderRegistry = $registry;
    }

    public function getBinderRegistry(): ?BinderRegistry
    {
        return $this->binderRegistry;
    }

    // Aliases
    public function getCurrentState(string $peerId): string
    {
        return $this->getState($peerId);
    }

    public function currentState(string $peerId): string
    {
        return $this->getState($peerId);
    }

    public function applyTransition(string $peerId, mixed $transitionOrState, array $context = []): string
    {
        return $this->transition($peerId, $transitionOrState, $context);
    }

    public function setState(string $peerId, mixed $state, array $context = []): string
    {
        return $this->transition($peerId, $state, $context);
    }

    public function move(string $peerId, mixed $state, array $context = []): string
    {
        return $this->transition($peerId, $state, $context);
    }

    public function changeState(string $peerId, mixed $state, array $context = []): string
    {
        return $this->transition($peerId, $state, $context);
    }

    public function canTransition(string $from, string $to): bool
    {
        return $this->isValidTransition($from, $to);
    }

    public function allowsTransition(string $from, string $to): bool
    {
        return $this->isValidTransition($from, $to);
    }

    private function registerDefaultTransitions(): void
    {
        $s = self::class;
        // initial
        $this->addTransition(new StateTransition(self::STATE_INITIAL, self::STATE_ACTIVE));
        $this->addTransition(new StateTransition(self::STATE_INITIAL, self::STATE_ERROR));
        // active
        $this->addTransition(new StateTransition(self::STATE_ACTIVE, self::STATE_WAITING_INPUT));
        $this->addTransition(new StateTransition(self::STATE_ACTIVE, self::STATE_COMPLETED));
        $this->addTransition(new StateTransition(self::STATE_ACTIVE, self::STATE_ERROR));
        // waiting_input
        $this->addTransition(new StateTransition(self::STATE_WAITING_INPUT, self::STATE_ACTIVE));
        $this->addTransition(new StateTransition(self::STATE_WAITING_INPUT, self::STATE_COMPLETED));
        $this->addTransition(new StateTransition(self::STATE_WAITING_INPUT, self::STATE_ERROR));
        // completed
        $this->addTransition(new StateTransition(self::STATE_COMPLETED, self::STATE_INITIAL));
        // error
        $this->addTransition(new StateTransition(self::STATE_ERROR, self::STATE_INITIAL));
    }
}
