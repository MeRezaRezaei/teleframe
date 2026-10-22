<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession\StateMachine;

/**
 * Represents a state in the state machine.
 */
final class State
{
    public function __construct(
        public string $id,
        public string $version,
        public array $data = [],
        public ?int $enteredAt = null,
        public ?int $expiresAt = null,
    ) {}

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return time() >= $this->expiresAt;
    }

    public function withData(array $data): self
    {
        return new self(
            id: $this->id,
            version: $this->version,
            data: $data,
            enteredAt: $this->enteredAt,
            expiresAt: $this->expiresAt,
        );
    }

    public function withExpiry(int $ttlSeconds): self
    {
        return new self(
            id: $this->id,
            version: $this->version,
            data: $this->data,
            enteredAt: $this->enteredAt,
            expiresAt: time() + $ttlSeconds,
        );
    }

    public function withoutExpiry(): self
    {
        return new self(
            id: $this->id,
            version: $this->version,
            data: $this->data,
            enteredAt: $this->enteredAt,
            expiresAt: null,
        );
    }
}
