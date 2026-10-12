<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Supervisor;

use RuntimeException;

/**
 * The refusal for a process name the shipped catalog does not know.
 *
 * Loud BY DESIGN: a supervisor that silently skips a misspelled or
 * unsupported process name is the one failure mode an operator cannot see —
 * the command exits "successfully" while the worker never starts. Every
 * refusal carries the known names so the mistake is fixable from the message.
 */
final class UnknownProcessException extends RuntimeException
{
    public static function for(string $name, array $known): self
    {
        return new self(sprintf(
            'Unknown supervised process "%s" — the package ships: %s. '
            .'Bind your own ProcessSpec list (SupervisorSupport::SPECS_KEY) to supervise your own long-running command.',
            $name,
            $known === [] ? '(none)' : implode(', ', $known),
        ));
    }
}
