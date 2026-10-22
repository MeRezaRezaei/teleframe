<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\StateMachine;

final class StateTransition
{
    public function __construct(
        public string $from,
        public string $to,
        public mixed $guard = null,
        public ?string $query = null,
        public array $queryParams = [],
        public mixed $transform = null,
    ) {}

    public function isAllowed(array $context = [], array $queries = []): bool
    {
        if ($this->guard !== null) {
            if (! (bool) ($this->guard)($context)) {
                return false;
            }
        }
        if ($this->query !== null) {
            $query = $queries[$this->query] ?? null;
            if (! is_callable($query)) {
                return false;
            }

            return (bool) $query(array_merge($this->queryParams, $context));
        }

        return true;
    }

    public function applyTransform(mixed $data): mixed
    {
        if ($this->transform === null) {
            return $data;
        }

        return ($this->transform)($data);
    }

    public function __toString(): string
    {
        return $this->to;
    }
}
