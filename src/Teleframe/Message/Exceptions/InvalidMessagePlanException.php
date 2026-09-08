<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Message\Exceptions;

use DomainException;

/**
 * Raised by `MessagePlanValidator` when a compiled plan does not conform to
 * what `MethodRegistry` accepts for `messages.sendMessage` (Q14 typecheck) —
 * unknown plan keys, unknown entity keys, non-`messageEntity*` entity types,
 * or structurally invalid `entities` / `reply_markup` values. Extension of
 * `DomainException` mirrors the handler substrate's
 * `MultipleSscanfTokensException` style.
 */
final class InvalidMessagePlanException extends DomainException
{
}