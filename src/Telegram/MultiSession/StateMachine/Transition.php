<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession\StateMachine;

/** @template T */
final class Transition
{
    public function __construct(
        public State $from,
        public State $to,
        /** @var callable(): bool|null */
        public $guard = null,
        /** @var callable(): void|null */
        public $onTransition = null,
    ) {}

    public function isAllowed(): bool
    {
        if ($this->guard === null) {
            return true;
        }

        return ($this->guard)();
    }

    public function execute(): void
    {
        if ($this->onTransition !== null) {
            ($this->onTransition)();
        }
    }
}
