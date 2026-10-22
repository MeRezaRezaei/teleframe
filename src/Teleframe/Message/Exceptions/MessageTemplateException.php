<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Message\Exceptions;

use RuntimeException;

/**
 * Raised by `MessageCompiler` for template-level failures: the named template
 * does not exist, or it did not `return` an array. Carries the template name
 * so hosts can log which view is broken.
 */
final class MessageTemplateException extends RuntimeException
{
    public function __construct(string $message, public readonly string $name)
    {
        parent::__construct($message);
    }
}