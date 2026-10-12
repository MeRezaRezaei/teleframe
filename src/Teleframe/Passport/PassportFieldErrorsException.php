<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Throwable;

/**
 * PassportFieldErrorsException (Cycle 18 W3) — a passport call that Telegram
 * refused per field, with EVERY failing field carried, not just the first.
 *
 * Why this exists rather than a message string: the whole point of the
 * success criterion is that a per-field refusal is *surfaced*. A flattened
 * "passport failed" message forces the host to parse English to rebuild a form;
 * dropping all but the first error forces the user through N round trips. The
 * caller gets `errors()` as typed `PassportFieldError` objects and
 * `describe()` as the rendered lines.
 *
 * Extends PassportLoginException, so `catch (PassportLoginException)` still
 * covers it, and `catch (PassportFieldErrorsException)` narrows to the
 * field-level case.
 */
final class PassportFieldErrorsException extends PassportLoginException
{
    /** @var list<PassportFieldError> */
    private readonly array $errors;

    /**
     * @param  list<PassportFieldError>  $errors  every failing field, in Telegram's order
     * @param  string  $method  the refused method, for the message
     */
    public function __construct(
        array $errors,
        string $method = '',
        ?Throwable $previous = null,
    ) {
        $this->errors = $errors;

        $rendered = self::render($this->errors);
        $message = 'telegram refused '.($method === '' ? 'the passport call' : $method)
            .' with '.count($this->errors).' field error(s) — '.$rendered;

        parent::__construct($message, $previous, 400);
    }

    /**
     * Every failing field, in Telegram's order.
     *
     * @return list<PassportFieldError>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function count(): int
    {
        return count($this->errors);
    }

    /** The rendered per-field lines, one per error, Telegram's text verbatim. */
    public function describe(): string
    {
        return self::render($this->errors);
    }

    /**
     * @param  list<PassportFieldError>  $errors
     */
    private static function render(array $errors): string
    {
        if ($errors === []) {
            return '(none)';
        }

        $lines = [];
        foreach ($errors as $error) {
            $lines[] = $error->label();
        }

        return implode('; ', $lines);
    }
}
