<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Stage\Exceptions;

use RuntimeException;

/**
 * Host-wiring guard (Phase 5e, F5-strict): a completed flow cannot submit
 * because no submit closure and no `Request` resolver were provided. This is
 * a configuration error surfaced to the integrator — never a user message.
 */
final class StageFlowException extends RuntimeException
{
}