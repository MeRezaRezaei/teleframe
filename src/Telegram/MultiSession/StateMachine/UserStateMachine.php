<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession\StateMachine;

use MeRezaRezaei\Teleframe\Telegram\MultiSession\Session;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\SessionStore;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class UserStateMachine implements StateMachine
{
    public const VERSION = '1.0.0';

    public const SCHEMA_VERSION = '1';

    private SessionStore $sessionStore;

    private LoggerInterface $logger;

    private array $stateDefinitions;

    private int $defaultTimeout;

    public function __construct(
        ?SessionStore $sessionStore = null,
        ?LoggerInterface $logger = null,
        int $defaultTimeout = 300,
    ) {
        $this->sessionStore = $sessionStore ?? new SessionStore;
        $this->logger = $logger ?? new NullLogger;
        $this->defaultTimeout = $defaultTimeout;
        $this->stateDefinitions = $this->getDefaultStateDefinitions();
    }

    public function getState(string $userId): State
    {
        $session = $this->sessionStore->get($userId);
        if ($session === null) {
            return new State(
                id: 'idle',
                version: self::VERSION,
                enteredAt: time(),
            );
        }

        if (isset($session->context['state'])) {
            $stateData = $session->context['state'];

            return new State(
                id: $stateData['id'] ?? 'idle',
                version: $stateData['version'] ?? self::VERSION,
                data: $stateData['data'] ?? [],
                enteredAt: $stateData['entered_at'] ?? null,
                expiresAt: $stateData['expires_at'] ?? null,
            );
        }

        return new State(
            id: 'idle',
            version: self::VERSION,
            enteredAt: time(),
        );
    }

    public function transition(string $userId, Transition $transition): State
    {
        $currentState = $this->getState($userId);

        if ($transition->from->id !== $currentState->id) {
            throw new \InvalidArgumentException(
                "Transition from state '{$transition->from->id}' not allowed. Current state is '{$currentState->id}'."
            );
        }

        if (! $this->isValidTransition($transition->from, $transition->to)) {
            throw new \InvalidArgumentException(
                "Transition from '{$transition->from->id}' to '{$transition->to->id}' is not valid."
            );
        }

        if (! $transition->isAllowed()) {
            throw new \LogicException("Transition guard failed for transition from '{$transition->from->id}' to '{$transition->to->id}'.");
        }

        $newState = $transition->to->withData(
            array_merge($currentState->data, $transition->to->data)
        )->withExpiry($this->getTimeout($transition->to));

        $transition->execute();

        $this->persistStateForUser($userId, $newState);

        $this->logger->info('State transition', [
            'user_id' => $userId,
            'from' => $transition->from->id,
            'to' => $transition->to->id,
        ]);

        return $newState;
    }

    public function getValidTransitions(State $current): array
    {
        return $this->stateDefinitions[$current->id]['transitions'] ?? [];
    }

    public function isValidTransition(State $from, State $to): bool
    {
        $validTransitions = $this->stateDefinitions[$from->id]['transitions'] ?? [];
        foreach ($validTransitions as $transition) {
            if ($transition->to->id === $to->id) {
                return true;
            }
        }

        return false;
    }

    public function getTimeout(State $state): int
    {
        return $this->stateDefinitions[$state->id]['timeout'] ?? $this->defaultTimeout;
    }

    public function persist(State $state): void
    {
        // The state is persisted via the session store when transitioning
        // This method is a no-op for the user-level state machine
        // but exists for interface compliance
    }

    public function persistStateForUser(string $userId, State $state): void
    {
        $session = $this->sessionStore->get($userId);
        if ($session === null) {
            $session = new Session($userId);
        }

        $session->context['state'] = [
            'id' => $state->id,
            'version' => $state->version,
            'data' => $state->data,
            'entered_at' => $state->enteredAt ?? time(),
            'expires_at' => $state->expiresAt,
        ];

        $this->sessionStore->set($userId, $session);
    }

    public function getStateSchema(): array
    {
        return [
            'version' => self::SCHEMA_VERSION,
            'states' => array_keys($this->stateDefinitions),
            'current_version' => self::VERSION,
        ];
    }

    public function getVersion(): string
    {
        return self::VERSION;
    }

    public function registerState(string $id, array $definition): void
    {
        $this->stateDefinitions[$id] = array_merge([
            'transitions' => [],
            'timeout' => $this->defaultTimeout,
        ], $definition);
    }

    private function getDefaultStateDefinitions(): array
    {
        return [
            'idle' => [
                'transitions' => [
                    new Transition(
                        from: new State('idle', self::VERSION),
                        to: new State('awaiting_input', self::VERSION),
                    ),
                ],
                'timeout' => 0,
            ],
            'awaiting_input' => [
                'transitions' => [
                    new Transition(
                        from: new State('awaiting_input', self::VERSION),
                        to: new State('processing', self::VERSION),
                    ),
                    new Transition(
                        from: new State('awaiting_input', self::VERSION),
                        to: new State('idle', self::VERSION),
                    ),
                ],
                'timeout' => 300,
            ],
            'processing' => [
                'transitions' => [
                    new Transition(
                        from: new State('processing', self::VERSION),
                        to: new State('completed', self::VERSION),
                    ),
                    new Transition(
                        from: new State('processing', self::VERSION),
                        to: new State('awaiting_input', self::VERSION),
                    ),
                    new Transition(
                        from: new State('processing', self::VERSION),
                        to: new State('error', self::VERSION),
                    ),
                ],
                'timeout' => 60,
            ],
            'completed' => [
                'transitions' => [
                    new Transition(
                        from: new State('completed', self::VERSION),
                        to: new State('idle', self::VERSION),
                    ),
                ],
                'timeout' => 0,
            ],
            'error' => [
                'transitions' => [
                    new Transition(
                        from: new State('error', self::VERSION),
                        to: new State('idle', self::VERSION),
                    ),
                ],
                'timeout' => 0,
            ],
        ];
    }
}

class_alias('MeRezaRezaei\Telegram\MultiSession\StateMachine\UserStateMachine', 'MeRezaRezaei\Telegram\MultiSession\StateMachine\ConversationStateMachine');
class_alias('MeRezaRezaei\Telegram\MultiSession\StateMachine\UserStateMachine', 'MeRezaRezaei\Teleframe\Telegram\MultiSession\StateMachine\ConversationStateMachine');
