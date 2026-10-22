<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession\StateMachine;

interface StateMachine
{
    public function getState(string $userId): State;

    public function transition(string $userId, Transition $transition): State;

    public function getValidTransitions(State $current): array;

    public function isValidTransition(State $from, State $to): bool;

    public function getTimeout(State $state): int;

    public function persist(State $state): void;

    public function getStateSchema(): array;

    public function getVersion(): string;
}
