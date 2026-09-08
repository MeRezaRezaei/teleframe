<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

use DomainException;

/**
 * Raised at registration time when a match pattern carries more than one
 * ``%s`` sscanf token (spec 5a §2: exactly zero or one are allowed). The
 * matcher's single-capture contract would be ambiguous otherwise.
 */
final class MultipleSscanfTokensException extends DomainException
{
}