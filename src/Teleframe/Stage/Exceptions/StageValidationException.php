<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Stage\Exceptions;

use DomainException;

/**
 * Template-shaped validation failure: thrown only inside the stage leg's own
 * capture/validate step, immediately converted by StageMiddleware into a
 * stage error message plan ({text, entities}) keyed by the failing field's
 * stage — never surfaced to a handler, never rethrown to the dispatcher
 * (Q13 / F5: failures are messages, not exceptions).
 */
final class StageValidationException extends DomainException
{
    /**
     * @param array<string, list<string>> $errors field → rule messages
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Stage validation failed.',
    ) {
        parent::__construct($message);
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}